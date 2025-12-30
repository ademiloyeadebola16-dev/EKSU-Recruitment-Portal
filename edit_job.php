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

/* ---------- DEFAULTS (IMPORTANT) ---------- */
$defaults = [
    'job_category' => 'Academic',
    'faculty' => '',
    'department' => '',
    'position' => '',
    'qualification_display' => '',
    'description' => '',
    'requirement_qualification' => '',
    'required_experience' => 0,
    'required_publications' => 0,
    'required_body' => '',
    'research_expected' => false,
    'custom_fields' => [],
    'is_active' => true,
    'deadline' => ''
];


$job = array_merge($defaults, $job);

if (!is_array($job['custom_fields'])) {
    $job['custom_fields'] = [];
}

/* ---------- SAVE UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jobs[$index]['job_category'] = $_POST['job_category'] ?? 'Academic';
    $jobs[$index]['faculty'] = trim($_POST['faculty'] ?? '');
    $jobs[$index]['department'] = trim($_POST['department'] ?? '');
    $jobs[$index]['position'] = trim($_POST['position'] ?? '');
    $jobs[$index]['qualification_display'] = trim($_POST['qualification_display'] ?? '');
    $jobs[$index]['description'] = trim($_POST['description'] ?? '');

    $jobs[$index]['requirement_qualification'] = trim($_POST['requirement_qualification'] ?? '');
    $jobs[$index]['required_experience'] = (int) ($_POST['required_experience'] ?? 0);
    $jobs[$index]['required_publications'] = (int) ($_POST['required_publications'] ?? 0);
    $jobs[$index]['required_body'] = trim($_POST['required_body'] ?? '');
    $jobs[$index]['research_expected'] = isset($_POST['research_expected']);

    $jobs[$index]['custom_fields'] = array_values(
        array_filter(
            array_map('trim', $_POST['custom_fields'] ?? [])
        )
    );
$jobs[$index]['is_active'] = isset($_POST['is_active']);

$jobs[$index]['deadline'] = !empty($_POST['deadline'])
    ? $_POST['deadline']
    : '';

    file_put_contents($jobs_file, json_encode($jobs, JSON_PRETTY_PRINT));

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
label input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

h3 {
    margin-top: 25px;
    color: #800000;
}

</style>
</head>

<body>

<div class="container">
<h2>Edit Job</h2>

<form method="POST">

    <label>Job Category</label>
    <select name="job_category" required>
        <option value="Academic" <?= $job['job_category']==="Academic"?"selected":"" ?>>Academic</option>
        <option value="Non-Academic" <?= $job['job_category']==="Non-Academic"?"selected":"" ?>>Non-Academic</option>
    </select>

    <label>Faculty</label>
    <input type="text" name="faculty" value="<?= htmlspecialchars($job['faculty']) ?>">

    <label>Department</label>
    <input type="text" name="department" value="<?= htmlspecialchars($job['department']) ?>">

    <label>Position</label>
    <input type="text" name="position" value="<?= htmlspecialchars($job['position']) ?>">

    <label>Qualification (Display)</label>
    <input type="text" name="qualification_display"
           value="<?= htmlspecialchars($job['qualification_display']) ?>">

    <label>Description</label>
    <textarea name="description" rows="4"><?= htmlspecialchars($job['description']) ?></textarea>
<h3>Job Status</h3>

<label>
    <input type="checkbox" name="is_active"
           <?= $job['is_active'] ? 'checked' : '' ?>>
    Job is Active
</label>

<label>Application Deadline</label>
<input type="datetime-local" name="deadline"
       value="<?= htmlspecialchars($job['deadline']) ?>">

    <h3>Admin Requirements</h3>

    <label>Required Academic Qualification</label>
    <input type="text" name="requirement_qualification"
           value="<?= htmlspecialchars($job['requirement_qualification']) ?>">

    <label>Required Experience (Years)</label>
    <input type="number" name="required_experience"
           value="<?= (int)$job['required_experience'] ?>">

    <label>Required Publications</label>
    <input type="number" name="required_publications"
           value="<?= (int)$job['required_publications'] ?>">

    <label>Required Professional Body</label>
    <input type="text" name="required_body"
           value="<?= htmlspecialchars($job['required_body']) ?>">

    <label>
        <input type="checkbox" name="research_expected"
               <?= $job['research_expected'] ? "checked" : "" ?>>
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
    const container = document.getElementById("fieldsContainer");
    const input = document.createElement("input");
    input.type = "text";
    input.name = "custom_fields[]";
    input.placeholder = "Custom field";
    container.appendChild(input);
};
</script>

</body>
</html>
