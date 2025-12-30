<?php
session_start();

// AUTH CHECK (NEW SYSTEM)
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$currentAdmin = $_SESSION['admin'];
$is_super = ($currentAdmin['role'] === 'super');

// Load admins (for management UI)
$admins_file = 'admins.json';
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];


// Handle admin actions (super admin only)
if ($is_super && $_SERVER['REQUEST_METHOD'] === 'POST') {


// CREATE ADMIN
if (isset($_POST['create_admin'])) {
$email = strtolower(trim($_POST['email']));
$password = $_POST['password'];


foreach ($admins as $a) {
if ($a['email'] === $email) {
$_SESSION['message'] = 'Admin already exists';
header('Location: admin.php'); exit();
}
}


$admins[] = [
'id' => time(),
'email' => $email,
'password' => password_hash($password, PASSWORD_DEFAULT),
'role' => 'admin',
'created_at' => date('Y-m-d H:i:s')
];


file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));
$_SESSION['message'] = 'Admin created successfully';
header('Location: admin.php'); exit();
}


// DELETE ADMIN
if (isset($_POST['delete_admin'])) {
$id = $_POST['id'];
$admins = array_filter($admins, fn($a) => $a['id'] != $id || $a['role'] === 'super');
file_put_contents($admins_file, json_encode(array_values($admins), JSON_PRETTY_PRINT));
$_SESSION['message'] = 'Admin deleted';
header('Location: admin.php'); exit();
}


// RESET PASSWORD
if (isset($_POST['reset_password'])) {
foreach ($admins as &$a) {
if ($a['id'] == $_POST['id']) {
$a['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
break;
}
}
file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));
$_SESSION['message'] = 'Password reset successfully';
header('Location: admin.php'); exit();
}
}
// PROMOTE / DEMOTE / ENABLE / DISABLE ADMINS
if ($is_super && $_SERVER['REQUEST_METHOD'] === 'POST') {


// Promote to super admin
if (isset($_POST['promote_admin'])) {
foreach ($admins as &$a) {
if ($a['id'] == $_POST['id'] && $a['role'] === 'admin') {
$a['role'] = 'super';
$_SESSION['message'] = 'Admin promoted to Super Admin';
break;
}
}
}


// Demote super admin
if (isset($_POST['demote_admin'])) {
foreach ($admins as &$a) {
if ($a['id'] == $_POST['id'] && $a['role'] === 'super' && $a['email'] !== $currentAdmin['email']) {
$a['role'] = 'admin';
$_SESSION['message'] = 'Super Admin demoted';
break;
}
}
}


// Disable admin
if (isset($_POST['disable_admin'])) {
foreach ($admins as &$a) {
if ($a['id'] == $_POST['id'] && $a['role'] !== 'super') {
$a['status'] = 'disabled';
$_SESSION['message'] = 'Admin disabled';
break;
}
}
}


// Enable admin
if (isset($_POST['enable_admin'])) {
foreach ($admins as &$a) {
if ($a['id'] == $_POST['id']) {
$a['status'] = 'active';
$_SESSION['message'] = 'Admin enabled';
break;
}
}
}


file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT));
header('Location: admin.php'); exit();
}
// Load jobs
$jobs_file = 'jobs.json';
$jobs = file_exists($jobs_file) ? json_decode(file_get_contents($jobs_file), true) : [];
$updated = false;
$now = time();

foreach ($jobs as $i => $job) {
    if (!empty($job['deadline'])) {
        if (strtotime($job['deadline']) < $now && !empty($job['is_active'])) {
            $jobs[$i]['is_active'] = false; // auto-disable
            $updated = true;
        }
    }
}

if ($updated) {
    file_put_contents($jobs_file, json_encode(array_values($jobs), JSON_PRETTY_PRINT));
}

$isClosed = !empty($job['deadline']) && strtotime($job['deadline']) < time();
$isExpired = !empty($job['deadline']) && strtotime($job['deadline']) < time();
$isActive  = $job['is_active'] ?? true;

