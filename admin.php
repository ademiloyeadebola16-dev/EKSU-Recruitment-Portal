<?php
session_start();
require 'db.php';
require_once 'admin_log_functions.php';



// AUTH CHECK (NEW SYSTEM)
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// 🔒 REVALIDATE ADMIN FROM DATABASE
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['admin']['id']]);
$currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

// Admin no longer exists or deleted
if (!$currentAdmin || $currentAdmin['status'] === 'deleted') {
    session_destroy();
    header("Location: admin_login.php?error=account_deleted");
    exit();
}

// Disabled admin
if ($currentAdmin['status'] === 'disabled') {
    session_destroy();
    header("Location: admin_login.php?error=account_disabled");
    exit();
}

$is_super = (strtolower($currentAdmin['role']) === 'super_admin');
$_SESSION['admin'] = $currentAdmin; // refresh session


// Load admins (for management UI)
$stmt = $pdo->query("
    SELECT * FROM admins 
    WHERE status != 'deleted' 
    ORDER BY created_at ASC
");

$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Handle admin actions (super admin only)
if ($is_super && $_SERVER['REQUEST_METHOD'] === 'POST') {


// CREATE ADMIN
if (isset($_POST['create_admin'])) {
$email = strtolower(trim($_POST['email']));
$password = $_POST['password'];


$stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetchColumn() > 0) {
    $_SESSION['message'] = 'Admin already exists';
    header('Location: admin.php'); exit();
}
$stmt = $pdo->prepare("
    INSERT INTO admins (email, password, role, status, created_at)
    VALUES (?, ?, 'admin', 'active', NOW())
");
$stmt->execute([
    $email,
    password_hash($password, PASSWORD_DEFAULT)
]);

log_admin_activity(
    $pdo,
    'Created admin',
    $email
);


$_SESSION['message'] = 'Admin created successfully';
header('Location: admin.php'); exit();
}


// DELETE ADMIN (SOFT DELETE)
if (isset($_POST['delete_admin']) && $is_super) {

    $stmt = $pdo->prepare("
        SELECT email FROM admins WHERE id = ?
    ");
    $stmt->execute([$_POST['id']]);
    $target = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        DELETE FROM admins
        WHERE id = ?
          AND role != 'super_admin'
    ");
    $stmt->execute([$_POST['id']]);

    log_admin_activity($pdo, 'Deleted admin', $target);

    $_SESSION['message'] = 'Admin deleted successfully';
    header('Location: admin.php');
    exit();
}


//Reset Password
if (isset($_POST['reset_password'])) {

    $adminId = (int)$_POST['id'];
    $newPass = trim($_POST['new_password']);

    if ($newPass === '') {
        $_SESSION['message'] = 'Password cannot be empty';
        header('Location: admin.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $target = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        UPDATE admins
        SET password = ?
        WHERE id = ?
          AND role != 'super_admin'
    ");

    $stmt->execute([
        password_hash($newPass, PASSWORD_DEFAULT),
        $adminId
    ]);

    log_admin_activity($pdo, 'Reset admin password', $target);

    $_SESSION['message'] = 'Password reset successfully';
    header('Location: admin.php');
    exit();
}

// PROMOTE / DEMOTE / ENABLE / DISABLE ADMINS

// Promote to super admin
if (isset($_POST['promote_admin'])) {

    $adminId = (int)$_POST['id'];

    // Prevent self-promotion (optional but recommended)
    if ($adminId === (int)$currentAdmin['id']) {
        $_SESSION['message'] = 'You cannot promote yourself';
        header('Location: admin.php');
        exit();
    }

    // Fetch target admin email for logging
    $stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $targetEmail = $stmt->fetchColumn();

    if (!$targetEmail) {
        $_SESSION['message'] = 'Admin not found';
        header('Location: admin.php');
        exit();
    }

    // Promote admin → super_admin
    $stmt = $pdo->prepare("
        UPDATE admins
        SET role = 'super_admin'
        WHERE id = ?
          AND role = 'admin'
    ");
    $stmt->execute([$adminId]);

    if ($stmt->rowCount() > 0) {
        log_admin_activity(
            $pdo,
            'Promoted admin to Super Admin',
            $targetEmail
        );
        $_SESSION['message'] = 'Admin promoted to Super Admin';
    } else {
        $_SESSION['message'] = 'Promotion not allowed';
    }

    header('Location: admin.php');
    exit();
}




// Demote super admin
if (isset($_POST['demote_admin'])) {

    $adminId = (int)$_POST['id'];

    // Prevent self-demotion
    if ($adminId === (int)$currentAdmin['id']) {
        $_SESSION['message'] = 'You cannot demote yourself';
        header('Location: admin.php');
        exit();
    }

    // Get target admin email for logging
    $stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $targetEmail = $stmt->fetchColumn();

    if (!$targetEmail) {
        $_SESSION['message'] = 'Admin not found';
        header('Location: admin.php');
        exit();
    }

    // Demote super admin → admin
    $stmt = $pdo->prepare("
        UPDATE admins
        SET role = 'admin'
        WHERE id = ?
          AND role = 'super_admin'
    ");
    $stmt->execute([$adminId]);

    if ($stmt->rowCount() > 0) {
        log_admin_activity(
            $pdo,
            'Demoted super admin',
            $targetEmail
        );
        $_SESSION['message'] = 'Admin demoted to regular admin';
    } else {
        $_SESSION['message'] = 'Action not allowed';
    }

    header('Location: admin.php');
    exit();
}



// Disable admin
if (isset($_POST['disable_admin'])) {

    $adminId = (int)$_POST['id'];

    // Get target admin email for logging
    $stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $targetEmail = $stmt->fetchColumn();

    // Prevent disabling super admins
    $stmt = $pdo->prepare("
        UPDATE admins
        SET status = 'disabled'
        WHERE id = ?
          AND role != 'super_admin'
    ");
    $stmt->execute([$adminId]);

    if ($stmt->rowCount() > 0) {
        log_admin_activity(
            $pdo,
            'Disabled admin',
            $targetEmail
        );
        $_SESSION['message'] = 'Admin disabled successfully';
    } else {
        $_SESSION['message'] = 'Action not allowed';
    }

    header('Location: admin.php');
    exit();
}



if (isset($_POST['enable_admin'])) {

    $adminId = (int)$_POST['id'];

    // Get target admin email for logging
    $stmt = $pdo->prepare("SELECT email FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $targetEmail = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        UPDATE admins
        SET status = 'active'
        WHERE id = ?
    ");
    $stmt->execute([$adminId]);

    if ($stmt->rowCount() > 0) {
        log_admin_activity(
            $pdo,
            'Enabled admin',
            $targetEmail
        );
        $_SESSION['message'] = 'Admin enabled successfully';
    } else {
        $_SESSION['message'] = 'Action failed';
    }

    header('Location: admin.php');
    exit();
}


header('Location: admin.php'); exit();

}
// Load jobs
$stmt = $pdo->query("
    SELECT *
    FROM jobs
    ORDER BY created_at DESC
");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isExpired = !empty($job['deadline']) && strtotime($job['deadline']) < time();
$isActive  = $job['is_active'] ?? true;


// Handle new job creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $jobs[] = [
    'category' => trim($_POST['category'] ?? 'General'),
    'title' => trim($_POST['title']),
    'faculty' => trim($_POST['faculty']),
    'department' => trim($_POST['department']),
    'position' => trim($_POST['position']),
    'qualification' => trim($_POST['qualification']),
    'qualification_display' => trim($_POST['qualification']),
    'description' => trim($_POST['description']),
    'deadline' => trim($_POST['deadline'] ?? ''),
    'is_active' => true,

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
nav {
    background:#800000;
    color:white;
    padding:10px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:relative;
}

.nav-left {
    display:flex;
    align-items:center;
    gap:12px;
}

.logo {
    width:50px;
    height:50px;
    border-radius:5px;
}

.nav-left h1 {
    margin:0;
    font-size:20px;
}

.nav-left h5 {
    margin:0;
    font-size:13px;
    font-weight:normal;
}

.nav-left span {
    font-size:13px;
    display:block;
    margin-top:3px;
}

.nav-links {
    display:none;
    flex-direction:column;
    position:absolute;
    right:20px;
    top:70px;
    background:#800000;
    padding:12px;
    border-radius:8px;
    width:200px;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
}

.nav-links a {
    background:white;
    color:#800000;
    padding:8px 12px;
    border-radius:5px;
    text-decoration:none;
    font-weight:bold;
    margin-bottom:8px;
    text-align:center;
}

.nav-links a.logout {
    background:#cc0000;
    color:white;
}

.nav-links.show {
    display:flex;
}

.burger {
    font-size:26px;
    cursor:pointer;
    display:block;
}
</style>
</head>
<body>
<nav>
   <div class="nav-left">
      <img src="logo.jfif" alt="Site Logo" class="logo">
      <div>
        <h1>Ekiti State University, Ado-Ekiti</h1>
        <h5>Recruitment Portal</h5>
        <span>Welcome, <?= htmlspecialchars($currentAdmin['email']) ?> 👋</span>
      </div>
   </div>

   <!-- Burger Button -->
   <div class="burger" onclick="toggleMenu()">☰</div>

   <!-- Menu -->
   <div class="nav-links" id="navLinks">
      <a href="index.php">Home</a>
      <a href="add_job.php">Add Job</a>
      <a href="view_applications.php">View Applications</a>
      <a href="admin_referees.php">Referee Letters</a>
      <a href="admin_logout.php" class="logout">Logout</a>
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
<?php if ($adm['role'] === 'super_admin'): ?>
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

<?php if (
    $is_super &&
    $adm['email'] !== $currentAdmin['email'] &&
    $adm['role'] !== 'super_admin'
): ?>
<form method="post" style="display:inline;"
      onsubmit="return confirm('Delete this admin permanently?');">
    <input type="hidden" name="id" value="<?= $adm['id'] ?>">
    <button class="delete-btn manage-btn" name="delete_admin">
        Delete
    </button>
</form>
<?php endif; ?>


    <!-- RESET PASSWORD (Super Admin Only) -->
<form method="post" style="display:inline-flex; gap:6px; align-items:center;"
      onsubmit="return confirm('Reset password for this admin?');">

    <input type="hidden" name="id" value="<?= $adm['id'] ?>">

    <input type="password"
           name="new_password"
           placeholder="New password"
           required
           style="padding:6px; width:140px;">

    <button type="submit"
            class="manage-btn"
            style="background:#004080;"
            name="reset_password">
        Reset
    </button>
</form>



<?php else: ?>
<em>Current User</em>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php if ($is_super): ?>
<hr>

<h3>System Monitoring</h3>

<a href="admin_logs.php"
   style="
     display:inline-block;
     margin-top:10px;
     background:#800000;
     color:white;
     padding:10px 18px;
     border-radius:6px;
     text-decoration:none;
     font-weight:bold;
   ">
   View Admin Activity Logs
</a>
<?php endif; ?>

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

<?php foreach ($jobs as $job): ?>
<?php
    $isExpired = !empty($job['deadline']) && strtotime($job['deadline']) < time();
$isActive  = (int)$job['is_active'] === 1;

?>
<tr>
    <td><?= htmlspecialchars($job['category']) ?></td>
<td><?= htmlspecialchars($job['faculty'] ?: '---') ?></td>
<td><?= htmlspecialchars($job['department']) ?></td>
<td><?= htmlspecialchars($job['position']) ?></td>
<td><?= htmlspecialchars($job['qualification']) ?></td>
<td>
    <?= $isActive ? '<span style="color:green">Active</span>' : '<span style="color:red">Inactive</span>' ?>
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
        <a href="edit_job.php?id=<?= $job['id'] ?>">Edit</a>

<a href="toggle_job.php?id=<?= $job['id'] ?>"
   onclick="return confirm('Change job visibility?')">
   <?= $isActive ? 'Disable' : 'Enable' ?>
</a>

<form action="delete_job.php" method="POST">
    <input type="hidden" name="id" value="<?= $job['id'] ?>">
    <button type="submit">Delete</button>
</form>

    </td>
</tr>
<?php endforeach; ?>

</table>
<?php else: ?>
<p>No jobs found.</p>
<?php endif; ?>

</div>
<script>
function toggleMenu() {
    document.getElementById("navLinks").classList.toggle("show");
}
</script>

</body>
</html>
