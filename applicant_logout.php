<?php
session_start();

// Destroy only applicant-related session data
if (isset($_SESSION['applicant_email'])) {
    unset($_SESSION['applicant_email']);
}

session_destroy();

// Redirect to homepage
header("Location: index.php");
exit();
?>
