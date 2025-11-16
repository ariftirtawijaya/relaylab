<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/mail.php';

echo "<h3>Test Kirim Email Kasbon</h3>";

$dummyName  = 'Test Pegawai';
$dummyId    = 'EMP001';
$dummyAmt   = 123456;
$dummyNote  = 'Ini hanya test email kasbon';
$dummyKasId = 999;

// pastikan di config: SMTP_ENABLE = true, SMTP_DEBUG = true
mail_admin_kasbon_new($dummyName, $dummyId, $dummyAmt, $dummyNote, $dummyKasId);

echo "<p>Kalau PHPMailer & SMTP benar, di atas akan muncul LOG SMTP (karena SMTP_DEBUG = true).<br>Dan email harus masuk ke ".htmlspecialchars(SMTP_ADMIN_EMAIL)."</p>";
