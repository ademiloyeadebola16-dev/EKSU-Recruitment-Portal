<?php
session_start();

// Remove only applicant session
unset($_SESSION['applicant']);
unset($_SESSION['applicant_email']);

// Optional security
session_regenerate_id(true);

// Redirect to homepage
header("Location: index.php");
exit();
