<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$jobs_file = 'jobs.json';
$jobs = file_exists($jobs_file) ? json_decode(file_get_contents($jobs_file), true) : [];

$index = $_GET['index'] ?? null;
if ($index === null || !isset($jobs[$index])) {
    die("Job not found.");
}

$job = $jobs[$index];

// Ensure custom_fields exists
if (!isset($job['custom_fields']) || !is_array($job['custom_fields'])) {
    $job['custom_fields'] = [];
}

// Save updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Standard fields
    $jobs[$index]['title'] = trim($_POST['title']);
    $jobs[$index]['faculty'] = trim($_POST['faculty']);
    $jobs[$index]['department'] = trim($_POST['department']);
    $jobs[$index]['position'] = trim($_POST['position']);
    $jobs[$index]['qualification'] = trim($_POST['qualification']);
    $jobs[$index]['description'] = trim($_POST['description']);

    // Requirement fields
    $jobs[$index]['requirement_qualification'] = trim($_POST['requirement_qualification']);
    $jobs[$index]['required_experience'] = trim($_POST['required_experience']);
    $jobs[$index]['required_publications'] = trim($_POST['required_publications']);
    $jobs[$index]['required_body'] = trim($_POST['required_body']);

    // Custom fields
    $jobs[$index]['custom_fields'] = array_filter(array_map('trim', $_POST['custom_fields'] ?? []));

    file_put_contents($jobs_file, json_encode($jobs, JSON_PRETTY_PRINT));

    $_SESSION['message'] = "Job updated successfully!";
    header("Location: admin_jobs.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Job</title>
<style>
body { font-family: Arial, sans-serif; background:#f0f2f5; }
.container { max-width:700px; margin:40px auto; background:white; padding:25px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
input, textarea { width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:5px; }
button { padding:10px 20px; margin-top:10px; border:none; border-radius:5px; background:#800000; color:white; cursor:pointer; }
button:hover { background:#660000; }
.add-btn { background:#0066cc; }
.add-btn:hover { background:#004c99; }
.field-container { margin-bottom:10px; }
h2, h3 { color:#800000; }
a { color:white; text-decoration:none; }
nav { background:#800000; padding:15px 10%; display:flex; justify-content:space-between; }
</style>
</head>
<body>

<nav>
    <div style="color:white;font-size:20px;">Edit Job</div>
    <a href="admin_jobs.php" style="background:#008000;padding:8px 15px;border-radius:5px;">⬅ Back</a>
</nav>

<div class="container">

<h2>Edit Job: <?= htmlspecialchars($job['title']) ?></h2>

<form method="POST">

    <label>Job Title:</label>
    <input type="text" name="title" value="<?= htmlspecialchars($job['title']) ?>" required>

    <label>Faculty:</label>
    <input type="text" name="faculty" value="<?= htmlspecialchars($job['faculty']) ?>" required>

    <label>Department:</label>
    <input type="text" name="department" value="<?= htmlspecialchars($job['department']) ?>" required>

    <label>Position:</label>
    <input type="text" name="position" value="<?= htmlspecialchars($job['position']) ?>" required>

    <label>Minimum Qualification:</label>
    <input type="text" name="qualification" value="<?= htmlspecialchars($job['qualification']) ?>" required>

    <label>Job Description:</label>
    <textarea name="description" rows="4"><?= htmlspecialchars($job['description']) ?></textarea>

    <hr>
    <h3>Admin-Only Requirements</h3>

    <label>Required Academic Qualification:</label>
    <input type="text" name="requirement_qualification" value="<?= htmlspecialchars($job['requirement_qualification']) ?>">

    <label>Minimum Years of Experience:</label>
    <input type="number" name="required_experience" value="<?= htmlspecialchars($job['required_experience']) ?>">

    <label>Required Publications:</label>
    <input type="number" name="required_publications" value="<?= htmlspecialchars($job['required_publications']) ?>">

    <label>Professional Body Requirement:</label>
    <input type="text" name="required_body" value="<?= htmlspecialchars($job['required_body']) ?>">

    <hr>
    <h3>Custom Requirements</h3>

    <div id="fieldsContainer">
        <?php foreach ($job['custom_fields'] as $field): ?>
            <div class="field-container">
                <input type="text" name="custom_fields[]" value="<?= htmlspecialchars($field) ?>" placeholder="Custom requirement">
            </div>
        <?php endforeach; ?>
    </div>

    <button type="button" id="addFieldBtn" class="add-btn">+ Add More</button>

    <br><br>

    <button type="submit">Save Changes</button>

</form>
</div>

<script>
const container = document.getElementById("fieldsContainer");
document.getElementById("addFieldBtn").addEventListener("click", () => {
    const div = document.createElement("div");
    div.className = "field-container";
    div.innerHTML = '<input type="text" name="custom_fields[]" placeholder="Custom requirement">';
    container.appendChild(div);
});
</script>

</body>
</html>
