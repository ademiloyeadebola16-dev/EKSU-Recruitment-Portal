<?php
session_start();
require 'db.php';

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

function sendStatusEmail($email, $first_name, $status)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP CONFIG (EDIT THESE)
        $mail->isSMTP();
        $mail->Host       = 'eksurecruitment.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@example.com';
        $mail->Password   = 'EMAIL_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@example.com', 'Recruitment Committee');
        $mail->addAddress($email, $first_name);

        $mail->isHTML(true);

        if ($status === 'qualified') {
            $mail->Subject = 'Qualification for Oral Interview – Recruitment Process';
            $mail->Body    = "
                Dear {$first_name},<br><br>
                We are pleased to inform you that your application has been reviewed and you have been
                <strong>qualified for the next stage of the recruitment process</strong>, which is the
                <strong>oral interview</strong>.<br><br>
                Kindly log in to your applicant dashboard to view your status and further instructions.<br><br>
                Kind regards,<br>
                <strong>Recruitment Committee</strong>
            ";
        } else {
            $mail->Subject = 'Recruitment Application Status';
            $mail->Body    = "
                Dear {$first_name},<br><br>
                Thank you for your interest in the recruitment exercise.<br><br>
                After careful consideration, we regret to inform you that you have
                <strong>not been selected for the next stage</strong> of the recruitment process.<br><br>
                We appreciate your effort and encourage you to apply in the future.<br><br>
                Kind regards,<br>
                <strong>Recruitment Committee</strong>
            ";
        }

        $mail->send();

    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['index'])) {

    $index  = (int)$_POST['index'];
    $action = $_POST['action'] ?? '';

    if ($action === 'show') {
    $visible = 1;
    $_SESSION['message'] = "Applicant status is now visible to the applicant.";

    // ✅ Fetch applicant details correctly
    $stmt = $pdo->prepare("
        SELECT 
            email,
            CONCAT(first_name, ' ', last_name) AS fullname,
            status
        FROM applications
        WHERE id = ?
    ");
    $stmt->execute([$index]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($applicant) {
        sendStatusEmail(
            $applicant['email'],
            $applicant['fullname'],
            $applicant['qualification_status']
        );
    }

} elseif ($action === 'hide') {
    $visible = 0;
    $_SESSION['message'] = "Applicant status is now hidden from the applicant.";
} else {
    header("Location: view_applications.php");
    exit();
}

    // Update visibility
    $stmt = $pdo->prepare(
        "UPDATE applications SET status_visible = ? WHERE id = ?"
    );
    $stmt->execute([$visible, $index]);

    header("Location: view_applicant_detail.php?index=" . $index);
    exit();
}

header("Location: view_applications.php");
exit();