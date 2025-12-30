<?php
session_start();

if (!isset($_SESSION['applicant_email'])) {
    header("Location: applicant_login.php");
    exit();
}

// If coming from first apply page, store previous data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['application_data'] = $_POST;
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Academic Records</title>

<style>
body {font-family: Arial; background:#eef1f5; padding:20px;}
.container {
    max-width:800px; margin:auto; background:white; padding:25px;
    border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
h2 {color:#800000;}
input, select {
    width:100%; padding:10px; margin:10px 0;
    border:1px solid #ccc; border-radius:5px;
}
button {
    padding:10px 15px; background:#800000; color:white;
    border:none; border-radius:5px; cursor:pointer;
}
button:hover { background:#5a0000; }
.add-btn {
    background:#004080;
    margin-bottom:15px;
}
.record-box {
    border:1px solid #ddd; padding:15px;
    border-radius:8px; margin-bottom:20px;
    background:#fafafa;
}
.remove-btn {
    background:#b30000;
    margin-top:10px;
}
</style>

<script>
function addRecord() {
    let box = document.createElement("div");
    box.className = "record-box";

    box.innerHTML = `
        <label>Name of Institution</label>
        <input type="text" name="institution[]" required>

        <label>Qualification Obtained</label>
        <input type="text" name="qualification[]" required>

        <label>Date From</label>
        <input type="date" name="date_from[]" required>

        <label>Date To</label>
        <input type="date" name="date_to[]" required>

        <label>Upload Certificate</label>
        <input type="file" name="certificate[]" accept=".pdf,.jpg,.jpeg,.png" required>

        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
    `;

    document.getElementById("records").appendChild(box);
}
</script>

</head>
<body>

<div class="container">
    <h2>Academic Records</h2>
    <p>Please enter your academic information below. You may add multiple records.</p>

    <form action="apply_submit.php" method="POST" enctype="multipart/form-data">

        <h3>Passport Photograph</h3>
        <input type="file" name="passport" accept=".jpg,.jpeg,.png" required>

        <h3>Academic Records</h3>

        <button type="button" class="add-btn" onclick="addRecord()">+ Add More</button>

        <div id="records">
            <!-- First default record -->
            <div class="record-box">
                <label>Name of Institution</label>
                <input type="text" name="institution[]" required>

                <label>Qualification Obtained</label>
                <input type="text" name="qualification[]" required>

                <label>Date From</label>
                <input type="date" name="date_from[]" required>

                <label>Date To</label>
                <input type="date" name="date_to[]" required>

                <label>Upload Certificate</label>
                <input type="file" name="certificate[]" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
        </div>

        <br>
        <button type="submit">Proceed to Final Submission</button>
    </form>
</div>

</body>
</html>
