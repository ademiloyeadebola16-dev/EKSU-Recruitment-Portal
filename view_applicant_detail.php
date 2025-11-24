<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$index = $_GET['index'] ?? null;
if ($index === null) {
    die("Invalid request.");
}

$applications_file = 'applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

if (!isset($applications[$index])) {
    die("Application not found.");
}

$app = $applications[$index];
$status_visible = $app['status_visible'] ?? false;
$status = ucfirst(strtolower($app['status'] ?? 'Pending'));
$reason = trim($app['reason'] ?? 'No remarks available.');
$fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));

// Qualification & Professional Body Matching
$job_requirements_file = 'job_requirements.json';
$requirements = file_exists($job_requirements_file) ? json_decode(file_get_contents($job_requirements_file), true) : [];

$qualification_match = 0;
$professional_match = 0;

if (!empty($requirements) && isset($app['job_title'])) {
    foreach ($requirements as $req) {
        if (strcasecmp($req['title'], $app['job_title']) == 0) {
            similar_text(strtolower($app['qualification'] ?? ''), strtolower($req['qualification'] ?? ''), $qualification_match);
            similar_text(strtolower($app['professional_body'] ?? ''), strtolower($req['professional_body'] ?? ''), $professional_match);
            break;
        }
    }
}

$qualification_match = round($qualification_match, 1);
$professional_match = round($professional_match, 1);

