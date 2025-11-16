<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/config.php';

$token   = TELEGRAM_BOT_TOKEN;
$chatId  = TELEGRAM_ADMIN_CHAT_ID;

$text = "🔔 *Test Notifikasi Telegram*\n"
      . "Ini hanya pesan percobaan dari sistem kasbon.\n"
      . "Waktu: " . date('d-m-Y H:i:s');

$payload = [
    'chat_id'    => $chatId,
    'text'       => $text,
    'parse_mode' => 'Markdown',
];

$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';

echo "<h3>Test Kirim Telegram</h3>";

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
        echo "<p><strong>file_get_contents gagal.</strong> Coba ulangi dengan cURL...</p>";

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
                echo "<p><strong>cURL error:</strong> " . htmlspecialchars(curl_error($ch)) . "</p>";
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