// Handle new job creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $jobs[] = [
        'title' => trim($_POST['title']),
        'faculty' => trim($_POST['faculty']),
        'department' => trim($_POST['department']),
        'position' => trim($_POST['position']),
        'qualification' => trim($_POST['qualification']),
        'description' => trim($_POST['description']),
        'requirement_qualification' => trim($_POST['requirement_qualification']),
        'requirement_experience' => trim($_POST['requirement_experience']),
        'requirement_publications' => trim($_POST['requirement_publications']),
        'requirement_body' => trim($_POST['requirement_body'])
    ];
    file_put_contents($jobs_file, json_encode($jobs, JSON_PRETTY_PRINT));
    $_SESSION['message'] = "Job added successfully!";
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
body {font-family: Arial, sans-serif; background:#f0f4f8; margin:0;}
nav {background:#800000;color:white;padding:15px;display:flex;justify-content:space-between;}
.container {max-width:900px;margin:40px auto;background:white;padding:25px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,0.1);}
input, textarea {width:100%;padding:10px;margin-bottom:15px;border:1px solid #ccc;border-radius:5px;}
button {background:#800000;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;}
button:hover {background:#660000;}
h2, h3 {color:#800000;}
table {width:100%;border-collapse:collapse;margin-top:20px;}
th, td {border:1px solid #ccc;padding:10px;text-align:left;}
th {background:#800000;color:white;}
.badge {padding:5px 8px;border-radius:4px;font-size:12px;color:white;}
.approved {background:green;}
.pending {background:orange;}
.super {background:purple;}
.manage-btn {padding:5px 10px;border:none;border-radius:5px;cursor:pointer;margin-right:3px;color:white;}
.promote-btn {background:#6600cc;}
.demote-btn {background:#993399;}
.approve-btn {background:#008000;}
.reject-btn {background:#cc0000;}
.delete-btn {background:#cc0000;}
.message {background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:20px;}
</style>
</head>
<body>
<nav>
   <div style="display:flex;align-items:center;gap:15px;">
      <img src="logo.jfif" alt="Site Logo" style="width:70px;height:70px;border-radius:5px;">
      <div>
        <h1 style="margin:0;">Ekiti State University, Ado-Ekiti</h1>
        <h5 style="margin:0;color:#ddd;">Job Recruitment Portal</h5>
       <span>Welcome, <?= htmlspecialchars($currentAdmin['email']) ?> 👋</span>
      </div>
   </div>
   <div>
      <a href="index.php" style="background:white;color:#800000;padding:8px 15px;border-radius:5px;text-decoration:none;font-weight:bold;">Home</a>
      <a href="add_job.php" style="background:#004080;color:white;padding:8px 15px;border-radius:5px;text-decoration:none;">Add Job</a>
      <a href="view_applications.php" style="background:#008000;color:white;padding:8px 15px;border-radius:5px;text-decoration:none;">View Applications</a>
      <a href="admin_logout.php" style="background:#cc0000;color:white;padding:8px 15px;border-radius:5px;text-decoration:none;">Logout</a>
   </div>
</nav>

<div class="container">

<?php if(!empty($_SESSION['message'])): ?>
  <div class="message"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<?php if ($is_super): ?>
<h2>Admin Management (Super Admin Only)</h2>


<table>
<tr>
<th>Email</th>
<th>Role</th>
<th>Status</th>
<th>Actions</th>
</tr>

<?php foreach ($admins as $adm): ?>
<tr>
<td><?= htmlspecialchars($adm['email']) ?></td>
<td>
<?php if ($adm['role'] === 'super'): ?>
<span class="badge super">Super Admin</span>
<?php else: ?>
<span class="badge approved">Admin</span>
<?php endif; ?>
</td>
<td>
<?php if (($adm['status'] ?? 'active') === 'disabled'): ?>
<span class="badge pending">Disabled</span>
<?php else: ?>
<span class="badge approved">Active</span>
<?php endif; ?>
</td>
<td>
<?php if ($adm['email'] !== $currentAdmin['email']): ?>


<!-- Promote / Demote -->
<?php if ($adm['role'] === 'admin'): ?>
<form method="post" style="display:inline;">
<input type="hidden" name="id" value="<?= $adm['id'] ?>">
<button class="promote-btn manage-btn" name="promote_admin">Promote</button>
</form>
<?php else: ?>
<form method="post" style="display:inline;">
<input type="hidden" name="id" value="<?= $adm['id'] ?>">
<button class="demote-btn manage-btn" name="demote_admin">Demote</button>
</form>
<?php endif; ?>


<!-- Disable / Enable -->
<?php if (($adm['status'] ?? 'active') === 'active'): ?>
<form method="post" style="display:inline;">
<input type="hidden" name="id" value="<?= $adm['id'] ?>">
<button class="delete-btn manage-btn" name="disable_admin">Disable</button>
</form>
<?php else: ?>
<form method="post" style="display:inline;">
<input type="hidden" name="id" value="<?= $adm['id'] ?>">
<button class="approve-btn manage-btn" name="enable_admin">Enable</button>
</form>
<?php endif; ?>


<?php else: ?>
<em>Current User</em>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>

<h3>Create New Admin</h3>
<form method="post">
<input type="email" name="email" placeholder="Admin Email" required>
<input type="password" name="password" placeholder="Temporary Password" required>
<button class="approve-btn" name="create_admin">Create Admin</button>
</form>
<?php endif; ?>
<hr>


<h2>Existing Job Listings</h2>

<?php if (count($jobs) > 0): ?>
<table>
<tr>
    <th>Job Category</th>
    <th>Faculty</th>
    <th>Department</th>
    <th>Position</th>
    <th>Qualification</th>
    <th>Job Status</th>
    <th>Action</th> 
</tr>

<?php foreach ($jobs as $index => $job): ?>
<?php
    $isExpired = !empty($job['deadline']) && strtotime($job['deadline']) < time();
    $isActive  = $job['is_active'] ?? true;
?>
<tr>
    <td><?= htmlspecialchars($job['category']) ?></td>
    <td><?= htmlspecialchars($job['faculty']) ?></td>
    <td><?= htmlspecialchars($job['department']) ?></td>
    <td><?= htmlspecialchars($job['position']) ?></td>
   <td><?= htmlspecialchars($job['qualification_display'] ?? $job['qualification'] ?? 'N/A') ?></td>
<td>
    <?php if (!empty($job['is_active'])): ?>
        <span style="color:green;font-weight:bold;">Active</span>
    <?php else: ?>
        <span style="color:red;font-weight:bold;">Inactive</span>
    <?php endif; ?>
</td>

    <td>
        <!-- STATUS BADGE -->
        <?php if ($isExpired): ?>
            <span class="badge pending">Closed</span>
        <?php elseif ($isActive): ?>
            <span class="badge approved">Active</span>
        <?php else: ?>
            <span class="badge pending">Disabled</span>
        <?php endif; ?>

        <br><br>

        <!-- ACTION BUTTONS -->
        <a href="edit_job.php?index=<?= $index ?>"
           class="manage-btn"
           style="background:#0066cc;">Edit</a>

        <a href="toggle_job.php?id=<?= $index ?>"
           class="manage-btn"
           style="background:#008000;"
           onclick="return confirm('Change job visibility?')">
           <?= $isActive ? 'Disable' : 'Enable' ?>
        </a>

        <form action="delete_job.php"
              method="POST"
              style="display:inline;"
              onsubmit="return confirm('Delete this job?');">
            <input type="hidden" name="id" value="<?= $index ?>">
            <button type="submit" class="delete-btn manage-btn">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>

</table>
<?php else: ?>
<p>No jobs found.</p>
<?php endif; ?>

</div>
</body>
</html>
