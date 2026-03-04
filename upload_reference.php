<?php
require 'db.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid reference link.");
}

// Fetch referee by token
$stmt = $pdo->prepare("SELECT * FROM referees WHERE token = ?");
$stmt->execute([$token]);
$referee = $stmt->fetch();

if (!$referee) {
    die("Invalid or expired token.");
}

// Check if already submitted
if (!empty($referee['file_path'])) {
    die("Reference already submitted. Thank you.");
}
// Check 7-day deadline
$created = strtotime($referee['created_at']);
$deadline = $created + (7 * 24 * 60 * 60); // 7 days in seconds

if (time() > $deadline) {
    die("This reference upload link has expired. The 7-day submission period has passed.");
}

$message = "";
$remainingSeconds = max(0, $deadline - time());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['reference_file'])) {
        $message = "No file uploaded.";
    } else {

        $file = $_FILES['reference_file'];

        // Check file errors
        if ($file['error'] !== 0) {
            $message = "Upload error.";
        } else {

            // Allow only PDF
            $allowed = ['application/pdf'];
            $fileType = mime_content_type($file['tmp_name']);

            if (!in_array($fileType, $allowed)) {
                $message = "Only PDF files are allowed.";
            } else {

                // Create upload folder if not exists
                $uploadDir = "uploads/references/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Unique filename
                $fileName = "ref_" . $referee['id'] . "_" . time() . ".pdf";
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {

                    // Update DB
                    $stmt = $pdo->prepare("
                        UPDATE referees 
                        SET file_path = ?, submitted_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$filePath, $referee['id']]);

                    $message = "Reference submitted successfully. Thank you!";
                } else {
                    $message = "Failed to save file.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upload Reference</title>
    <meta charset="UTF-8">
    <style>
body {
    font-family: Arial, sans-serif;
    background: #eef1f5;
    margin: 0;
    padding: 0;
}

/* ================= NAV ================= */
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

/* ================= MAIN BOX ================= */
.box {
    max-width: 400px;
    margin: 80px auto;
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
}

input[type="file"] {
    width: 100%;
    margin: 15px 0;
}

button {
    width: 100%;
    background: #800000;
    color: white;
    border: none;
    padding: 10px;
    cursor: pointer;
    border-radius: 5px;
    font-size: 15px;
}

button:hover {
    background: #660000;
}

a {
    color: #800000;
    text-decoration: none;
}

/* ================= MESSAGE ================= */
.message {
    margin-top: 15px;
    color: green;
    font-weight: bold;
}

/* ================= FOOTER ================= */
footer {
    background: #800000;
    color: white;
    text-align: center;
    padding: 20px;
    margin-top: 60px;
}

footer a {
    color: #ffffff;
    text-decoration: underline;
    transition: opacity 0.3s ease, text-decoration-color 0.3s ease;
}

footer a:hover {
    opacity: 0.75;
    text-decoration: none;
}

/* ================= MOBILE ================= */
@media (max-width: 600px) {
    .box {
        width: 90%;
        margin: 40px auto;
    }

    nav {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }

    footer {
        padding: 12px 10px;
        font-size: 13px;
    }
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

<div class="box">
    <h2>Reference Upload</h2>
    <p>Referee: <strong><?php echo htmlspecialchars($referee['name']); ?></strong></p>
<div style="margin-bottom:15px; font-weight:bold;">
    Deadline: <span id="countdown" style="color:#800000;"></span>
</div>
    <?php if (!$message): ?>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="reference_file" accept="application/pdf" required>
            <br>
            <button type="submit">Upload Reference</button>
        </form>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
</div>
<script>
let remaining = <?= $remainingSeconds ?>;

function updateCountdown() {
    if (remaining <= 0) {
        document.getElementById("countdown").innerHTML = "Expired";
        document.body.innerHTML = "<h2 style='color:red'>This reference link has expired.</h2>";
        return;
    }

    let days = Math.floor(remaining / (60 * 60 * 24));
    let hours = Math.floor((remaining % (60 * 60 * 24)) / (60 * 60));
    let minutes = Math.floor((remaining % (60 * 60)) / 60);
    let seconds = remaining % 60;

    document.getElementById("countdown").innerHTML =
        days + "d " + hours + "h " + minutes + "m " + seconds + "s";

    remaining--;
}

setInterval(updateCountdown, 1000);
updateCountdown();
</script>


<footer>
    <p>&copy; <?= date('Y') ?> EKSU Recruitment. All rights reserved.</p>
</footer>

</body>
</html>
