<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin']['role'] !== 'super_admin') {
    header("Location: admin.php");
    exit();
}

/* ✅ HANDLE DELETE FIRST */
if (isset($_POST['delete_log_id'])) {
    $logId = (int) $_POST['delete_log_id'];

    $deleteStmt = $pdo->prepare(
        "DELETE FROM admin_activity_logs WHERE id = :id"
    );
    $deleteStmt->execute(['id' => $logId]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

/* ✅ THEN FETCH LOGS */
$stmt = $pdo->query("
    SELECT *
    FROM admin_activity_logs
    ORDER BY created_at DESC
    LIMIT 500
");

// ---- FILTER INPUTS ----
$adminFilter  = $_GET['admin_email'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$targetFilter = $_GET['target'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

// ---- BUILD QUERY DYNAMICALLY ----
$sql = "SELECT * FROM admin_activity_logs WHERE 1";
$params = [];

if ($adminFilter !== '') {
    $sql .= " AND admin_email LIKE :admin_email";
    $params['admin_email'] = "%$adminFilter%";
}

if ($actionFilter !== '') {
    $sql .= " AND action LIKE :action";
    $params['action'] = "%$actionFilter%";
}

if ($targetFilter !== '') {
    $sql .= " AND target LIKE :target";
    $params['target'] = "%$targetFilter%";
}
if ($dateFrom !== '') {
    $sql .= " AND DATE(created_at) >= :date_from";
    $params['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $sql .= " AND DATE(created_at) <= :date_to";
    $params['date_to'] = $dateTo;
}


$sql .= " ORDER BY created_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Activity Logs</title>

    <!-- YOUR THEME CSS GOES HERE -->
    <!-- Example -->
    <link rel="stylesheet" href="assets/css/admin.css">
<style>
body {
   font-family: 'Times New Roman', Times, serif;
    background: #f0f4f8;
    margin: 0;
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


/* === MAIN CONTENT === */
.container {
    max-width: 1000px;
    margin: 40px auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
h2 {
    color: #800000;
    text-align: center;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border: 1px solid #ccc;
    padding: 12px;
    text-align: left;
}
th {
    background: #800000;
    color: white;
}
    </style>
</head>

<body>
<nav>
   <div class="nav-container">
      <img src="logo.jfif" alt="Site Logo">
      <div class="nav-text">
        <h1>Ekiti State University, Ado-Ekiti</h1>
        <h5>Recruitment Portal</h5>
      </div>
    </div>
<a href="admin.php" class="btn" style="background:#008000;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;margin-right:6px;">Home</a>
    </nav>
<div class="wrapper">
    <div class="card">
            <h2>Admin Activity Logs</h2>
<form method="GET" style="margin-bottom:15px;display:flex;gap:10px;flex-wrap:wrap;">
    <input type="text" name="admin_email" placeholder="Admin Email"
           value="<?= htmlspecialchars($adminFilter) ?>">

    <input type="text" name="action" placeholder="Action"
           value="<?= htmlspecialchars($actionFilter) ?>">

    <input type="text" name="target" placeholder="Target"
           value="<?= htmlspecialchars($targetFilter) ?>">

    <input type="date" name="date_from"
           value="<?= htmlspecialchars($dateFrom) ?>">

    <input type="date" name="date_to"
           value="<?= htmlspecialchars($dateTo) ?>">

    <button type="submit"
        style="background:#800000;color:#fff;border:none;
               padding:6px 12px;border-radius:5px;">
        Filter
    </button>

    <a href="<?= $_SERVER['PHP_SELF'] ?>"
       style="background:#555;color:#fff;padding:6px 12px;
              border-radius:5px;text-decoration:none;">
        Reset
    </a>
</form>


            <table>
                <thead>
<tr>
    <th>S/N</th>
    <th>Date</th>
    <th>Admin</th>
    <th>Action</th>
    <th>Target</th>
    <th>IP</th>
    <th>Delete</th>
</tr>
</thead>


               <tbody>
<?php $sn = 1; ?>
<?php foreach ($logs as $log): ?>
<tr>
    <td><?= $sn++ ?></td>
    <td><?= htmlspecialchars($log['created_at']) ?></td>
    <td><?= htmlspecialchars($log['admin_email']) ?></td>
    <td><?= htmlspecialchars($log['action']) ?></td>
    <td><?= htmlspecialchars($log['target']) ?></td>
    <td><?= htmlspecialchars($log['ip_address']) ?></td>
    <td>
        <form method="POST" onsubmit="return confirm('Delete this activity log?');">
            <input type="hidden" name="delete_log_id" value="<?= $log['id'] ?>">
            <button type="submit"
                style="background:#c0392b;color:#fff;border:none;
                       padding:6px 10px;border-radius:5px;cursor:pointer;">
                Delete
            </button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>

            </table>
        </div>

    </div>
</div>
</body>
</html>
