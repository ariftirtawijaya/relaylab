<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/config.php';

$token = TELEGRAM_BOT_TOKEN;
$admins = TELEGRAM_ADMIN_CHAT_IDS; // array chat_id

if (empty($token) || empty($admins) || !is_array($admins)) {
    die("<p><strong>Config error:</strong> TOKEN atau daftar chat_id belum di-set dengan benar.</p>");
}

$text = "🔔 *Test Notifikasi Telegram*\n"
    . "Pesan percobaan dari sistem RelayLab.\n"
    . "Waktu: " . date('d-m-Y H:i:s');

$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';

echo "<h3>Test Kirim Telegram ke Banyak Admin</h3>";

foreach ($admins as $chatId) {

    echo "<h4>Mengirim ke Chat ID: {$chatId}</h4>";

    $payload = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
    ];

    try {
        // Coba file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
            ]
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            echo "<p><strong>file_get_contents gagal.</strong> Coba cURL...</p>";

            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($payload),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                ]);

                $res = curl_exec($ch);

                if ($res === false) {
                    echo "<pre><strong>cURL Error:</strong> " . htmlspecialchars(curl_error($ch)) . "</pre>";
                } else {
                    echo "<pre>Response cURL:\n" . htmlspecialchars($res) . "</pre>";
                }

                curl_close($ch);
            } else {
                echo "<p>cURL tidak tersedia di server.</p>";
            }

        } else {
            echo "<pre>Response:\n" . htmlspecialchars($result) . "</pre>";
        }

    } catch (Throwable $e) {
        echo "<p><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "<hr>";
}

echo "<p>✔️ Selesai mengirim ke semua admin.</p>";
