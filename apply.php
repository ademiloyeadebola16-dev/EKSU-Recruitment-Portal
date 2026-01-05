<?php
session_start();
require 'db.php';
require_once 'job_guard.php';



if (!isset($_SESSION['applicant_email'])) {
    header("Location: applicant_login.php");
    exit();
}

$email  = $_SESSION['applicant_email'];
$job_id = $_GET['job_id'] ?? null;

if (!$job_id) {
    die("Invalid job.");
}

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job || !isJobOpen($job)) {
    die("You cannot apply for this job. This position is closed.");
}



/**
 * Generate applicant number in format:
 * EKSU/APP/YEAR/NNNN
 *
 * Finds the highest existing NNNN for the current year in $applications and returns next.
 */
function generateApplicantNumber(PDO $pdo) {
    $year = date('Y');

    $stmt = $pdo->prepare("
        SELECT applicant_number 
        FROM applications 
        WHERE applicant_number LIKE ?
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute(["EKSU/APP/{$year}/%"]);
    $last = $stmt->fetchColumn();

    $next = 1;
    if ($last) {
        $parts = explode('/', $last);
        if (count($parts) === 4) {
            $next = intval($parts[3]) + 1;
        }
    }

    return "EKSU/APP/{$year}/" . str_pad($next, 4, '0', STR_PAD_LEFT);
}

/*
  NOTE: We require the mail helper but DO NOT call it until after
  the application is successfully saved below.
  Make sure send_application_mail.php defines a function:
    sendApplicantMail($toEmail, $toName, $jobTitle, $applicantNumber = null)
*/
if (file_exists(__DIR__ . '/send_application_mail.php')) {
    require_once __DIR__ . '/send_application_mail.php';
}

// Check if applicant already applied for this job (prevent duplicate)
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM applications 
    WHERE email = ? AND job_id = ?
");
$stmt->execute([$email, $job_id]);
$has_applied = $stmt->fetchColumn() > 0;


// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_applied) {

    // Basic sanitization helpers
    $sanitize = function($v) {
        return is_string($v) ? trim($v) : $v;
    };

    // Collect posted simple fields early (used for email name)
    $first_name  = $sanitize($_POST['first_name'] ?? '');
    $middle_name = $sanitize($_POST['middle_name'] ?? '');
    $last_name   = $sanitize($_POST['last_name'] ?? '');

    // --- handle CV upload ---
    $cv_path = '';
    if (isset($_FILES['cv']) && is_uploaded_file($_FILES['cv']['tmp_name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
        $cv_name = 'CV_' . time() . '_' . uniqid() . '.' . preg_replace('/[^a-z0-9.]/i', '', $file_ext);
        $cv_path = 'uploads/' . $cv_name; // store relative path
        move_uploaded_file($_FILES['cv']['tmp_name'], __DIR__ . '/' . $cv_path);
    }

    // --- handle passport upload ---
    $passport_path = '';
    if (isset($_FILES['passport']) && is_uploaded_file($_FILES['passport']['tmp_name']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
        $passport_dir = __DIR__ . '/uploads/passports/';
        if (!file_exists($passport_dir)) mkdir($passport_dir, 0777, true);
       $ext = strtolower(pathinfo($_FILES['passport']['name'], PATHINFO_EXTENSION));
        $safe = 'passport_' . time() . '_' . uniqid() . '.' . preg_replace('/[^a-z0-9.]/i', '', $ext);
        $passport_rel = 'uploads/passports/' . $safe;
        move_uploaded_file($_FILES['passport']['tmp_name'], __DIR__ . '/' . $passport_rel);
        $passport_path = $passport_rel;
    }

    // --- fuzzy matching for academic qualification & professional body (80% threshold) ---
   $job_qual = strtolower(trim($job['requirement_qualification'] ?? ''));
    $applicant_qual = strtolower(trim($_POST['academic_qualification'] ?? ''));
    $qual_similarity = 0;
    if ($job_qual !== '' && $applicant_qual !== '') {
        similar_text($job_qual, $applicant_qual, $qual_similarity);
    }

    $job_body = strtolower(trim($job['requirement_body'] ?? $job['required_body'] ?? ''));
    $applicant_body = strtolower(trim($_POST['professional_body'] ?? ''));
    $body_similarity = 0;
    if ($job_body !== '' && $applicant_body !== '') {
        similar_text($job_body, $applicant_body, $body_similarity);
    }

    $meets_qual = ($qual_similarity >= 80);
    $meets_body = ($body_similarity >= 80);

    // internal evaluation (for admin) — don't reveal to applicant until admin toggles
    $internal_status = 'Pending';

    // Applicant-visible status defaults to Under Review (admin will change later)
    $public_status = 'Under Review';
    $reason = 'Awaiting admin review';

    // --- handle custom requirement responses (both text and file) ---
    $custom_requirements_responses = [];
    $custom_fields = [];

if (!empty($job['custom_fields'])) {
    if (is_string($job['custom_fields'])) {
        $decoded = json_decode($job['custom_fields'], true);
        $custom_fields = is_array($decoded) ? $decoded : [];
    } elseif (is_array($job['custom_fields'])) {
        $custom_fields = $job['custom_fields'];
    }
}

    $custom_texts = $_POST['custom_text'] ?? [];
    $custom_files = $_FILES['custom_file'] ?? null;

    $custom_upload_dir = __DIR__ . '/uploads/custom_requirements/';
    if (!file_exists($custom_upload_dir)) mkdir($custom_upload_dir, 0777, true);

    foreach ($custom_fields as $i => $label) {
        $label_clean = $sanitize($label);
        $response_text = isset($custom_texts[$i]) ? $sanitize($custom_texts[$i]) : '';

        $response_file_path = '';
        if ($custom_files && isset($custom_files['error'][$i]) && $custom_files['error'][$i] === UPLOAD_ERR_OK) {
            $orig_name = $custom_files['name'][$i];
            $tmp_name  = $custom_files['tmp_name'][$i];
            $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
            $fname = 'custom_' . $job_id . '_' . time() . '_' . uniqid() . "_{$i}." . preg_replace('/[^a-z0-9.]/i', '', $ext);
            $targetRel = 'uploads/custom_requirements/' . $fname;
            $targetAbs = __DIR__ . '/' . $targetRel;
            if (move_uploaded_file($tmp_name, $targetAbs)) {
                $response_file_path = $targetRel;
            }
        }

        $custom_requirements_responses[] = [
            'label' => $label_clean,
            'response_text' => $response_text,
            'response_file' => $response_file_path
        ];
    }

    // --- assemble referees ---
    $referees = [
        [
            'name' => $sanitize($_POST['ref1_name'] ?? ''),
            'phone' => $sanitize($_POST['ref1_phone'] ?? ''),
            'email' => $sanitize($_POST['ref1_email'] ?? ''),
            'occupation' => $sanitize($_POST['ref1_occupation'] ?? '')
        ],
        [
            'name' => $sanitize($_POST['ref2_name'] ?? ''),
            'phone' => $sanitize($_POST['ref2_phone'] ?? ''),
            'email' => $sanitize($_POST['ref2_email'] ?? ''),
            'occupation' => $sanitize($_POST['ref2_occupation'] ?? '')
        ],
        [
            'name' => $sanitize($_POST['ref3_name'] ?? ''),
            'phone' => $sanitize($_POST['ref3_phone'] ?? ''),
            'email' => $sanitize($_POST['ref3_email'] ?? ''),
            'occupation' => $sanitize($_POST['ref3_occupation'] ?? '')
        ]
    ];

    // --- handle academic records (multiple) ---
    $academic_records = [];
    $insts = $_POST['inst_name'] ?? [];
    $quals = $_POST['inst_qualification'] ?? [];
   $fromMonths = $_POST['inst_month_from'] ?? [];
$fromYears  = $_POST['inst_year_from'] ?? [];
$toMonths   = $_POST['inst_month_to'] ?? [];
$toYears    = $_POST['inst_year_to'] ?? [];

    $cert_files = $_FILES['institution_certificate'] ?? null;

    $acad_dir = __DIR__ . '/uploads/academic_records/';
    if (!file_exists($acad_dir)) mkdir($acad_dir, 0777, true);

    $maxCount = max(
    count($insts),
    count($quals),
    count($fromMonths),
    count($fromYears)
);

for ($i = 0; $i < $maxCount; $i++) {
    $n = $sanitize($insts[$i] ?? '');
    $q = $sanitize($quals[$i] ?? '');

    $from = ($fromMonths[$i] ?? '') && ($fromYears[$i] ?? '')
        ? $fromMonths[$i] . '/' . $fromYears[$i]
        : '';

    $to = ($toMonths[$i] ?? '') && ($toYears[$i] ?? '')
        ? $toMonths[$i] . '/' . $toYears[$i]
        : '';

    $cert_path = '';

    if ($cert_files && isset($cert_files['error'][$i]) && $cert_files['error'][$i] === UPLOAD_ERR_OK) {
        $ext = pathinfo($cert_files['name'][$i], PATHINFO_EXTENSION);
        $safe = 'cert_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-z0-9]/i','', $ext);
        $target = $acad_dir . $safe;
        if (move_uploaded_file($cert_files['tmp_name'][$i], $target)) {
            $cert_path = 'uploads/academic_records/' . $safe;
        }
    }

    if ($n === '' && $q === '') continue;

    $academic_records[] = [
        'institution' => $n,
        'qualification' => $q,
        'from' => $from,
        'to' => $to,
        'certificate' => $cert_path
    ];
}

    // --- Generate applicant number (NEW) ---
   $applicant_number = generateApplicantNumber($pdo);

    // --- assemble application entry ---
    $newApplication = [
        'email' => $email,
        'job_id' => $job_id,
        'job_title' => $job['title'] ?? '',
        'department' => $job['department'] ?? '',
        'qualification' => $sanitize($_POST['academic_qualification'] ?? ''),
        'requirement_qualification' => $job['qualification'] ?? '',
        'academic_qualification' => $sanitize($_POST['academic_qualification'] ?? ''),
        'academic_qualification_other' => $sanitize($_POST['academic_qualification_other'] ?? ''),
        'professional_body' => $sanitize($_POST['professional_body'] ?? ''),
        'cover_letter' => $sanitize($_POST['cover_letter'] ?? ''),
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'phone' => $sanitize($_POST['phone'] ?? ''),
        'dob' => $sanitize($_POST['dob'] ?? ''),
        'pob' => $sanitize($_POST['pob'] ?? ''),
        'gender' => $sanitize($_POST['gender'] ?? ''),
        'nationality' => $sanitize($_POST['nationality'] ?? ''),
        'marital_status' => $sanitize($_POST['marital_status'] ?? ''),
        'children' => intval($_POST['children'] ?? 0),
        'permanent_address' => $sanitize($_POST['permanent_address'] ?? ''),
        'state' => $sanitize($_POST['state'] ?? ''),
        'home_town' => $sanitize($_POST['home_town'] ?? ''),
        'lga' => $sanitize($_POST['lga'] ?? ''),
        'publications' => intval($_POST['publications'] ?? 0),
        'experience_years' => intval($_POST['experience'] ?? 0),
        'cv' => $cv_path,
        'passport' => $passport_path,
        'academic_records' => $academic_records,
        'internal_status' => $internal_status,
        'status' => $public_status,
        'reason' => $reason,
        'qualification_similarity' => round($qual_similarity, 2),
        'body_similarity' => round($body_similarity, 2),
        'custom_requirements' => $custom_requirements_responses,
        'status_visible' => false,
        'date' => date('Y-m-d H:i:s'),
        'applicant_number' => $applicant_number // <-- saved here
    ];

    $stmt = $pdo->prepare("
    INSERT INTO applications (
        applicant_number, email, job_id, job_title, department,
        first_name, middle_name, last_name, phone, dob, pob, gender,
        nationality, marital_status, children, permanent_address,
        state, home_town, lga,
        academic_qualification, academic_qualification_other,
        professional_body, publications, experience_years,
        cover_letter, cv, passport,
        academic_records, custom_requirements,
        internal_status, status, reason,
        qualification_similarity, body_similarity,
        status_visible, created_at
    ) VALUES (
        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW()
    )
");

$saved = $stmt->execute([
    $applicant_number,
    $email,
    $job_id,
    $job['title'] ?? '',
    $job['department'] ?? '',
    $first_name,
    $middle_name,
    $last_name,
    $sanitize($_POST['phone'] ?? ''),
    $sanitize($_POST['dob'] ?? ''),
    $sanitize($_POST['pob'] ?? ''),
    $sanitize($_POST['gender'] ?? ''),
    $sanitize($_POST['nationality'] ?? ''),
    $sanitize($_POST['marital_status'] ?? ''),
    intval($_POST['children'] ?? 0),
    $sanitize($_POST['permanent_address'] ?? ''),
    $sanitize($_POST['state'] ?? ''),
    $sanitize($_POST['home_town'] ?? ''),
    $sanitize($_POST['lga'] ?? ''),
    $sanitize($_POST['academic_qualification'] ?? ''),
    $sanitize($_POST['academic_qualification_other'] ?? ''),
    $sanitize($_POST['professional_body'] ?? ''),
    intval($_POST['publications'] ?? 0),
    intval($_POST['experience'] ?? 0),
    $sanitize($_POST['cover_letter'] ?? ''),
    $cv_path,
    $passport_path,
    json_encode($academic_records),
    json_encode($custom_requirements_responses),
    $internal_status,
    $public_status,
    $reason,
    round($qual_similarity, 2),
    round($body_similarity, 2),
    0
]);


if ($saved !== false) {

    // 🔑 Get the newly inserted application ID
    $applicationId = $pdo->lastInsertId();

    // ================= SAVE REFEREES =================
    $refStmt = $pdo->prepare("
        INSERT INTO referees (application_id, name, occupation, email, phone)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($referees as $ref) {
        if (!empty($ref['name'])) {
            $refStmt->execute([
                $applicationId,
                $ref['name'],
                $ref['occupation'],
                $ref['email'],
                $ref['phone']
            ]);
        }
    }

    // ================= SEND EMAIL =================
    if (function_exists('sendApplicantMail')) {
        $toEmail = $email;
        $toName = trim($first_name . ' ' . $last_name);
        $jobPosition = $job['position'] ?? 'Job Application';

        try {
            sendApplicantMail($toEmail, $toName, $jobPosition);
        } catch (\Throwable $e) {
            @file_put_contents(
                __DIR__ . '/email_errors.log',
                date('c') . " - sendApplicantMail error: " . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    // ================= REDIRECT =================
    header(
        "Location: applicant_dashboard.php?success=1&applicant_number=" .
        urlencode($applicant_number)
    );
    exit();
} else {
    $error = "Unable to save your application at the moment. Please try again later.";
}


// ----------------- Render form -----------------
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Application for <?= htmlspecialchars($job['position']) ?></title>
<style>
body {
    font-family: Arial, sans-serif;
    background:#eef1f5;
    margin:0;
}
nav {
    background: #800000; 
    color: #fff;
    padding: 15px 10%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nav-container {
    display: flex;
    align-items: center;
    gap: 15px;
}
.nav-container img {
    width: 70px;
    height: 70px;
    border-radius: 5px;
}
nav h1 {
    font-size: 22px;
    margin: 0;
}
.nav-text h5 {
    margin: 0;
    font-size: 14px;
    font-weight: normal;
    color: #ddd;
}
.container {
    max-width:900px;
    margin:40px auto;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
h2, h3 {
    color:#800000;
}
input, select, textarea {
    width:100%;
    padding:10px;
    margin-top:8px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:5px;
    box-sizing: border-box;
}
button {
    background:#800000;
    color:white;
    border:none;
    border-radius:5px;
    padding:10px 20px;
    cursor:pointer;
}
button:hover { background:#660000; }
fieldset {
    border:1px solid #ccc;
    border-radius:8px;
    padding:15px;
    margin-top:20px;
}
legend {
    font-weight:bold;
    color:#800000;
}
.notice {
    background:#ffd6d6;
    border:1px solid #ff8c8c;
    color:#800000;
    padding:15px;
    border-radius:5px;
    margin-bottom:20px;
}
.custom-block {
    margin-bottom: 18px;
}
.custom-block label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}
.custom-block input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-bottom: 8px;
}
.custom-block input[type="file"] {
    display: block;
    margin-top: 4px;
}
.academic-record {
    border:1px solid #eee;
    padding:12px;
    border-radius:6px;
    margin-bottom:12px;
    background:#fafafa;
}
.add-acad { background:#800000; margin-bottom:10px; }
.remove-acad { background:#b30000; margin-top:8px; }
@media print {
    nav, footer, .nav-container, .print-btn {
        display: none !important;
    }
    body {
        background: #fff;
    }
    .container {
        box-shadow: none;
        margin: 0;
        padding: 0;
    }
}
</style>
</head>
<body>
  <nav>
    <div class="nav-container">
        <img src="logo.jfif" alt="Site Logo">
        <div class="nav-text">
            <h1>Ekiti State University, Ado-Ekiti</h1>
            <h5>Job Recruitment Portal</h5>
        </div>
    </div>
    <div>
        <a href="index.php" style="background:white; color:#004080; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:bold;">Home</a>
    </div>
</nav>

<?php
// include header if available
if (file_exists(__DIR__ . '/header.php')) include __DIR__ . '/header.php';
?>

<div class="container">
  <h2>Application for <?= htmlspecialchars($job['position']) ?></h2>
  <p><strong>Department:</strong> <?= htmlspecialchars($job['department'] ?? '') ?></p>
  <p><strong>Minimum Qualification Required:</strong> 
<?= htmlspecialchars($job['requirement_qualification'] ?? '') ?></p>


  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

    <button class="print-btn" onclick="window.print()" 
style="
    background:#800000;
    color:white;
    border:none;
    padding:10px 15px;
    border-radius:5px;
    float:right;
    margin-bottom:10px;
    cursor:pointer;
">
  🖨️ Print Application
    </button>

  <?php if ($has_applied): ?>
    <div class="notice">
       You have already applied for this job. You cannot apply again.
    </div>
  <?php else: ?>
    <form method="POST" enctype="multipart/form-data" novalidate>
      <!-- Personal -->
      <fieldset>
    <legend>Personal Information</legend>

    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="middle_name" placeholder="Middle Name">
    <input type="text" name="last_name" placeholder="Last Name" required>

    <input type="tel" name="phone" placeholder="Phone Number" required>

    <input type="email" name="email" placeholder="Email Address" value="<?= htmlspecialchars($email) ?>" readonly>

    <label>Date of Birth</label>
    <input type="date" name="dob" required>

    <input type="text" name="pob" placeholder="Place of Birth" required>

    <label>Gender</label>
    <select name="gender" required>
        <option value="">-- Select Gender --</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
    </select>

    <input type="text" name="nationality" placeholder="Nationality" required>

    <!-- NEW FIELDS START -->

    <label>Marital Status</label>
    <select name="marital_status" required>
        <option value="">-- Select Marital Status --</option>
        <option value="Single">Single</option>
        <option value="Married">Married</option>
        <option value="Divorced">Divorced</option>
        <option value="Widowed">Widowed</option>
    </select>

    <input type="number" name="children" min="0" placeholder="Number of Children" required>

    <textarea name="permanent_address" rows="3" placeholder="Permanent Contact Address" required></textarea>


    <input type="text" name="state" placeholder="State of Origin" required>

    <input type="text" name="home_town" placeholder="Home Town" required>

    <input type="text" name="lga" placeholder="Local Government Area" required>

</fieldset>
      <!-- Professional -->
      <fieldset>
    <legend>Professional Information</legend>

    <label>Academic Qualification</label>
    <select name="academic_qualification" id="academic_qualification" required>
        <option value="">-- Select Qualification --</option>
        <option value="SSCE / O-Level">SSCE / O-Level</option>
        <option value="ND (National Diploma)">ND (National Diploma)</option>
        <option value="NCE">NCE</option>
        <option value="HND">HND</option>
        <option value="B.Sc">B.Sc</option>
        <option value="B.A">B.A</option>
        <option value="B.Ed">B.Ed</option>
        <option value="B.Eng">B.Eng</option>
        <option value="LL.B">LL.B</option>
        <option value="MBBS">MBBS</option>
        <option value="M.Sc">M.Sc</option>
        <option value="M.A">M.A</option>
        <option value="M.Ed">M.Ed</option>
        <option value="MBA">MBA</option>
        <option value="M.Eng">M.Eng</option>
        <option value="PhD">PhD</option>
        <option value="Other">Other (specify)</option>
    </select>

    <!-- Hidden "Other" qualification textbox -->
    <input type="text" name="academic_qualification_other" id="academic_qualification_other"
        placeholder="Specify qualification"
        style="display:none; margin-top:8px;">

    <label>Professional Body Registered With</label>
    <input type="text" name="professional_body" placeholder="e.g. COREN, NCS, ICAN" required>

    <label>Number of Publications</label>
    <input type="number" name="publications" min="0" placeholder="e.g. 5" required>

    <label>Years of Experience</label>
    <input type="number" name="experience" min="0" placeholder="e.g. 3" required>

    <label>Cover Letter / Description</label>
    <textarea name="cover_letter" rows="4" placeholder="Write your cover letter here..." required></textarea>

    <label>Upload CV (PDF or DOCX)</label>
    <input type="file" name="cv" accept=".pdf,.doc,.docx" required>
</fieldset>


      <!-- Passport + Academic Records (merged from File 2/3) -->
      <fieldset>
        <legend>Passport Photograph & Academic Records</legend>
        <p class="small">Upload your passport photograph and list your academic records.</p>

        <label>Passport Photograph (jpg/png)</label>
        <input type="file" name="passport" accept=".jpg,.jpeg,.png" required>

        <hr style="margin:12px 0">

        <div id="academicRecordsContainer">
          <!-- initial single academic record block -->
          <div class="academic-record">
            <label>Institution</label>
            <input type="text" name="inst_name[]" placeholder="Name of Institution" required>

            <label>Qualification Obtained</label>
            <input type="text" name="inst_qualification[]" placeholder="e.g. B.Sc Computer Science" required>

           <div style="display:flex; gap:10px; margin-bottom:12px;">

  <!-- FROM -->
  <div style="flex:1">
    <label>Date From</label>
    <div style="display:flex; gap:5px;">
      
      <!-- Month From -->
      <select name="inst_month_from[]" required>
        <option value="">Month</option>
        <?php 
          $months = [
            "01"=>"January","02"=>"February","03"=>"March","04"=>"April",
            "05"=>"May","06"=>"June","07"=>"July","08"=>"August",
            "09"=>"September","10"=>"October","11"=>"November","12"=>"December"
          ];
          foreach ($months as $num=>$name) {
            echo "<option value='$num'>$name</option>";
          }
        ?>
      </select>

      <!-- Year From -->
      <select name="inst_year_from[]" required>
        <option value="">Year</option>
        <?php 
          $currentYear = date("Y");
          for ($y = $currentYear; $y >= 1950; $y--) {
              echo "<option value='$y'>$y</option>";
          }
        ?>
      </select>

    </div>
  </div>

  <!-- TO -->
  <div style="flex:1">
    <label>Date To</label>

    <div style="display:flex; gap:5px; align-items:center;">

      <!-- Month To -->
      <select name="inst_month_to[]" class="month-to">
        <option value="">Month</option>
        <?php 
          foreach ($months as $num=>$name) {
            echo "<option value='$num'>$name</option>";
          }
        ?>
      </select>

      <!-- Year To -->
      <select name="inst_year_to[]" class="year-to">
        <option value="">Year</option>
        <?php 
          for ($y = $currentYear; $y >= 1950; $y--) {
              echo "<option value='$y'>$y</option>";
          }
        ?>
      </select>

      <!-- Present Checkbox -->
      <label style="display:flex; align-items:center; font-size:13px;">
        <input type="checkbox" class="present-checkbox" style="margin-right:4px;">
        Present
      </label>

    </div>
  </div>

</div>

            <label>Upload Certificate </label>
            <input type="file" name="institution_certificate[]" accept=".pdf,.jpg,.jpeg,.png">

            <button type="button" class="remove-acad" onclick="this.closest('.academic-record').remove()" style="display:none">Remove</button>
          </div>
        </div>

        <p>
          <button type="button" class="add-acad" id="addAcademicBtn">+ Add More Academic Record</button>
        </p>
      </fieldset>

      <!-- Custom Requirements (text + optional file per requirement) -->
      <?php
$custom_fields = [];

if (!empty($job['custom_fields'])) {
    if (is_string($job['custom_fields'])) {
        $decoded = json_decode($job['custom_fields'], true);
        $custom_fields = is_array($decoded) ? $decoded : [];
    } elseif (is_array($job['custom_fields'])) {
        $custom_fields = $job['custom_fields'];
    }
}

if (!empty($custom_fields)):
?>
  <fieldset>
    <legend>Additional Requirements</legend>
    <p class="small">
      For each requirement below you may provide a short text response
      and/or upload a supporting file.
    </p>

    <?php foreach ($custom_fields as $i => $label): ?>
      <div style="margin-bottom:12px;">
        <label><strong><?= htmlspecialchars($label) ?></strong></label>
        <div class="custom-row">
          <input type="text" name="custom_text[]" placeholder="Your response">
          <input type="file" name="custom_file[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
        </div>
      </div>
    <?php endforeach; ?>
  </fieldset>
<?php endif; ?>

      <!-- Referees -->
      <fieldset>
        <legend>Referee 1</legend>
        <input type="text" name="ref1_name" placeholder="Full Name" required>
        <input type="tel" name="ref1_phone" placeholder="Phone Number" required>
        <input type="email" name="ref1_email" placeholder="Email Address" required>
        <input type="text" name="ref1_occupation" placeholder="Occupation" required>
      </fieldset>

      <fieldset>
        <legend>Referee 2</legend>
        <input type="text" name="ref2_name" placeholder="Full Name" required>
        <input type="tel" name="ref2_phone" placeholder="Phone Number" required>
        <input type="email" name="ref2_email" placeholder="Email Address" required>
        <input type="text" name="ref2_occupation" placeholder="Occupation" required>
      </fieldset>

      <fieldset>
        <legend>Referee 3</legend>
        <input type="text" name="ref3_name" placeholder="Full Name" required>
        <input type="tel" name="ref3_phone" placeholder="Phone Number" required>
        <input type="email" name="ref3_email" placeholder="Email Address" required>
        <input type="text" name="ref3_occupation" placeholder="Occupation" required>
      </fieldset>

      <button type="submit">Submit Application</button>
    </form>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;

    // Manage dynamic academic records
    const addBtn = document.getElementById('addAcademicBtn');
    const container = document.getElementById('academicRecordsContainer');

    addBtn.addEventListener('click', function() {
        const block = document.createElement('div');
        block.className = 'academic-record';
        block.innerHTML = `
            <label>Institution</label>
            <input type="text" name="inst_name[]" placeholder="Name of Institution" required>

            <label>Qualification Obtained</label>
            <input type="text" name="inst_qualification[]" placeholder="e.g. B.Sc Computer Science" required>

            <div style="display:flex;gap:10px">
              <div style="flex:1">
                <label>Date From</label>
                <input type="text" name="inst_from[]" placeholder="YYYY" required>
              </div>
              <div style="flex:1">
                <label>Date To</label>
                <input type="text" name="inst_to[]" placeholder="YYYY or present" required>
              </div>
            </div>

            <label>Upload Certificate (optional)</label>
            <input type="file" name="institution_certificate[]" accept=".pdf,.jpg,.jpeg,.png">

            <button type="button" class="remove-acad" onclick="this.closest('.academic-record').remove()">Remove</button>
        `;
        container.appendChild(block);
    });
const qualSelect = document.getElementById('academic_qualification');
const otherField = document.getElementById('academic_qualification_other');

qualSelect.addEventListener('change', function () {
    if (this.value === 'Other') {
        otherField.style.display = 'block';
        otherField.required = true;
    } else {
        otherField.style.display = 'none';
        otherField.required = false;
        otherField.value = '';
    }
});

    form.addEventListener('submit', function(e) {
        e.preventDefault(); // prevent default submission

        // Collect required fields
        const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
        let allFilled = true;
        let missingFields = [];

        requiredFields.forEach(field => {
            // For file inputs, check files length
            if (field.type === 'file') {
                if (field.files.length === 0) {
                    allFilled = false;
                    missingFields.push(field.name || 'file');
                }
            } else {
                if (!field.value.trim()) {
                    allFilled = false;
                    missingFields.push(field.placeholder || field.name);
                }
            }
        });

        if (!allFilled) {
            alert("Please fill in all required fields:\n" + missingFields.join(", "));
            return;
        }
        // Confirm submission
        const confirmSubmit = confirm(
            "Are you sure you want to submit your application?\n" +
            "You can cancel and modify your application before submitting."
        );

        if (confirmSubmit) {
            // disable submit button to help prevent double submits
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }
            form.submit(); // submit the form if confirmed
        } else {
            alert("You can now review and modify your application.");
        }
    });
});
// Handle Present checkbox toggle
document.querySelectorAll(".present-checkbox").forEach((chk) => {
    chk.addEventListener("change", function () {

        const container = this.closest("div");
        const monthSelect = container.querySelector(".month-to");
        const yearSelect  = container.querySelector(".year-to");

        if (this.checked) {
            // Disable month & year
            monthSelect.disabled = true;
            yearSelect.disabled = true;

            // Clear existing values
            monthSelect.value = "";
            yearSelect.value = "";
        } else {
            monthSelect.disabled = false;
            yearSelect.disabled = false;
        }
    });
});

// Before submitting, convert "Present" rows correctly
document.querySelector("form").addEventListener("submit", function () {
    document.querySelectorAll(".present-checkbox").forEach((chk) => {
        const container = chk.closest("div");

        if (chk.checked) {
            // Create hidden fields so the server receives consistent values
            const hiddenMonth = document.createElement("input");
            hiddenMonth.type = "hidden";
            hiddenMonth.name = "inst_month_to[]";
            hiddenMonth.value = "Present";
            container.appendChild(hiddenMonth);

            const hiddenYear = document.createElement("input");
            hiddenYear.type = "hidden";
            hiddenYear.name = "inst_year_to[]";
            hiddenYear.value = "Present";
            container.appendChild(hiddenYear);
        }
    });
});
</script>

<?php
if (file_exists(__DIR__ . '/footer.php')) include __DIR__ . '/footer.php';
?>
</body>
</html>