<?php
// send_reset_link.php
require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/mail_config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetLink($email, $resetLink)
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
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Password Reset Request - EKSU Recruitment Portal";

        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Click the link below to reset your password:</p>
            <p><a href='$resetLink'>Reset Password</a></p>
            <p>If you did not request this, ignore this email.</p>
        ";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Reset mail error: " . $mail->ErrorInfo);
        return false;
    }
}
