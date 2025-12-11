<?php
require_once __DIR__ . '/config.php';

// ==== Load PHPMailer (manual) ====
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

/* ============================================================
 *  EMAIL: Notifikasi Kasbon
 * ============================================================
 */
function mail_admin_kasbon_new(string $empName, string $empId, int $amount, string $note, int $kasbonId): void
{
    if (!defined('SMTP_ENABLE') || SMTP_ENABLE === false) {
        return;
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('PHPMailer tidak ditemukan. Pastikan folder app/PHPMailer/src ada.');
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            echo "<p>PHPMailer tidak ditemukan. Cek folder <code>app/PHPMailer/src</code>.</p>";
        }
        return;
    }

    if (empty(SMTP_ADMIN_EMAIL)) {
        return;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // 465 SSL
        $mail->Port = SMTP_PORT;

        $mail->Timeout = 10;
        $mail->SMTPAutoTLS = false;

        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'html';
        } else {
            $mail->SMTPDebug = 0;
        }

        // From / To
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME ?: 'Presensi');
        $mail->addAddress(SMTP_ADMIN_EMAIL);

        // Body
        $amountFmt = 'Rp ' . number_format($amount, 0, ',', '.');

        $mail->isHTML(true);
        $mail->Subject = "[Kasbon Baru] {$empId} - {$empName} - {$amountFmt}";

        $htmlNote = nl2br(htmlspecialchars($note, ENT_QUOTES, 'UTF-8'));

        $mail->Body = "
            <p>Hai Admin,</p>
            <p>Ada pengajuan kasbon baru:</p>
            <table cellpadding='4' cellspacing='0' border='0'>
                <tr><td><strong>Pegawai</strong></td><td>: " . htmlspecialchars($empName, ENT_QUOTES, 'UTF-8') . " ({$empId})</td></tr>
                <tr><td><strong>Nominal</strong></td><td>: {$amountFmt}</td></tr>
                <tr><td><strong>Catatan</strong></td><td>: {$htmlNote}</td></tr>
                <tr><td><strong>ID Kasbon</strong></td><td>: {$kasbonId}</td></tr>
            </table>
            <hr>
            <p>Relaylab Autolight</p>
        ";

        $mail->AltBody =
            "Pengajuan kasbon baru.\n" .
            "Pegawai: {$empName} ({$empId})\n" .
            "Nominal: {$amountFmt}\n" .
            "Catatan: {$note}\n" .
            "ID Kasbon: {$kasbonId}\n\n";

        $mail->send();

    } catch (Exception $e) {
        error_log('Kasbon mail error: ' . $e->getMessage());
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            echo "<pre>Mailer Error: " . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, 'UTF-8') . "</pre>";
        }
    }
}

/* ============================================================
 *  TELEGRAM CORE (helper kirim pesan)
 * ============================================================
 */
function telegram_send_text(string $text): void
{
    if (empty(TELEGRAM_BOT_TOKEN) || empty(TELEGRAM_ADMIN_CHAT_IDS)) {
        return;
    }

    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';

    foreach (TELEGRAM_ADMIN_CHAT_IDS as $chatId) {

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        try {
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($payload),
                    'timeout' => 10,
                ],
            ];

            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);

            if ($result === false && function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($payload),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                ]);
                $res = curl_exec($ch);
                curl_close($ch);
            }

        } catch (Throwable $e) {
            error_log('Telegram exception: ' . $e->getMessage());
        }
    }
}


/* ============================================================
 *  TELEGRAM: Notifikasi Kasbon
 * ============================================================
 */
function telegram_admin_kasbon_new(string $empName, string $empId, int $amount, string $note, int $kasbonId): void
{
    if (!defined('KASBON_NOTIFY_TELEGRAM') || KASBON_NOTIFY_TELEGRAM === false) {
        return;
    }

    $amountFmt = 'Rp ' . number_format($amount, 0, ',', '.');

    $text = "Kasbon Baru\n";
    $text .= "Pegawai : {$empName} ({$empId})\n";
    $text .= "Nominal : {$amountFmt}\n";
    if ($note !== '') {
        $text .= "Catatan : {$note}\n";
    }
    $text .= "ID Kasbon : {$kasbonId}";

    telegram_send_text($text);
}

/**
 * Wrapper kasbon: panggil email &/atau telegram sesuai config.
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

/* ============================================================
 *  TELEGRAM: Notifikasi Presensi (Absen Masuk/Keluar)
 * ============================================================
 */
function telegram_notify_attendance(string $eventType, string $empName, string $empId, string $datetime, ?string $extra = null): void
{
    if (!defined('ATTEND_NOTIFY_TELEGRAM') || ATTEND_NOTIFY_TELEGRAM === false) {
        return;
    }

    if (!$empName && !$empId) {
        return;
    }

    // $eventType: 'in' atau 'out'
    $jenis = ($eventType === 'out') ? 'Absen Pulang' : 'Absen Masuk';

    try {
        $dt = (new DateTime($datetime))->format('d-m-Y H:i:s');
    } catch (Throwable $e) {
        $dt = $datetime;
    }

    $text = "Presensi Pegawai\n";
    $text .= "Jenis   : {$jenis}\n";
    $text .= "Pegawai : {$empName}\n";
    $text .= "Waktu   : {$dt}\n";
    if ($extra) {
        $text .= "Info    : {$extra}\n";
    }

    telegram_send_text($text);
}

function whatsapp_send_text(string $text): void
{
    try {
        $token = "yNuNwRkmU8L4YDyF1NQi";   // Ganti dengan token Fonnte Anda
        $group_id = "120363424390701667@g.us";    // Ganti dengan ID Group WhatsApp, format: 628xxxxxxx-xxxxx@g.us

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $group_id,
                'message' => $text,
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $token
            ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
    } catch (Throwable $e) {
        error_log('Whatsapp exception: ' . $e->getMessage());
    }

}


function whatsapp_notify_attendance(string $eventType, string $empName, string $empId, string $datetime, ?string $extra = null): void
{
    if (!$empName && !$empId) {
        return;
    }

    // $eventType: 'in' atau 'out'
    $jenis = ($eventType === 'out') ? 'Absen Pulang' : 'Absen Masuk';

    try {
        $dt = (new DateTime($datetime))->format('d-m-Y H:i:s');
    } catch (Throwable $e) {
        $dt = $datetime;
    }

    $text = "Presensi Pegawai\n";
    $text .= "Jenis   : {$jenis}\n";
    $text .= "Pegawai : {$empName}\n";

    $text .= "Waktu   : {$dt}\n";
    if ($extra) {
        $text .= "Info    : {$extra}\n";
    }

    whatsapp_send_text($text);
}
