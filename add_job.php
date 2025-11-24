<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $faculty = trim($_POST['faculty']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $qualification = trim($_POST['qualification']);
    $description = trim($_POST['description']);

    // Admin-only requirement fields
    $requirement_qualification = trim($_POST['requirement_qualification']);
    $required_experience = trim($_POST['required_experience']);
    $required_publications = trim($_POST['required_publications']);
    $required_body = trim($_POST['required_body']);

    // NEW — Custom Requirements
    $custom_fields = $_POST['custom_fields'] ?? [];
    $custom_fields = array_filter(array_map('trim', $custom_fields));

    $file = 'jobs.json';
    $jobs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

    // Add new job
    $jobs[] = [
        'title' => $title,
        'faculty' => $faculty,
        'department' => $department,
        'position' => $position,
        'qualification' => $qualification,
        'description' => $description,
        'requirement_qualification' => $requirement_qualification,
        'required_experience' => $required_experience,
        'required_publications' => $required_publications,
        'required_body' => $required_body,

        // NEW
        'custom_fields' => $custom_fields
    ];

    file_put_contents($file, json_encode($jobs, JSON_PRETTY_PRINT));

    $_SESSION['message'] = "Job added successfully!";
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Job Posting</title>
<style>
body {
    font-family: Arial, sans-serif;
    background:#f0f2f5;
    margin:0;
    padding:0;
}
.container {
    max-width:650px;
    margin:40px auto;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
input, textarea {
    width:100%;
    padding:10px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:5px;
}
button {
    padding:10px 20px;
    border:none;
    border-radius:5px;
    background:#800000;
    color:white;
    cursor:pointer;
}
button:hover {
    background:#660000;
}
.add-btn {
    background:#008000;
    margin-bottom:10px;
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
h3 {
    color:#800000;
    border-bottom:2px solid #ccc;
    padding-bottom:5px;
}
.field-block { margin-bottom:10px; }
</style>
</head>
<body>

<nav>
    <div class="nav-container">
        <img src="logo.jfif" alt="Site Logo">
        <div class="nav-text">
            <h1>Ekiti State University, Ado-Ekiti</h1>
            <h5>Job Recruitment Portal</h5>
        </div>
    </div>
    <div>
        <a href="admin.php" style="background:#008000; padding:8px 15px; border-radius:5px;">🏠 Dashboard</a>
        <a href="logout.php" style="background:#cc0000; padding:8px 15px; border-radius:5px;">Logout</a>
    </div>
</nav>

<div class="container">
    <h2>Add New Job</h2>

    <form method="POST">

        <label>Job Title:</label>
        <input type="text" name="title" required>

        <label>Faculty:</label>
        <input type="text" name="faculty" required>

        <label>Department:</label>
        <input type="text" name="department" required>

        <label>Position:</label>
        <input type="text" name="position" required>

        <label>Minimum Qualification:</label>
        <input type="text" name="qualification" required>

        <label>Job Description:</label>
        <textarea name="description" rows="4" required></textarea>

        <hr>
        <h3>Admin-Only Requirements</h3>

        <label>Academic Qualification Requirement:</label>
        <input type="text" name="requirement_qualification" required>

        <label>Years of Teaching Experience:</label>
        <input type="number" name="required_experience" required>

        <label>Required Number of Publications:</label>
        <input type="number" name="required_publications" required>

        <label>Professional Body Registration:</label>
        <input type="text" name="required_body" required>

        <hr>
        <h3>Custom Job Requirements (+ Add More)</h3>

        <div id="customFieldsContainer">
            <div class="field-block">
                <input type="text" name="custom_fields[]" placeholder="Enter custom requirement">
            </div>
        </div>

        <button type="button" class="add-btn" id="addFieldBtn">+ Add More</button>
        <br><br>

        <button type="submit">Add Job</button>
    </form>
</div>

<script>
document.getElementById("addFieldBtn").addEventListener("click", function () {
    const div = document.createElement("div");
    div.className = "field-block";
    div.innerHTML = '<input type="text" name="custom_fields[]" placeholder="Enter custom requirement">';
    document.getElementById("customFieldsContainer").appendChild(div);
});
</script>

</body>
</html>
