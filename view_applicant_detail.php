<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$index = $_GET['index'] ?? null;
if ($index === null) {
    die("Invalid request.");
}

$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ? LIMIT 1");
$stmt->execute([$index]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die("Application not found.");
}
// ================= FETCH REFEREES FROM DB =================
$refStmt = $pdo->prepare("
    SELECT name, occupation, email, phone
    FROM referees
    WHERE application_id = ?
    ORDER BY id ASC
");
$refStmt->execute([$app['id']]);
$referees = $refStmt->fetchAll(PDO::FETCH_ASSOC);
$academic_records = [];

if (!empty($app['academic_records'])) {
    if (is_string($app['academic_records'])) {
        $decoded = json_decode($app['academic_records'], true);
        $academic_records = is_array($decoded) ? $decoded : [];
    } elseif (is_array($app['academic_records'])) {
        $academic_records = $app['academic_records'];
    }
}


$applicantId = $app['applicant_number'] ?? 'N/A';

// Build full name
$fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));

// Load passport
$passport = !empty($app['passport']) ? $app['passport'] : "no-image.png";

// Status
$status = ucfirst(strtolower($app['status'] ?? 'Pending'));
$reason = !empty($app['reason']) ? $app['reason'] : "No remarks available.";
$status_visible = $app['status_visible'] ?? false;

// Load job requirement to match qualification
$job_requirements = 'job_requirements.json';
$requirements = file_exists($job_requirements) ? json_decode(file_get_contents($job_requirements), true) : [];

$qualification_match = 0;
$professional_match = 0;

if (!empty($requirements) && isset($app['job_title'])) {
    foreach ($requirements as $req) {
        if (strcasecmp($req['title'], $app['job_title']) === 0) {
            similar_text(strtolower($app['qualification'] ?? ''), strtolower($req['qualification'] ?? ''), $qualification_match);
            similar_text(strtolower($app['professional_body'] ?? ''), strtolower($req['professional_body'] ?? ''), $professional_match);
            break;
        }
    }
}

$qualification_match = round($qualification_match, 1);
$professional_match = round($professional_match, 1);
$academic_records = [];

