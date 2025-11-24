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

if (!$first_name || !$last_name || !$email || !$job_title) {
    die("Please fill all required fields.");
}

// Add application to the JSON
$new_application = [
    'first_name' => $first_name,
    'last_name'  => $last_name,
    'email'      => $email,
    'job_title'  => $job_title,
    'status'     => 'Pending',
    'internal_status' => '',
    'reason'     => '',
    'date'       => $date
];

$applications[] = $new_application;
file_put_contents($applications_file, json_encode($applications, JSON_PRETTY_PRINT));

// -----------------------------
// Send email notification via SMTP
// -----------------------------
$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';       // Use your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-email@gmail.com'; // Replace with your SMTP email
    $mail->Password   = 'your-email-password';  // Use app password if Gmail
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Sender & recipient
    $mail->setFrom('noreply@eksu.edu.ng', 'EKSU Recruitment');
    $mail->addAddress($email, "{$first_name} {$last_name}");

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Your application is under review - EKSU Recruitment";
    $mail->Body    = "
        <p>Dear {$first_name} {$last_name},</p>
        <p>Thank you for submitting your application for the position of <strong>{$job_title}</strong>.</p>
        <p>Your application is currently <strong>under review</strong> by our recruitment team.</p>
        <p>We will notify you once the review is complete.</p>
        <br>
        <p>Best regards,<br>EKSU Recruitment Team</p>
    ";

    $mail->send();
    $_SESSION['message'] = "Application submitted successfully. Confirmation email sent.";
} catch (Exception $e) {
    $_SESSION['message'] = "Application submitted successfully. Email could not be sent: {$mail->ErrorInfo}";
}

// Redirect to confirmation page
header("Location: application_form.php");
exit();
