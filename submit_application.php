<?php
session_start();
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load applications
$applications_file = 'applications.json';
$applications = file_exists($applications_file) ? json_decode(file_get_contents($applications_file), true) : [];

// Collect submitted data
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$job_title  = trim($_POST['job_title'] ?? '');
$date       = date('Y-m-d H:i:s');

// Professional Information
$qualification = trim($_POST['academic_qualification'] ?? '');
$qualification_other = trim($_POST['academic_qualification_other'] ?? '');
$professional_body = trim($_POST['professional_body'] ?? '');
$publications = trim($_POST['publications'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$cover_letter = trim($_POST['cover_letter'] ?? '');

// Handle "Other (specify)"
if ($qualification === 'Other' && $qualification_other !== '') {
    $qualification = $qualification_other;
}

if (!$first_name || !$last_name || !$email || !$job_title) {
    die("Please fill all required fields.");
}

/* ---------------------------------------------
   GENERATE APPLICANT NUMBER  (NEW CODE ADDED)
   --------------------------------------------- */

// Get current year
$year = date("Y");

// Generate new sequence number
$last_number = 0;

if (!empty($applications)) {
    $numbers = [];

    foreach ($applications as $app) {
        if (isset($app['applicant_number'])) {
            $parts = explode("/", $app['applicant_number']);
            $num = end($parts); // last part (0001, 0002...)
            if (is_numeric($num)) {
                $numbers[] = (int)$num;
            }
        }
    }

    if (!empty($numbers)) {
        $last_number = max($numbers);
    }
}

$new_number = $last_number + 1;

// Format as 4 digits with leading zeros
$formatted_number = str_pad($new_number, 4, '0', STR_PAD_LEFT);

// Final Applicant Number
$applicant_number = "EKSU/APP/{$year}/{$formatted_number}";

/* ---------------------------------------------
   BUILD APPLICATION ENTRY
   --------------------------------------------- */

$new_application = [
    'applicant_number' => $applicant_number, // NEW FIELD
    'first_name' => $first_name,
    'last_name'  => $last_name,
    'email'      => $email,
    'job_title'  => $job_title,
    'academic_qualification' => $qualification,
    'professional_body' => $professional_body,
    'publications' => $publications,
    'experience' => $experience,
    'cover_letter' => $cover_letter,
    'status' => 'Pending',
    'internal_status' => '',
    'reason' => '',
    'date'  => $date
];

// Save application
$applications[] = $new_application;
file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));

/* ---------------------------------------------
   SEND EMAIL NOTIFICATION (UPDATED)
   --------------------------------------------- */

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-email@gmail.com';
    $mail->Password   = 'your-email-password'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('noreply@eksu.edu.ng', 'EKSU Recruitment');
    $mail->addAddress($email, "{$first_name} {$last_name}");

    $mail->isHTML(true);
    $mail->Subject = "Your EKSU Applicant Number";

    $mail->Body = "
        <p>Dear {$first_name} {$last_name},</p>

        <p>Thank you for applying for the position of <strong>{$job_title}</strong>.</p>

        <p>Your Applicant Number is:</p>

        <h2 style='color:#800000;'>{$applicant_number}</h2>

        <p>Please keep this number safe. You will need it for all future communication.</p>

        <p>Your application is currently <strong>under review</strong>.</p>

        <br>
        <p>Best regards,<br>EKSU Recruitment Team</p>
    ";

    $mail->send();
    $_SESSION['message'] = "Application submitted successfully. Your Applicant Number is: {$applicant_number}";
} 
catch (Exception $e) {
    $_SESSION['message'] = "Application submitted. Email error: " . $mail->ErrorInfo;
}

// Redirect
header("Location: application_form.php?applicant_number=" . urlencode($applicant_number));
exit();

?>
