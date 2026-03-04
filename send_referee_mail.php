<?php
require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/mail_config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendRefereeMail($toEmail, $toName, $applicantName, $jobTitle, $token)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // CORRECT LINK
        $uploadLink = "http://localhost/my%20code/My%20PHP%20codes/upload_reference.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Reference Request – EKSU Recruitment";

        $mail->Body = "
        <h3>Referee Request</h3>
        <p>Dear <b>{$toName}</b>,</p>
        <p>You have been listed as a referee for <b>{$applicantName}</b>
        who applied for the position of <b>{$jobTitle}</b>.</p>

        <p>Please upload your reference letter (PDF only) using the link below:</p>

        <p>
        <a href='{$uploadLink}' style='
            background:#800000;
            color:white;
            padding:10px 15px;
            text-decoration:none;
            border-radius:5px;
        '>Upload Reference Letter</a>
        </p>

        <p>This link is confidential and meant only for you.</p>

        <br>
        <p>Regards,<br><b>EKSU Recruitment Team</b></p>
        ";

        return $mail->send();

    } 
    catch (Exception $e) {
    error_log("Mail Error: " . $mail->ErrorInfo);
    return false;
}
}
