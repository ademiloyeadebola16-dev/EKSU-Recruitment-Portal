<?php
session_start();
if (!isset($_SESSION['applicant_email'])) {
    header("Location: applicant_login.php");
    exit();
}

$email = $_SESSION['applicant_email'];
$applications_file = 'applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

// Find all applications by this applicant
$userApplications = array_filter($applications, function($app) use ($email) {
    return isset($app['email']) && $app['email'] === $email;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applicant Dashboard</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f7fb; margin:0; }
nav { background:#800000; color:white; padding:15px 10%; display:flex; justify-content:space-between; align-items:center; }
.nav-container { display:flex; align-items:center; gap:15px; }
.nav-container img { width:70px; height:70px; border-radius:5px; }
.nav-text h1 { font-size:22px; margin:0; }
.nav-text h5 { margin:0; font-size:14px; font-weight:normal; color:#ddd; }
nav a { color:white; text-decoration:none; background:#cc0000; padding:8px 15px; border-radius:5px; }
.container { max-width:800px; margin:40px auto; background:white; padding:25px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
h2 { color:#800000; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:10px; text-align:left; }
th { background:#800000; color:white; }
.status { font-weight:bold; }
.status.Pending { color:orange; }
.status.UnderReview { color:blue; }
.status.Qualified { color:green; }
.status.NotQualified { color:red; }
.apply-btn { display:inline-block; background:#800000; color:white; padding:10px 20px; border:none; border-radius:5px; text-decoration:none; font-weight:bold; margin-top:15px; }
.apply-btn:hover { background:#660000; }
</style>
</head>
<body>

<!-- Header -->
<nav>
   <div class="nav-container">
      <img src="logo.jfif" alt="Site Logo">
      <div class="nav-text">
        <h1>Ekiti State University, Ado-Ekiti</h1>
        <h5>Job Recruitment Portal</h5>
      </div>
   </div>
</nav>

<nav>
  <span>Welcome, <?= htmlspecialchars($email) ?> 👋</span>
  <a href="index.php" style="background:white; color:#800000; padding:8px 15px; border-radius:5px; text-decoration:none; font-weight:bold; margin-left:10px;">🏠 Home</a>
  <a href="logout.php">Logout</a>
</nav>

<div class="container">
  <h2>My Applications</h2>

  <?php if (count($userApplications) > 0): ?>
    <table>
      <tr>
        <th>Job Title</th>
        <th>Department</th>
        <th>Status</th>
        <th>Remarks</th>
        <th>Description</th>
      </tr>
      <?php foreach ($userApplications as $app): ?>
        <?php 
            $fullName = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
            $status_visible = isset($app['status_visible']) && $app['status_visible'] === true;

            // Default to Under Review if not visible yet
            $status = $status_visible ? ($app['status'] ?? 'Pending') : 'Under Review';
            $reason = $status_visible ? ($app['reason'] ?? 'No remarks available.') : 'Under Review';
        ?>
        <tr>
          <td><?= htmlspecialchars($app['job_title'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($app['department'] ?? 'N/A') ?></td>
          <td class="status <?= htmlspecialchars($status) ?>">
              <?= htmlspecialchars($status) ?>
          </td>
          <td><?= nl2br(htmlspecialchars($reason)) ?></td>
          <td><?= htmlspecialchars($app['description'] ?? 'No description provided') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <a href="index.php" class="apply-btn">Apply for Another Job</a>

  <?php else: ?>
    <p>You haven’t applied for any jobs yet.</p>
    <a href="index.php" class="apply-btn">Apply Now</a>
  <?php endif; ?>
</div>

</body>
</html>
