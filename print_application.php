<?php
session_start();

$applications_file = "applications.json";
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

$id = $_GET['id'] ?? null;
if ($id === null || !isset($applications[$id])) {
    die("Invalid application reference.");
}

$app = $applications[$id];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Print Application</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    padding: 20px;
}
.print-box {
    background: white;
    padding: 25px;
    max-width: 900px;
    margin: auto;
    border-radius: 6px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    color: #800000;
}
.section {
    margin-top: 25px;
}
.section h3 {
    color: #800000;
    border-bottom: 2px solid #800000;
    padding-bottom: 4px;
}

.label { font-weight: bold; }
.row { margin-bottom: 10px; }

#print-btn {
    background:#800000;
    color:white;
    padding:10px 20px;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

@media print {
    #print-btn {
        display: none;
    }
    body {
        background:white;
    }
}
</style>
</head>
<body>

<div class="print-box">
    <button id="print-btn" onclick="window.print()">🖨 Print</button>

    <h1>Application Summary</h1>

    <div class="section">
        <h3>Personal Information</h3>
        <div class="row"><span class="label">Full Name:</span> <?= htmlspecialchars($app['first_name']." ".$app['middle_name']." ".$app['last_name']) ?></div>
        <div class="row"><span class="label">Email:</span> <?= htmlspecialchars($app['email']) ?></div>
        <div class="row"><span class="label">Phone:</span> <?= htmlspecialchars($app['phone']) ?></div>
    </div>

    <div class="section">
        <h3>Job Applied For</h3>
        <div class="row"><span class="label">Job:</span> <?= htmlspecialchars($app['job_title']) ?></div>
        <div class="row"><span class="label">Department:</span> <?= htmlspecialchars($app['department']) ?></div>
        <div class="row"><span class="label">Qualification:</span> <?= htmlspecialchars($app['academic_qualification']) ?></div>
        <div class="row"><span class="label">Date:</span> <?= htmlspecialchars($app['date']) ?></div>
    </div>

    <?php if (!empty($app['custom_fields'])): ?>
    <div class="section">
        <h3>Additional Requirements</h3>

        <?php foreach ($app['custom_fields'] as $entry): ?>
            <div class="row">
                <span class="label"><?= htmlspecialchars($entry['label']) ?>:</span><br>
                <strong>Response:</strong> <?= htmlspecialchars($entry['text'] ?: "No text provided") ?><br>
                <?php if (!empty($entry['file'])): ?>
                    <strong>File:</strong> <a href="uploads/<?= htmlspecialchars($entry['file']) ?>" target="_blank">View Attachment</a>
                <?php else: ?>
                    <strong>File:</strong> None uploaded
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
