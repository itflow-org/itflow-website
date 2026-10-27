<?php
/*
 * itflow.org - outgoing mail through PHPMailer, using $mail from config.php.
 */

defined('ITFLOW_SITE') || exit;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

/*
 * Send a plain-text email. Returns true on success. On failure returns false and
 * writes the mail server's reason to the PHP error log (never shown to visitors).
 *
 *   $to        one address or a list of addresses
 *   $reply_to  optional address replies should go to
 */
function sendMail($to, $subject, $body, $reply_to = '') {
    global $mail;

    $mailer = new PHPMailer(true);
    try {
        if (($mail['transport'] ?? 'smtp') === 'smtp') {
            // How long to wait for the mail server - for connecting and for each reply,
            // so a stalled server can't hold the page for PHPMailer's default five minutes
            $timeout = max(1, (int) ($mail['timeout'] ?? 15));
            $mailer->isSMTP();
            $mailer->Host    = $mail['host'];
            $mailer->Port    = (int) $mail['port'];
            $mailer->Timeout = $timeout;
            $mailer->getSMTPInstance()->Timelimit = $timeout;

            $encryption = strtolower((string) ($mail['encryption'] ?? ''));
            if ($encryption === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mailer->SMTPSecure  = '';
                $mailer->SMTPAutoTLS = false;
            }

            if (($mail['username'] ?? '') !== '') {
                $mailer->SMTPAuth = true;
                $mailer->Username = $mail['username'];
                $mailer->Password = $mail['password'];
            }
        } else {
            $mailer->isMail();
        }

        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->setFrom($mail['from_email'], $mail['from_name'] ?? '');
        foreach ((array) $to as $address) {
            $mailer->addAddress($address);
        }
        if ($reply_to !== '' && PHPMailer::validateAddress($reply_to)) {
            $mailer->addReplyTo($reply_to);
        }

        $mailer->isHTML(false);
        $mailer->Subject = $subject;
        $mailer->Body    = $body;

        return $mailer->send();
    } catch (MailerException $ex) {
        error_log('itflow.org mail failed: ' . $mailer->ErrorInfo);
        return false;
    }
}
