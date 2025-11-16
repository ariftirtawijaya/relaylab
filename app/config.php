<?php
// Ubah sesuai server
define('DB_HOST', 'localhost');
define('DB_NAME', 'pupq3195_presensi');
define('DB_USER', 'pupq3195_admin');
define('DB_PASS', '0WJk,0)ttfAN(M1V');

// Default TZ
date_default_timezone_set('Asia/Jakarta');
define('URL_BASE', '/');
//0WJk,0)ttfAN(M1V

// Jam kerja & aturan
const START_ALLOW_IN = '08:00:00'; // earliest allowed check-in
const ONTIME_LIMIT = '08:59:59'; // <= ini masih tepat waktu
const LATE_BASE = '09:00:00'; // mulai hitung telat
const MIN_CHECKOUT = '17:00:00'; // minimal boleh absen keluar
const OT_BASE = '18:00:00'; // mulai dihitung lembur (18:00 = 1 jam)
const LATE_FINE_PER_H = 10000;      // potongan telat per jam

const SMTP_ENABLE = true;  // sementara kalau mau matikan email: set ke false
const SMTP_DEBUG = false; // set true saat TEST manual

// ====== SMTP / Email Settings ======
// Ganti sesuai akun email SMTP kamu (misal Gmail, Mailgun, dll)
const SMTP_HOST = 'mail.relaylab.id';
const SMTP_PORT = 465;             // 587 (TLS) / 465 (SSL)
const SMTP_USER = 'kasbon@relaylab.id';
const SMTP_PASS = 'TuHgNPTCFrxB24';
const SMTP_FROM = 'kasbon@relaylab.id';
const SMTP_FROM_NAME = 'Kasbon RelayLab';  // bebas
const SMTP_ADMIN_EMAIL = 'mochariftirta@gmail.com';   // tujuan notifikasi kasbon

// ====== Notifikasi Kasbon ======
const KASBON_NOTIFY_EMAIL = false;   // kirim notifikasi via email?
const KASBON_NOTIFY_TELEGRAM = true;   // kirim notifikasi via Telegram?

// ====== Telegram Settings ======
// Buat bot pakai @BotFather, lalu isi token & chat_id admin di sini.
const TELEGRAM_BOT_TOKEN = '8079678971:AAFvuZWFzxsfMKHj1J4F4tY7YRZn4vF3ErM';      // contoh: 123456789:ABCDEF...
const TELEGRAM_ADMIN_CHAT_ID = '318416641'; // contoh: 123456789

const ATTEND_NOTIFY_TELEGRAM = true;