// Custom Requirements (dynamic)
$custom_requirements = $app['custom_requirements'] ?? []; // array of ['label'=>'Requirement', 'response'=>'Answer']
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applicant Details</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f0f4f8;
    margin: 0;
}
.container {
    max-width: 900px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
h2 { color: #800000; border-bottom: 2px solid #800000; padding-bottom: 5px; }
p { line-height: 1.7; margin: 10px 0; }
strong { color: #800000; }
a.button {
    display: inline-block;
    background: #800000;
    color: #fff;
    padding: 10px 18px;
    border-radius: 5px;
    text-decoration: none;
}
a.button:hover { background: #600000; }
button.print-btn {
    background:#004080;
    color:white;
    padding:10px 20px;
    border:none;
    border-radius:5px;
    cursor:pointer;
    margin-top:20px;
}
button.print-btn:hover { background:#00264d; }
nav {
    background: #800000; 
    color: white;
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
.nav-text h1 { font-size: 22px; margin: 0; }
.nav-text h5 { margin: 0; font-size: 14px; font-weight: normal; color: #ddd; }
footer {
    background: #800000;
    color: white;
    text-align: center;
    padding: 20px;
    margin-top: 40px;
}
.status-box {
    border-left: 6px solid #800000;
    background: #f9f9f9;
    padding: 15px 20px;
    border-radius: 8px;
    margin: 25px 0;
}
.status-box h3 { margin-top: 0; color: #800000; }
.status-qualified { color: green; font-weight: bold; }
.status-notqualified { color: red; font-weight: bold; }
.status-disqualified { color: orange; font-weight: bold; }
.status-pending { color: gray; font-weight: bold; }

/* PRINT STYLES */
@media print {
    body { background: white; }
    nav, .print-btn { display: none !important; }
    .container { box-shadow: none; margin: 0; border-radius: 0; padding: 0; }
    .print-header { display: block; text-align: center; margin-bottom: 20px; }
    .print-header img { width: 100px; height: 100px; }
    .print-header h1 { margin: 10px 0 0 0; font-size: 24px; }
    .print-header h3 { margin: 5px 0 0 0; font-size: 18px; color: #800000; }
}
.print-header { display: none; }

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
        <a href="view_applications.php" class="button" style="background:#008000;">⬅ Back</a>
        <a href="admin_logout.php" class="button" style="background:#cc0000;">Logout</a>
    </div>
</nav>

<div class="print-header">
    <h3>Applicant: <?= htmlspecialchars($fullName) ?></h3>
</div>

<div class="container">
  <h2>Applicant Details</h2>

  <p><strong>Status Visibility:</strong>
     <?= $status_visible ? '<span style="color:green;font-weight:bold;">Visible to Applicant</span>' 
                         : '<span style="color:red;font-weight:bold;">Hidden from Applicant</span>' ?>
  </p>

  <form method="post" action="toggle_status_visibility.php">
    <input type="hidden" name="index" value="<?= $index ?>">
    <input type="hidden" name="action" value="<?= $status_visible ? 'hide' : 'show' ?>">
    <button type="submit"
            style="padding:10px 15px;background:#004080;color:white;border:none;border-radius:5px;cursor:pointer;">
        <?= $status_visible ? 'Hide Status from Applicant' : 'Make Status Visible to Applicant' ?>
    </button>
</form>

<hr><br>

<p><strong>Full Name:</strong> <?= htmlspecialchars($fullName) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($app['email'] ?? '') ?></p>
<p><strong>Phone:</strong> <?= htmlspecialchars($app['phone'] ?? '') ?></p>
<p><strong>Date of Birth:</strong> <?= htmlspecialchars($app['dob'] ?? '') ?></p>
<p><strong>Gender:</strong> <?= htmlspecialchars($app['gender'] ?? '') ?></p>
<p><strong>Nationality:</strong> <?= htmlspecialchars($app['nationality'] ?? '') ?></p>
<p><strong>State:</strong> <?= htmlspecialchars($app['state'] ?? '') ?></p>
<p><strong>LGA:</strong> <?= htmlspecialchars($app['lga'] ?? '') ?></p>
<p><strong>Place of Birth:</strong> <?= htmlspecialchars($app['pob'] ?? '') ?></p>
<p><strong>Job Applied For:</strong> <?= htmlspecialchars($app['job_title'] ?? '') ?></p>
<p><strong>Academic Qualification:</strong> <?= htmlspecialchars($app['qualification'] ?? '') ?></p>
<p><strong>Professional Body Registered With:</strong> <?= htmlspecialchars($app['professional_body'] ?? '') ?></p>
<p><strong>Years of Experience:</strong> <?= htmlspecialchars($app['experience_years'] ?? '') ?></p>
<p><strong>Number of Publications:</strong> <?= htmlspecialchars($app['publications'] ?? '') ?></p>

<div class="status-box">
    <h3>Qualification Review Summary</h3>
    <p><strong>Status:</strong> <span class="status-<?= strtolower($status) ?>"><?= htmlspecialchars($status) ?></span></p>
    <p><strong>Remarks:</strong><br><?= nl2br(htmlspecialchars($reason)) ?></p>
    <hr>
    <p><strong>Qualification Match:</strong> <?= $qualification_match ?>%</p>
    <p><strong>Professional Body Match:</strong> <?= $professional_match ?>% (minimum 80% for consideration)</p>
</div>

<!-- Custom Requirements -->
 <?php if ($job && !empty($job['custom_fields'])): ?>
        <h3>Custom Requirements for This Job</h3>
        <table class="table">
            <tr><th>Requirement</th><th>Applicant Response</th></tr>

            <?php foreach ($job['custom_fields'] as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req) ?></td>
                    <td>
                        <?= htmlspecialchars($app['custom_answers'][$req] ?? 'Not Provided') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

<!-- Referees -->
<h3 style="color:#800000;">Referees</h3>
<?php if (!empty($app['referees']) && is_array($app['referees'])): ?>
    <?php foreach ($app['referees'] as $i => $ref): ?>
        <p>
            <strong>Referee <?= $i + 1 ?>:</strong> <?= htmlspecialchars($ref['name'] ?? '') ?>
            (<?= htmlspecialchars($ref['occupation'] ?? '') ?>) -
            <?= htmlspecialchars($ref['email'] ?? '') ?>,
            <?= htmlspecialchars($ref['phone'] ?? '') ?>
        </p>
    <?php endforeach; ?>
<?php else: ?>
    <p>No referees provided.</p>
<?php endif; ?>

<?php if (!empty($app['cv'])): ?>
  <p><strong>CV:</strong> <a href="<?= htmlspecialchars($app['cv']) ?>" target="_blank">View CV</a></p>
<?php endif; ?>

<button class="print-btn" onclick="window.print()">Print</button>
</div>

<footer>
    <p>&copy; 2025 EKSU Recruitment. All rights reserved.</p>
</footer>

</body>
</html>
