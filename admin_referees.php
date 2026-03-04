<?php
session_start();
require 'db.php';

// OPTIONAL: admin protection
// if (!isset($_SESSION['admin'])) {
//     header("Location: admin_login.php");
//     exit();
// }

// Fetch referees with applicant details
$stmt = $pdo->query("
    SELECT r.*, 
           a.first_name, 
           a.last_name, 
           a.applicant_number,
           a.job_title
    FROM referees r
    JOIN applications a ON r.application_id = a.id
    ORDER BY r.submitted_at DESC
");

$referees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Referee Submissions</title>
    <meta charset="UTF-8">
    <style>
body {font-family: Arial; background:#eef1f5;}
.container {
    max-width:400px;
    margin:80px auto;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
input {
    width:100%;
    padding:10px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:5px;
}
button {
    width:100%;
    padding:10px;
    background:#800000;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
button:hover {background:#800000;}
a {color:#800000;text-decoration:none;}

nav {
    background:#800000; 
    color:#fff;
    padding:15px 10%;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.nav-container {
    display:flex;
    align-items:center;
    gap:15px;
}
.nav-container img {
    width:70px;
    height:70px;
    border-radius:5px;
}
nav h1 {
    font-size:22px;
    margin:0;
}
.nav-text h5 {
    margin:0;
    font-size:14px;
    font-weight:normal;
    color:#ddd;
}
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }
        h2 {
            color: #800000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #800000;
            color: white;
        }
        .btn {
            padding: 6px 10px;
            background: #2e86de;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .pending {
            color: red;
            font-weight: bold;
        }
        .submitted {
            color: green;
            font-weight: bold;
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
    <div>
        <a href="admin.php"
           style="background:white; color:#004080; padding:8px 15px; border-radius:5px; font-weight:bold;">
           Home
        </a>
    </div>
</nav>
<h2>Referee Letters</h2>

<table>
    <tr>
        <th>Applicant No.</th>
        <th>Applicant Name</th>
        <th>Job Title</th>
        <th>Referee Name</th>
        <th>Email</th>
        <th>Occupation</th>
        <th>Status</th>
        <th>Submitted At</th>
        <th>Download</th>
    </tr>

    <?php foreach ($referees as $ref): ?>
        <tr>
            <td><?= htmlspecialchars($ref['applicant_number']) ?></td>
            <td><?= htmlspecialchars($ref['first_name'] . ' ' . $ref['last_name']) ?></td>
            <td><?= htmlspecialchars($ref['job_title']) ?></td>
            <td><?= htmlspecialchars($ref['name']) ?></td>
            <td><?= htmlspecialchars($ref['email']) ?></td>
            <td><?= htmlspecialchars($ref['occupation']) ?></td>

            <td>
                <?php if (!empty($ref['file_path'])): ?>
                    <span class="submitted">Submitted</span>
                <?php else: ?>
                    <span class="pending">Pending</span>
                <?php endif; ?>
            </td>

            <td>
                <?= $ref['submitted_at'] ? htmlspecialchars($ref['submitted_at']) : '-' ?>
            </td>

            <td>
                <?php if (!empty($ref['file_path'])): ?>
                    <a class="btn" href="<?= htmlspecialchars($ref['file_path']) ?>" target="_blank">
                        Download
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
