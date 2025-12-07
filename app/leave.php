<?php
require_once __DIR__ . '/db.php';

/** Ambil status izin/sakit pegawai pada tanggal tertentu */
function leave_get_status(int $uid, string $date): ?array
{
    $st = pdo()->prepare("SELECT * FROM leave_days WHERE user_id=? AND leave_date=? LIMIT 1");
    $st->execute([$uid, $date]);
    return $st->fetch() ?: null;
}

/** Cek apakah ada izin/sakit pada tanggal tersebut */
function leave_is_active(int $uid, string $date): bool
{
    return leave_get_status($uid, $date) !== null;
}
