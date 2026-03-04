<?php
require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/mail_config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendRefereeReminder($toEmail, $toName, $applicantName, $jobTitle, $token)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

         $uploadLink = "http://localhost/my%20code/My%20PHP%20codes/upload_reference.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Reminder: Reference Letter Required";

        $mail->Body = "
        <h3>Reference Reminder</h3>
        <p>Dear <b>{$toName}</b>,</p>
        <p>This is a reminder to upload the reference letter for 
        <b>{$applicantName}</b> applying for <b>{$jobTitle}</b>.</p>

        <p>Please submit your reference within the allowed time.</p>

        <p>
        <a href='{$uploadLink}' style='
            background:#800000;
            color:white;
            padding:10px 15px;
            text-decoration:none;
            border-radius:5px;
        '>Upload Reference</a>
        </p>

        <p>Thank you.</p>
        <br>
        <p><b>EKSU Recruitment Team</b></p>
        ";

        return $mail->send();

    } catch (Exception $e) {
        error_log("Reminder Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
