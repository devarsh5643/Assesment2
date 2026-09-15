<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendEnquiryNotification(array $enquiry): bool
{
    $configPath = '/var/www/.bakery-email.json';
    if (!is_readable($configPath)) {
        error_log('Bakery enquiry email is not configured.');
        return false;
    }

    $config = json_decode((string) file_get_contents($configPath), true);
    if (!is_array($config)) {
        error_log('Bakery enquiry email configuration is invalid.');
        return false;
    }

    $username = trim((string) ($config['username'] ?? ''));
    $password = (string) ($config['password'] ?? '');
    $recipient = trim((string) ($config['recipient'] ?? ''));
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)
        || !filter_var($recipient, FILTER_VALIDATE_EMAIL)
        || $password === '') {
        error_log('Bakery enquiry email configuration is incomplete.');
        return false;
    }

    $name = trim((string) ($enquiry['name'] ?? 'Customer'));
    $email = trim((string) ($enquiry['email'] ?? ''));
    $phone = trim((string) ($enquiry['phone'] ?? ''));
    $type = trim((string) ($enquiry['type'] ?? 'General'));
    $message = trim((string) ($enquiry['message'] ?? ''));
    $submittedAt = date('j M Y, g:i a');
    $escape = static function (string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = (string) ($config['host'] ?? 'smtp.gmail.com');
        $mail->Port = (int) ($config['port'] ?? 587);
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 12;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        $mail->setFrom($username, 'Adelaide Artisan Bakery Website');
        $mail->addAddress($recipient);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($email, $name);
        }

        $mail->isHTML(true);
        $mail->Subject = 'New website enquiry: ' . $type;
        $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:640px;color:#26323d">'
            . '<div style="background:#17212b;color:#fff;padding:22px 26px;border-radius:10px 10px 0 0">'
            . '<p style="margin:0 0 5px;font-size:12px;color:#c8d1d8">ADELAIDE ARTISAN BAKERY</p>'
            . '<h1 style="margin:0;font-size:22px">New customer enquiry</h1></div>'
            . '<div style="padding:24px 26px;border:1px solid #e2e7eb;border-top:0;border-radius:0 0 10px 10px">'
            . '<p style="margin-top:0"><strong>Type:</strong> ' . $escape($type) . '</p>'
            . '<p><strong>Name:</strong> ' . $escape($name) . '</p>'
            . '<p><strong>Email:</strong> <a href="mailto:' . $escape($email) . '">' . $escape($email) . '</a></p>'
            . '<p><strong>Phone:</strong> ' . $escape($phone) . '</p>'
            . '<p><strong>Submitted:</strong> ' . $escape($submittedAt) . '</p>'
            . '<div style="margin:20px 0;padding:18px;background:#f5f6f7;border-left:4px solid #a65c2e">'
            . nl2br($escape($message)) . '</div>'
            . '<p style="margin-bottom:0"><a href="https://32.236.141.114/index.php?page=admin">Open the admin dashboard</a></p>'
            . '</div></div>';
        $mail->AltBody = "New customer enquiry\n\n"
            . "Type: {$type}\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\nSubmitted: {$submittedAt}\n\n"
            . $message . "\n\nAdmin: https://32.236.141.114/index.php?page=admin";

        $mail->send();
        return true;
    } catch (Throwable $exception) {
        error_log('Bakery enquiry email failed: ' . ($mail->ErrorInfo ?: $exception->getMessage()));
        return false;
    }
}
