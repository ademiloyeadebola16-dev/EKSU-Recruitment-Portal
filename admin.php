<?php
session_start();

// Load admins
$admins_file = 'admins.json';
$admins = file_exists($admins_file) ? json_decode(file_get_contents($admins_file), true) : [];

// Verify current admin session
$current = null;
foreach ($admins as $a) {
    if ($a['email'] === ($_SESSION['admin'] ?? '')) {
        $current = $a;
        break;
    }
}

if (!$current || empty($current['approved'])) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?error=not_approved");
    exit();
}

$is_super = $current['is_super'] ?? false;

// Load jobs
$jobs_file = 'jobs.json';
$jobs = file_exists($jobs_file) ? json_decode(file_get_contents($jobs_file), true) : [];

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
        <span>Welcome, <?= htmlspecialchars($_SESSION['admin']) ?> 👋</span>
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
<h2>Admin Management (Super-Admin Only)</h2>
<table>
<tr>
<th>Name</th>
<th>Email</th>
<th>Status</th>
<th>Role</th>
<th>Actions</th>
</tr>
<?php foreach ($admins as $adm): ?>
<tr>
<td><?= htmlspecialchars($adm['name']) ?></td>
<td><?= htmlspecialchars($adm['email']) ?></td>
<td>
<?php if ($adm['approved']): ?><span class="badge approved">Approved</span>
<?php else: ?><span class="badge pending">Pending</span><?php endif; ?>
</td>
<td>
<?php if ($adm['is_super']): ?><span class="badge super">Super Admin</span>
<?php else: ?>Admin<?php endif; ?>
</td>
<td>
<?php if (!$adm['approved']): ?>
    <a href="approve_admin.php?id=<?= $adm['id'] ?>"><button class="approve-btn manage-btn">Approve</button></a>
    <form action="reject_admin.php" method="POST" style="display:inline;" onsubmit="return confirm('Reject this admin?');">
        <input type="hidden" name="id" value="<?= $adm['id'] ?>">
        <button type="submit" class="reject-btn manage-btn">Reject</button>
    </form>
<?php endif; ?>

<?php if ($adm['approved'] && !$adm['is_super']): ?>
    <a href="promote_admin.php?id=<?= $adm['id'] ?>"><button class="promote-btn manage-btn">Promote</button></a>
<?php endif; ?>

<?php if ($adm['approved'] && $adm['is_super'] && $adm['email'] !== $_SESSION['admin']): ?>
    <a href="demote_admin.php?id=<?= $adm['id'] ?>"><button class="demote-btn manage-btn">Demote</button></a>
<?php endif; ?>

<?php if ($adm['approved'] && !$adm['is_super'] && $adm['email'] !== $_SESSION['admin']): ?>
    <form action="delete_admin.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this admin?');">
        <input type="hidden" name="id" value="<?= $adm['id'] ?>">
        <button type="submit" class="delete-btn manage-btn">Delete</button>
    </form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<hr>
<?php endif; ?>

<h2>Create a New Job Posting</h2>
<form method="POST">
<input type="text" name="title" placeholder="Job Title" required>
<input type="text" name="faculty" placeholder="Faculty" required>
<input type="text" name="department" placeholder="Department" required>
<input type="text" name="position" placeholder="Position" required>
<input type="text" name="qualification" placeholder="Minimum Qualification" required>
<textarea name="description" rows="4" placeholder="Job Description" required></textarea>

<h3>Admin-only Requirements</h3>
<input type="text" name="requirement_qualification" placeholder="Academic Requirement" required>
<input type="number" name="requirement_experience" placeholder="Years of Experience" required>
<input type="number" name="requirement_publications" placeholder="Publications Required" required>
<input type="text" name="requirement_body" placeholder="Professional Body" required>

<button type="submit">Add Job</button>
</form>

<hr>
<h2>Existing Job Listings</h2>
<?php if(count($jobs)>0): ?>
<table>
<tr>
<th>Title</th>
<th>Faculty</th>
<th>Department</th>
<th>Position</th>
<th>Qualification</th>
<th>Actions</th>
</tr>
<?php foreach($jobs as $index=>$job): ?>
<tr>
<td><?= htmlspecialchars($job['title']) ?></td>
<td><?= htmlspecialchars($job['faculty']) ?></td>
<td><?= htmlspecialchars($job['department']) ?></td>
<td><?= htmlspecialchars($job['position']) ?></td>
<td><?= htmlspecialchars($job['qualification']) ?></td>
<td>
  <a href="edit_job.php?index=<?= $index ?>" class="manage-btn" style="background:#0066cc;">Edit</a>
  <form action="delete_job.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this job?');">
    <input type="hidden" name="id" value="<?= $index ?>">
    <button type="submit" class="delete-btn">Delete</button>
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
