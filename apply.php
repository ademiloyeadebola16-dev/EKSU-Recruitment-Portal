<?php
session_start();
if (!isset($_SESSION['applicant_email'])) {
    header("Location: applicant_login.php");
    exit();
}

$email = $_SESSION['applicant_email'];
$job_id = $_GET['job_id'] ?? null;

// Load job details
$jobs_file = 'jobs.json';
$jobs = file_exists($jobs_file) ? json_decode(file_get_contents($jobs_file), true) : [];
if ($job_id === null || !isset($jobs[$job_id])) {
    echo "Invalid or missing job ID.";
    exit();
}
$job = $jobs[$job_id];

// Load existing applications
$applications_file = 'applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

require "send_application_mail.php";

// Prepare email variables
$toEmail = $email;
$toName = $first_name . " " . $last_name;
$jobTitle = $job['title'] ?? 'Job Application';

// Send email notification
sendApplicantMail($toEmail, $toName, $jobTitle);

// Check if applicant already applied for this job (prevent duplicate)
$has_applied = false;
foreach ($applications as $app) {
    if (isset($app['email']) && $app['email'] === $email && isset($app['job_id']) && $app['job_id'] == $job_id) {
        $has_applied = true;
        break;
    }
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_applied) {

    // --- handle CV upload ---
    $cv_path = '';
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
        $cv_name = 'CV_' . time() . '_' . uniqid() . '.' . $file_ext;
        $cv_path = $upload_dir . $cv_name;
        move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path);
    }

    // --- fuzzy matching for academic qualification & professional body (80% threshold) ---
    $job_qual = strtolower(trim($job['qualification'] ?? ''));
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
    $internal_status = ($meets_qual && $meets_body) ? 'Qualified' : 'Not Qualified';

    // Applicant-visible status defaults to Under Review (admin will change later)
    $public_status = 'Under Review';
    $reason = 'Awaiting admin review';

    // --- handle custom requirement responses (both text and file) ---
    $custom_requirements_responses = [];
    // Expect job may have 'custom_fields' (array of strings)
    $custom_fields = $job['custom_fields'] ?? [];
    // Gather posted text responses
    $custom_texts = $_POST['custom_text'] ?? [];
    // Files for custom fields come in $_FILES['custom_file']
    $custom_files = $_FILES['custom_file'] ?? null;

    // Prepare upload dir for custom responses
    $custom_upload_dir = 'uploads/custom_requirements/';
    if (!file_exists($custom_upload_dir)) mkdir($custom_upload_dir, 0777, true);

    // Process each custom field (index alignment)
    foreach ($custom_fields as $i => $label) {
        $label_clean = trim($label);
        $response_text = isset($custom_texts[$i]) ? trim($custom_texts[$i]) : '';

        $response_file_path = '';
        // Check if file was uploaded for this index
        if ($custom_files && isset($custom_files['error'][$i]) && $custom_files['error'][$i] === UPLOAD_ERR_OK) {
            $orig_name = $custom_files['name'][$i];
            $tmp_name = $custom_files['tmp_name'][$i];
            $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
            $fname = 'custom_' . $job_id . '_' . time() . '_' . uniqid() . "_{$i}." . $ext;
            $target = $custom_upload_dir . $fname;
            if (move_uploaded_file($tmp_name, $target)) {
                $response_file_path = $target;
            }
        }

        // Only save non-empty responses (but include empty if admin expects them)
        $custom_requirements_responses[] = [
            'label' => $label_clean,
            'response_text' => $response_text,
            'response_file' => $response_file_path
        ];
    }

    // --- assemble application entry ---
    $newApplication = [
        'email' => $email,
        'job_id' => $job_id,
        'job_title' => $job['title'] ?? '',
        'department' => $job['department'] ?? '',
        // save applicant-provided, and also job requirement for later reference
        'qualification' => $_POST['academic_qualification'] ?? '',
        'requirement_qualification' => $job['qualification'] ?? '',
        'academic_qualification' => $_POST['academic_qualification'] ?? '',
        'professional_body' => $_POST['professional_body'] ?? '',
        'cover_letter' => $_POST['cover_letter'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'dob' => $_POST['dob'] ?? '',
        'pob' => $_POST['pob'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'nationality' => $_POST['nationality'] ?? '',
        'state' => $_POST['state'] ?? '',
        'lga' => $_POST['lga'] ?? '',
        'publications' => $_POST['publications'] ?? 0,
        'experience_years' => $_POST['experience'] ?? 0,
        'cv' => $cv_path,
        'referees' => [
            [
                'name' => $_POST['ref1_name'] ?? '',
                'phone' => $_POST['ref1_phone'] ?? '',
                'email' => $_POST['ref1_email'] ?? '',
                'occupation' => $_POST['ref1_occupation'] ?? ''
            ],
            [
                'name' => $_POST['ref2_name'] ?? '',
                'phone' => $_POST['ref2_phone'] ?? '',
                'email' => $_POST['ref2_email'] ?? '',
                'occupation' => $_POST['ref2_occupation'] ?? ''
            ]
        ],
        // Both admin/internal and public properties
        'internal_status' => $internal_status,          // Qualified / Not Qualified (admin use)
        'status' => $public_status,                     // Under Review (applicant sees this by default)
        'reason' => $reason,                            // admin remark placeholder
        'qualification_similarity' => round($qual_similarity, 2),
        'body_similarity' => round($body_similarity, 2),
        'custom_requirements' => $custom_requirements_responses,
        'status_visible' => false,                      // admin must toggle to reveal actual status/reason
        'date' => date('Y-m-d H:i:s')
    ];

    // Append and save
    $applications[] = $newApplication;
    file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));

    // Redirect to dashboard with success
    header("Location: applicant_dashboard.php?success=1");
    exit();
}