if (!empty($app['academic_records'])) {
    $decoded = json_decode($app['academic_records'], true);
    if (is_array($decoded)) {
        $academic_records = $decoded;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applicant Details</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #eef1f5;
    margin: 0;
    padding: 0;
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

.nav-text h1 { margin: 0; font-size: 22px; }
.nav-text h5 { margin: 0; color: #ddd; }

a.button {
    padding: 10px 18px;
    background: #008000;
    color: white;
    border-radius: 5px;
    text-decoration: none;
}
a.button:hover { background: #006400; }

.container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
}

/* SECTION HEADINGS */
.section-title {
    font-size: 20px;
    color: #800000;
    border-left: 5px solid #800000;
    padding-left: 10px;
    margin-top: 35px;
    margin-bottom: 15px;
}

/* PASSPORT + NAME BAR */
.header-box {
    display: flex;
    gap: 20px;
    align-items: center;
    padding-bottom: 20px;
    border-bottom: 2px solid #ccc;
}

.passport-box img {
    width: 130px;
    height: 150px;
    object-fit: cover;
    border: 3px solid #800000;
    border-radius: 5px;
}

.header-info {
    flex: 1;
}

.header-info h2 {
    margin: 0;
    font-size: 26px;
    color: #800000;
}

.header-info p {
    margin: 6px 0;
}

.status-box {
    border-left: 6px solid #800000;
    background: #f7f7f7;
    padding: 15px;
    border-radius: 8px;
    margin-top: 25px;
}

.status-qualified { color: green; font-weight: bold; }
.status-notqualified { color: red; font-weight: bold; }
.status-disqualified { color: orange; font-weight: bold; }
.status-pending { color: gray; font-weight: bold; }

/* PRINT */
@media print {
    nav, .print-btn { display: none !important; }
    .container { box-shadow: none; }
}
.print-btn {
    background:#800000;
    color:white;
    padding:10px 20px;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
.print-btn:hover { background:#00264d; }
</style>

</head>
<body>

<nav>
    <div class="nav-container">
        <img src="logo.jfif" alt="Logo">
        <div class="nav-text">
            <h1>Ekiti State University, Ado-Ekiti</h1>
            <h5>Recruitment Portal</h5>
        </div>
    </div>

    <div>
        <a href="view_applications.php" class="button">⬅ Back</a>
        <a href="admin_logout.php" class="button" style="background:#cc0000;">Logout</a>
    </div>
</nav>

<div class="container">

    <div class="header-box">
    <div class="passport-box">
        <img src="<?= htmlspecialchars($passport) ?>" alt="Passport">
    </div>

    <div class="header-info">
    <h2><?= htmlspecialchars($fullName) ?></h2>

    <!-- Applicant ID -->
    <p style="font-weight:bold; color:#333;">
        Applicant ID: 
        <span style="color:#800000;">
            <?= htmlspecialchars($applicantId) ?>
        </span>
    </p>

    <!-- Job -->
    <p><strong>Job Applied For:</strong> <?= htmlspecialchars($app['job_title'] ?? '') ?></p>

    <!-- Status -->
    <p><strong>Status:</strong> 
        <span class="status-<?= strtolower($status) ?>">
            <?= htmlspecialchars($status) ?>
        </span>
    </p>
</div>
</div>

    <!-- STATUS VISIBILITY -->
    <p><strong>Status Visibility:</strong>
        <?= $status_visible 
            ? "<span style='color:green;font-weight:bold;'>Visible to Applicant</span>"
            : "<span style='color:red;font-weight:bold;'>Hidden from Applicant</span>" ?>
    </p>

    <form method="post" action="toggle_status_visibility.php">
    <input type="hidden" name="index" value="<?= (int)$index ?>">

    <input type="hidden" name="action"
        value="<?= $status_visible ? 'hide' : 'show' ?>">

    <button type="submit" class="print-btn" style="background:#800000;">
        <?= $status_visible ? "Hide Status" : "Show Status to Applicant" ?>
    </button>
</form>


    <!-- PERSONAL INFORMATION -->
    <h3 class="section-title">Personal Information</h3>

    <p><strong>Full Name:</strong> <?= htmlspecialchars($fullName) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($app['phone'] ?? '') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($app['email'] ?? '') ?></p>
    <p><strong>Date of Birth:</strong> <?= htmlspecialchars($app['dob'] ?? '') ?></p>
    <p><strong>Place of Birth:</strong> <?= htmlspecialchars($app['pob'] ?? '') ?></p>
    <p><strong>Gender:</strong> <?= htmlspecialchars($app['gender'] ?? '') ?></p>
    <p><strong>Nationality:</strong> <?= htmlspecialchars($app['nationality'] ?? '') ?></p>
    <p><strong>Permanent Address:</strong> <?= htmlspecialchars($app['permanent_address'] ?? '') ?></p>
    <p><strong>State:</strong> <?= htmlspecialchars($app['state'] ?? '') ?></p>
    <p><strong>LGA:</strong> <?= htmlspecialchars($app['lga'] ?? '') ?></p>
    <p><strong>Home Town:</strong> <?= htmlspecialchars($app['home_town'] ?? '') ?></p>
    <p><strong>Marital Status:</strong> <?= htmlspecialchars($app['marital_status'] ?? '') ?></p>
    <p><strong>Number of Children:</strong> <?= htmlspecialchars($app['children'] ?? '') ?></p>

    <!-- QUALIFICATION -->
    <h3 class="section-title">Qualification Information</h3>

    <p><strong>Academic Qualification:</strong> <?= htmlspecialchars($app['academic_qualification'] ?? '') ?></p>
    <p><strong>Professional Body:</strong> <?= htmlspecialchars($app['professional_body'] ?? '') ?></p>
    <p><strong>Years of Experience:</strong> <?= htmlspecialchars($app['experience_years'] ?? '') ?></p>
    <p><strong>Number of Publications:</strong> <?= htmlspecialchars($app['publications'] ?? '') ?></p>

    <!-- MATCH BOX -->
    <div class="status-box">
        <h3>Qualification Review Summary</h3>

        <p><strong>Status:</strong>
            <span class="status-<?= strtolower($status) ?>"><?= htmlspecialchars($status) ?></span>
        </p>

        <p><strong>Remarks:</strong><br><?= nl2br(htmlspecialchars($reason)) ?></p>
        <hr>

        <p><strong>Qualification Match:</strong> <?= $qualification_match ?>%</p>
        <p><strong>Professional Body Match:</strong> <?= $professional_match ?>%</p>
    </div>

    <h3 class="section-title">Referees</h3>

<?php if (!empty($referees)): ?>
    <table width="100%" cellpadding="8" cellspacing="0" border="1">
        <thead style="background:#f5f5f5">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Occupation</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($referees as $i => $ref): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($ref['name']) ?></td>
                    <td><?= htmlspecialchars($ref['occupation']) ?></td>
                    <td><?= htmlspecialchars($ref['email']) ?></td>
                    <td><?= htmlspecialchars($ref['phone']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="color:#777;">No referees provided.</p>
<?php endif; ?>


    <!-- CV -->
    <?php if (!empty($app['cv'])): ?>
        <p><strong>CV:</strong> <a href="<?= htmlspecialchars($app['cv']) ?>" target="_blank">View CV</a></p>
    <?php endif; ?>
        <h3 class="section-title">Academic Records</h3>

<?php if (!empty($academic_records)): ?>
    <table width="100%" cellpadding="8" cellspacing="0" border="1">
        <thead style="background:#f5f5f5">
            <tr>
                <th>#</th>
                <th>Institution</th>
                <th>Qualification</th>
                <th>Period</th>
                <th>Certificate</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($academic_records as $i => $rec): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($rec['institution'] ?? '') ?></td>
                <td><?= htmlspecialchars($rec['qualification'] ?? '') ?></td>
                <td>
                    <?= htmlspecialchars(($rec['from'] ?? '') . ' - ' . ($rec['to'] ?? '')) ?>
                </td>
                <td>
                    <?php if (!empty($rec['certificate'])): ?>
                        📄 <a href="<?= htmlspecialchars($rec['certificate']) ?>" target="_blank">
                            View Certificate
                        </a>
                    <?php else: ?>
                        <span style="color:#777;">Not uploaded</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="color:#777;">No academic records uploaded.</p>
<?php endif; ?>



</div>

<footer style="background:#800000; color:white; text-align:center; padding:15px; margin-top:40px;">
    &copy; 2026 EKSU Recruitment. All rights reserved.
</footer>

</body>
</html>
