<?php
require_once __DIR__ . '/config.php';

// ==== Load PHPMailer (manual) ====
// Kita coba include manual dari app/PHPMailer/src.
// Kalau folder belum ada, fungsi mail akan skip otomatis (tidak fatal).
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $base = __DIR__ . '/PHPMailer/src';
    if (file_exists($base . '/PHPMailer.php')) {
        require_once $base . '/PHPMailer.php';
        require_once $base . '/SMTP.php';
        require_once $base . '/Exception.php';
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Kirim email ke admin ketika ada pengajuan kasbon baru.
 *
 * @param string $empName  Nama pegawai
 * @param string $empId    ID pegawai
 * @param int    $amount   Nominal kasbon
 * @param string $note     Catatan pegawai
 * @param int    $kasbonId ID kasbon (untuk referensi/link)
 */
function mail_admin_kasbon_new(string $empName, string $empId, int $amount, string $note, int $kasbonId): void
{
    // Kalau dimatikan di config, jangan kirim apapun.
    if (!defined('SMTP_ENABLE') || SMTP_ENABLE === false) {
        return;
    }

    // Kalau PHPMailer belum ada, jangan fatal.
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('PHPMailer tidak ditemukan. Pastikan folder app/PHPMailer/src ada.');
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            echo "<p>PHPMailer tidak ditemukan. Cek folder <code>app/PHPMailer/src</code>.</p>";
        }
        return;
    }

    // Kalau email admin belum di-set, skip.
    if (empty(SMTP_ADMIN_EMAIL)) {
        return;
    }

    $mail = new PHPMailer(true);

    try {
        // ===== Server settings =====
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;

        // Pakai SSL (SMTPS) di port 465
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;  // 465

        // Biar tidak nge-hang lama
        $mail->Timeout     = 10;
        $mail->SMTPAutoTLS = false; // supaya tidak maksa STARTTLS di 465

        // Debug
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            $mail->SMTPDebug   = 2;
            $mail->Debugoutput = 'html';
        } else {
            $mail->SMTPDebug = 0;
        }

        // ===== From / To =====
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME ?: 'Presensi');
        $mail->addAddress(SMTP_ADMIN_EMAIL);

        // ===== Subject & Body =====
        $amountFmt      = 'Rp ' . number_format($amount, 0, ',', '.');
        $adminKasbonUrl = rtrim(URL_BASE, '/') . '/admin_kasbon.php';

        $mail->isHTML(true);
        $mail->Subject = "[Kasbon Baru] {$empId} - {$empName} - {$amountFmt}";

        $htmlNote = nl2br(htmlspecialchars($note, ENT_QUOTES, 'UTF-8'));

        $mail->Body = "
            <p>Hai Admin,</p>
            <p>Ada pengajuan kasbon baru:</p>
            <table cellpadding='4' cellspacing='0' border='0'>
                <tr><td><strong>Pegawai</strong></td><td>: ".htmlspecialchars($empName, ENT_QUOTES, 'UTF-8')." ({$empId})</td></tr>
                <tr><td><strong>Nominal</strong></td><td>: {$amountFmt}</td></tr>
                <tr><td><strong>Catatan</strong></td><td>: {$htmlNote}</td></tr>
                <tr><td><strong>ID Kasbon</strong></td><td>: {$kasbonId}</td></tr>
            </table>
            <hr>
            <p>Relaylab Autolight</p>
        ";

        $mail->AltBody =
            "Pengajuan kasbon baru.\n".
            "Pegawai: {$empName} ({$empId})\n".
            "Nominal: {$amountFmt}\n".
            "Catatan: {$note}\n".
            "ID Kasbon: {$kasbonId}\n\n";

        $mail->send();

    } catch (Exception $e) {
        error_log('Kasbon mail error: ' . $e->getMessage());
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            echo "<pre>Mailer Error: ".htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, 'UTF-8')."</pre>";
        }
    }
}

/**
 * Kirim notifikasi kasbon baru ke Telegram admin (tanpa Markdown, biar minim error).
 */
function telegram_admin_kasbon_new(string $empName, string $empId, int $amount, string $note, int $kasbonId): void
{
    if (!defined('KASBON_NOTIFY_TELEGRAM') || KASBON_NOTIFY_TELEGRAM === false) {
        return;
    }
    if (empty(TELEGRAM_BOT_TOKEN) || empty(TELEGRAM_ADMIN_CHAT_ID)) {
        return;
    }

    $amountFmt      = 'Rp ' . number_format($amount, 0, ',', '.');
    $adminKasbonUrl = rtrim(URL_BASE, '/') . '/admin_kasbon.php';

    // Plain text saja, tanpa Markdown
    $text  = "Kasbon Baru\n";
    $text .= "Pegawai : {$empName} ({$empId})\n";
    $text .= "Nominal : {$amountFmt}\n";
    if ($note !== '') {
        $text .= "Catatan : {$note}\n";
    }
    $text .= "ID Kasbon : {$kasbonId}\n";

    $payload = [
        'chat_id' => TELEGRAM_ADMIN_CHAT_ID,
        'text'    => $text,
    ];

    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';

    try {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
            ],
        ];
        $context = stream_context_create($options);
        $result  = @file_get_contents($url, false, $context);

        if ($result === false) {
            // Fallback ke cURL kalau ada
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query($payload),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                ]);
                $res = curl_exec($ch);
                if ($res === false) {
                    error_log('Telegram notify cURL error: ' . curl_error($ch));
                } else {
                    $data = json_decode($res, true);
                    if (!isset($data['ok']) || !$data['ok']) {
                        error_log('Telegram notify error (curl): ' . ($data['description'] ?? 'unknown'));
                    }
                }
                curl_close($ch);
            } else {
                error_log('Telegram notify error: file_get_contents & cURL gagal / tidak tersedia.');
            }
        } else {
            $data = json_decode($result, true);
            if (!isset($data['ok']) || !$data['ok']) {
                error_log('Telegram notify error: ' . ($data['description'] ?? 'unknown'));
            }
        }

    } catch (Throwable $e) {
        error_log('Telegram notify exception: ' . $e->getMessage());
    }
}

/**
 * Wrapper utama: panggil email &/atau telegram sesuai config.
 */
function notify_admin_kasbon_new(string $empName, string $empId, int $amount, string $note, int $kasbonId): void
{
    if (defined('KASBON_NOTIFY_EMAIL') && KASBON_NOTIFY_EMAIL) {
        mail_admin_kasbon_new($empName, $empId, $amount, $note, $kasbonId);
    }

    if (defined('KASBON_NOTIFY_TELEGRAM') && KASBON_NOTIFY_TELEGRAM) {
        telegram_admin_kasbon_new($empName, $empId, $amount, $note, $kasbonId);
    }
}
