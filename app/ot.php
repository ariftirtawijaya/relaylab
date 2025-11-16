<?php
require_once __DIR__ . '/db.php';

/** Ambil multiplier untuk tanggal (string Y-m-d atau DateTime). Default 1.0 jika tidak ada. */
function ot_get_multiplier($date): float
{
    if ($date instanceof DateTime) {
        $d = $date->format('Y-m-d');
    } else {
        $d = (string) $date;
    }
    $st = pdo()->prepare("SELECT multiplier FROM overtime_days WHERE work_date=? LIMIT 1");
    $st->execute([$d]);
    $row = $st->fetch();
    if ($row && isset($row['multiplier'])) {
        $m = floatval($row['multiplier']);
        return ($m > 0) ? $m : 1.0;
    }
    return 1.0;
}

/** Ambil semua hari multiplier (untuk admin list). */
function ot_list(): array
{
    $st = pdo()->query("SELECT work_date, multiplier, note FROM overtime_days ORDER BY work_date DESC");
    return $st->fetchAll();
}
