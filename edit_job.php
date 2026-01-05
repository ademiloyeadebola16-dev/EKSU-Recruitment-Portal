<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

/* ------------------ GET JOB ID ------------------ */
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    die("Job not found.");
}

/* ------------------ NORMALIZE DATA ------------------ */
$job['custom_fields'] = $job['custom_fields']
    ? json_decode($job['custom_fields'], true)
    : [];

if (!is_array($job['custom_fields'])) {
    $job['custom_fields'] = [];
}

/* ------------------ UPDATE JOB ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category = $_POST['category'] ?? 'Academic';
    $faculty = trim($_POST['faculty'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $requirement_qualification = trim($_POST['requirement_qualification'] ?? '');
    $requirement_experience = (int)($_POST['requirement_experience'] ?? 0);
    $requirement_publications = (int)($_POST['requirement_publications'] ?? 0);
    $requirement_body = trim($_POST['requirement_body'] ?? '');
    $research_expected = isset($_POST['research_expected']) ? 1 : 0;

    $custom_fields = array_values(
        array_filter(array_map('trim', $_POST['custom_fields'] ?? []))
    );
    $custom_fields_json = json_encode($custom_fields);

    $deadline = !empty($_POST['deadline'])
        ? date('Y-m-d H:i:s', strtotime($_POST['deadline']))
        : null;

    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("
        UPDATE jobs SET
            category = ?,
            faculty = ?,
            department = ?,
            position = ?,
            qualification = ?,
            description = ?,
            requirement_qualification = ?,
            requirement_experience = ?,
            requirement_publications = ?,
            requirement_body = ?,
            research_expected = ?,
            custom_fields = ?,
            deadline = ?,
            is_active = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $category,
        $faculty,
        $department,
        $position,
        $qualification,
        $description,
        $requirement_qualification,
        $requirement_experience,
        $requirement_publications,
        $requirement_body,
        $research_expected,
        $custom_fields_json,
        $deadline,
        $is_active,
        $jobId
    ]);

    $_SESSION['message'] = "Job updated successfully!";
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit Job</title>
<style>
body { font-family: Arial; background:#f0f2f5; }
.container { max-width:750px; margin:40px auto; background:white; padding:25px; border-radius:10px; }
input, select, textarea { width:100%; padding:10px; margin:10px 0; }
button { padding:10px 20px; border:none; background:#800000; color:white; cursor:pointer; }
.add-btn { background:#0066cc; margin-bottom:10px; }
label input[type="checkbox"] { width:auto; margin-right:8px; }
h3 { margin-top:25px; color:#800000; }
</style>
</head>

<body>

<div class="container">
<h2>Edit Job</h2>

<form method="POST">

<label>Job Category</label>
<select name="category" required>
    <option value="Academic" <?= $job['category']==='Academic'?'selected':'' ?>>Academic</option>
    <option value="Non-Academic" <?= $job['category']==='Non-Academic'?'selected':'' ?>>Non-Academic</option>
</select>

<label>Faculty</label>
<input type="text" name="faculty" value="<?= htmlspecialchars($job['faculty']) ?>">

<label>Department</label>
<input type="text" name="department" value="<?= htmlspecialchars($job['department']) ?>">

<label>Position</label>
<input type="text" name="position" value="<?= htmlspecialchars($job['position']) ?>">

<label>Qualification</label>
<input type="text" name="qualification" value="<?= htmlspecialchars($job['qualification']) ?>">

<label>Description</label>
<textarea name="description" rows="4"><?= htmlspecialchars($job['description']) ?></textarea>

<h3>Job Status</h3>
<label>
    <input type="checkbox" name="is_active" <?= $job['is_active'] ? 'checked' : '' ?>>
    Job is Active
</label>

<label>Application Deadline</label>
<input type="datetime-local" name="deadline"
       value="<?= $job['deadline'] ? date('Y-m-d\TH:i', strtotime($job['deadline'])) : '' ?>">

<h3>Admin Requirements</h3>

<label>Required Academic Qualification</label>
<input type="text" name="requirement_qualification"
       value="<?= htmlspecialchars($job['requirement_qualification']) ?>">

<label>Required Experience (Years)</label>
<input type="number" name="requirement_experience"
       value="<?= (int)$job['requirement_experience'] ?>">

<label>Required Publications</label>
<input type="number" name="requirement_publications"
       value="<?= (int)$job['requirement_publications'] ?>">

<label>Required Professional Body</label>
<input type="text" name="requirement_body"
       value="<?= htmlspecialchars($job['requirement_body']) ?>">

<label>
    <input type="checkbox" name="research_expected"
           <?= $job['research_expected'] ? 'checked' : '' ?>>
    Research Expected?
</label>

<h3>Custom Fields</h3>

<div id="fieldsContainer">
<?php foreach ($job['custom_fields'] as $field): ?>
    <input type="text" name="custom_fields[]" value="<?= htmlspecialchars($field) ?>">
<?php endforeach; ?>
</div>

<button type="button" class="add-btn" id="addFieldBtn">+ Add More</button>

<br><br>
<button type="submit">Save Changes</button>

</form>
</div>

<script>
document.getElementById("addFieldBtn").onclick = function () {
    const c = document.getElementById("fieldsContainer");
    const i = document.createElement("input");
    i.type = "text";
    i.name = "custom_fields[]";
    i.placeholder = "Custom field";
    c.appendChild(i);
};
</script>

</body>
</html>