// ----------------- Render form -----------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Apply for <?= htmlspecialchars($job['title']) ?></title>
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
}
button {
    background:#800000;
    color:white;
    border:none;
    border-radius:5px;
    padding:10px 20px;
    cursor:pointer;
}
button:hover {
    background:#800000;
}
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
        <a href="index.php" style="background:white; color:#004080; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:bold;">🏠 Home</a>
    </div>
</nav>
<?php
// include header if you have it
if (file_exists('header.php')) include 'header.php';
?>
<div class="container">
  <h2>Apply for <?= htmlspecialchars($job['title']) ?></h2>
  <p><strong>Department:</strong> <?= htmlspecialchars($job['department'] ?? '') ?></p>
  <p><strong>Minimum Qualification Required:</strong> <?= htmlspecialchars($job['qualification'] ?? '') ?></p>

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
        <input type="text" name="state" placeholder="State of Origin" required>
        <input type="text" name="lga" placeholder="Local Government Area" required>
      </fieldset>

      <!-- Professional -->
      <fieldset>
        <legend>Professional Information</legend>
        <label>Academic Qualification</label>
        <input type="text" name="academic_qualification" placeholder="e.g. Ph.D in Computer Science" required>

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

      <!-- Custom Requirements (text + optional file per requirement) -->
      <?php
        $custom_fields = $job['custom_fields'] ?? [];
        if (!empty($custom_fields)): ?>
          <fieldset>
            <legend>Additional Requirements</legend>
            <p class="small">For each requirement below you may provide a short text response and/or upload a supporting file.</p>

            <?php foreach ($custom_fields as $i => $label): ?>
              <div style="margin-bottom:12px;">
                <label><strong><?= htmlspecialchars($label) ?></strong></label>
                <div class="custom-row">
                  <input type="text" name="custom_text[]" placeholder="Your response (optional)">
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
        <input type="text" name="ref2_name" placeholder="Full Name" required>
        <input type="tel" name="ref2_phone" placeholder="Phone Number" required>
        <input type="email" name="ref2_email" placeholder="Email Address" required>
        <input type="text" name="ref2_occupation" placeholder="Occupation" required>
      </fieldset>

      <button type="submit">Submit Application</button>
    </form>
  <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        e.preventDefault(); // prevent default submission

        // Collect required fields
        const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
        let allFilled = true;
        let missingFields = [];

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                allFilled = false;
                missingFields.push(field.placeholder || field.name);
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
            form.submit(); // submit the form if confirmed
        } else {
            alert("You can now review and modify your application.");
        }
    });
});
</script>


<?php
if (file_exists('footer.php')) include 'footer.php';
?>
</body>
</html>
