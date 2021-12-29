<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';
// passing true in constructor enables exceptions in PHPMailer
function php_mailer($data){
    $mail = new PHPMailer(true);
    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // for detailed debug output
        $mail->SMTPDebug = 0; // for detailed debug output
        $mail->isSMTP();$mail->Host = 'smtp.gmail.com';$mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;$mail->Port = 587;
        $sender = "enquiry@kidovillage.com";
        $passwd = "kv@enquiry";
        // $to = "ziauddin.sayyed@kido.school";
        // $cc = "fauzan.falke@kido.school";
        // $rece_nm = "Receiver Name";
        $mail->Username = $sender; // YOUR gmail email
        $mail->Password = $passwd; // YOUR gmail password
        $mail->setFrom($sender, 'Kido audit admin');
        foreach ($data['users'] as $k => $v) {
            $mail->addAddress($v, "User".($k+1));
        }
        isset($data['cc']) ? $mail->addCC($cc) : FALSE;
        $mail->addReplyTo($sender, 'Kido audit admin');
        $mail->IsHTML(true);
        $mail->Subject = $data['subject'];
        $mail->Body = $data['message'];
        // $mail->AltBody = 'Plain text message body for non-HTML email client. Gmail SMTP email body.';
        $temp = $mail->send();
        // echo "Email message sent.";
    } catch (Exception $e) {
        echo "Error in sending email. Mailer Error: {$mail->ErrorInfo}";
    }
}

?>