<?php
// send_application_mail.php
require __DIR__ . "/vendor/autoload.php";  
require __DIR__ . "/mail_config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendApplicantMail($toEmail, $toName, $jobTitle)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = MAIL_PORT;

        // Sender
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

        // Receiver
        $mail->addAddress($toEmail, $toName);

        // Email subject
        $mail->Subject = "Your Application Has Been Received - EKSU Recruitment";

        // Email body
        $body = "
        <h2 style='color:#800000;'>Application Received</h2>
        <p>Dear <b>{$toName}</b>,</p>
        <p>Thank you for applying for the position of <b>{$jobTitle}</b> at 
        Ekiti State University, Ado-Ekiti.</p>
        <p>Your application has been successfully submitted and is currently under review.</p>
        <p>You will receive further communication after evaluation.</p>
        <br>
        <p>Regards,<br><b>EKSU Recruitment Team</b></p>
        ";

        $mail->isHTML(true);
        $mail->Body = $body;

        return $mail->send();

    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
