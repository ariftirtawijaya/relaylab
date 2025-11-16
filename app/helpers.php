<?php
require_once __DIR__ . '/config.php';

/** URL helper: pastikan semua link/redirect konsisten dengan /presensi/public */
function url(string $path): string
{
    return rtrim(URL_BASE, '/') . $path;
}

/** Format waktu/tanggal ke d-m-Y H:i:s */
function fmt_datetime(?string $dt): string
{
    if (!$dt)
        return '-';
    try {
        return (new DateTime($dt))->format('d-m-Y H:i:s');
    } catch (Throwable $e) {
        return $dt;
    }
}

/** Format tanggal saja ke d-m-Y */
function fmt_date(?string $d): string
{
    if (!$d)
        return '-';
    try {
        return (new DateTime($d))->format('d-m-Y');
    } catch (Throwable $e) {
        return $d;
    }
}

/** Format waktu saja ke H:i:s */
function fmt_time(?string $d): string
{
    if (!$d)
        return '-';
    try {
        return (new DateTime($d))->format('H:i:s');
    } catch (Throwable $e) {
        return $d;
    }
}

/** Format durasi menit → "H jam M menit" */
function fmt_duration_hm(int $minutes): string
{
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return sprintf('%d jam %02d menit', $h, $m);
}

/** ---- FLASH (untuk SweetAlert2) ---- */
function flash_set(string $type, string $text): void
{
    // $type: success | error | warning | info | question
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}
function flash_get(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** ====== Aturan bisnis presensi (pakai konstanta dari config.php) ====== */

/** Cek apakah tanggal (Y-m-d) adalah hari Minggu */
function is_sunday(string $ymd): bool
{
    try {
        return ((int) (new DateTime($ymd))->format('w')) === 0;
    } // 0 = Sunday
    catch (Throwable $e) {
        return false;
    }
}

/** Hitung jam telat dari in_time (DateTime) terhadap aturan. */
function calc_late_hours(DateTime $in): int
{
    $d = $in->format('Y-m-d');

    // Jika Minggu, tidak ada telat
    if (is_sunday($d))
        return 0;

    $ontimeLimit = new DateTime("$d " . ONTIME_LIMIT);  // 08:59:59
    if ($in <= $ontimeLimit)
        return 0;

    $lateBase = new DateTime("$d " . LATE_BASE);        // 09:00:00
    if ($in < $lateBase)
        return 0;

    $diffSec = $in->getTimestamp() - $lateBase->getTimestamp();
    $hours = intdiv($diffSec, 3600); // jam penuh setelah 09:00
    return 1 + $hours;               // 09:00:00 → 1 jam telat
}

/**
 * Hitung total menit lembur per record (memperhitungkan hari Minggu).
 * - Hari biasa: lembur sejak 17:00 (per menit, proporsional).
 * - Hari Minggu: lembur penuh = durasi dari in_time sampai out_time.
 */
function calc_overtime_minutes_record(string $ymd, ?string $in_str, ?string $out_str): int
{
    if (!$out_str || !$in_str)
        return 0;

    try {
        $in = new DateTime($in_str);
        $out = new DateTime($out_str);
    } catch (Throwable $e) {
        return 0;
    }
    if ($out <= $in)
        return 0;

    if (is_sunday($ymd)) {
        // Minggu: semua jam dihitung lembur penuh
        return intdiv($out->getTimestamp() - $in->getTimestamp(), 60);
    } else {
        // Hari biasa: lembur sejak 17:00
        $otStart = new DateTime("$ymd 17:00:00");
        if ($out <= $otStart)
            return 0;
        $start = max($otStart->getTimestamp(), $in->getTimestamp());
        return intdiv(max(0, $out->getTimestamp() - $start), 60);
    }
}

/**
 * Versi lama (dipakai di beberapa tempat): lembur proporsional dari 17:00.
 * Disediakan untuk kompatibilitas.
 */
function calc_overtime_minutes(DateTime $out): int
{
    $d = $out->format('Y-m-d');
    $otStart = new DateTime("$d 17:00:00");
    if ($out <= $otStart)
        return 0;
    $diffSec = $out->getTimestamp() - $otStart->getTimestamp();
    return intdiv($diffSec, 60); // menit bulat
}

/** Validasi absen masuk minimal 08:00 */
function can_checkin(DateTime $now): bool
{
    $d = $now->format('Y-m-d');
    $minIn = new DateTime("$d " . START_ALLOW_IN); // 08:00:00
    return $now >= $minIn;
}

/** Validasi absen keluar minimal 17:00 */
function can_checkout(DateTime $now): bool
{
    $d = $now->format('Y-m-d');
    $minOut = new DateTime("$d " . MIN_CHECKOUT); // 17:00:00
    return $now >= $minOut;
}

/* ========= Periode Rekap: Bulan / Minggu / Hari ========= */

/** Kembalikan [DateTime $start, DateTime $end, string $label] untuk mode rekap. */
function period_range(string $mode, ?string $bulan, ?string $tgl): array
{
    $mode = in_array($mode, ['month', 'week', 'day'], true) ? $mode : 'month';

    if ($mode === 'day') {
        // $tgl: Y-m-d (default: today)
        $d = $tgl && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) ? $tgl : date('Y-m-d');
        $start = new DateTime($d . ' 00:00:00');
        $end = (clone $start)->modify('+1 day');
        $label = 'Harian: ' . fmt_date($d);
        return [$start, $end, $label];
    }

    if ($mode === 'week') {
        // $tgl: anchor Y-m-d, dihitung Senin–Minggu
        $d = $tgl && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) ? new DateTime($tgl) : new DateTime();
        // Tentukan Senin pada pekan anchor
        $w = (int) $d->format('w'); // 0..6 (0=Min,1=Senin)
        $monday = (clone $d)->modify($w === 0 ? '-6 days' : (1 - $w) . ' days'); // jika Minggu -> mundur 6 hari
        $sunday = (clone $monday)->modify('+6 days');
        $start = new DateTime($monday->format('Y-m-d') . ' 00:00:00');
        $end = (clone $sunday)->modify('+1 day'); // exclusive
        $label = 'Mingguan: ' . fmt_date($monday->format('Y-m-d')) . ' s.d. ' . fmt_date($sunday->format('Y-m-d'));
        return [$start, $end, $label];
    }

    // default month
    $b = $bulan && preg_match('/^\d{4}-\d{2}$/', $bulan) ? $bulan : date('Y-m');
    $start = new DateTime($b . '-01 00:00:00');
    $end = (clone $start)->modify('first day of next month');
    $label = 'Bulanan: ' . (new DateTime($b . '-01'))->format('F Y');
    return [$start, $end, $label];
}
