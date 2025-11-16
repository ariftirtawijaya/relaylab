<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_role('pegawai');

$mode = $_GET['mode'] ?? 'month';               // month|week|day
$bulan = preg_replace('/[^0-9\-]/', '', $_GET['bulan'] ?? date('Y-m'));
$tgl = preg_replace('/[^0-9\-]/', '', $_GET['tgl'] ?? date('Y-m-d'));
$uid = me_id();

// Tentukan rentang periode (Senin–Minggu untuk mingguan)
[$start, $end, $period_label] = period_range($mode, $bulan, $tgl);

$params = [$uid, $start->format('Y-m-d'), $end->format('Y-m-d')];
$sql = "SELECT a.*, u.employee_id, u.name, u.overtime_rate
        FROM attendance a
        JOIN users u ON u.id=a.user_id
        WHERE a.user_id=? AND a.work_date >= ? AND a.work_date < ?
        ORDER BY a.work_date";
$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$detail = [];
$tot_late_h = $tot_fine = $tot_ot_min = $tot_otpay = 0;

foreach ($rows as $r) {
    $d = $r['work_date'];
    $in = $r['in_time'] ? new DateTime($r['in_time']) : null;
    $out = $r['out_time'] ? new DateTime($r['out_time']) : null;

    // Telat: 0 jika Minggu
    $late_h = ($in && !is_sunday($d)) ? calc_late_hours($in) : 0;

    // Lembur: Minggu = durasi penuh in→out; hari biasa = sejak 17:00
    $ot_min = calc_overtime_minutes_record($d, $r['in_time'], $r['out_time']);

    $fine = $late_h * LATE_FINE_PER_H;
    $otpay = round(($ot_min / 60) * intval($r['overtime_rate']));
    $net = $otpay - $fine;

    $tot_late_h += $late_h;
    $tot_fine += $fine;
    $tot_ot_min += $ot_min;
    $tot_otpay += $otpay;

    $detail[] = [
        'date' => $d,
        'in_time' => $r['in_time'],
        'out_time' => $r['out_time'],
        'late_h' => $late_h,
        'fine' => $fine,
        'ot_min' => $ot_min,
        'otpay' => $otpay,
        'net' => $net,
    ];
}

$summary = [
    'late_h' => $tot_late_h,
    'fine' => $tot_fine,
    'ot_min' => $tot_ot_min,
    'otpay' => $tot_otpay,
    'net' => $tot_otpay - $tot_fine
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Rekap Saya • <?= htmlspecialchars($_SESSION['name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Rekap Absensi Saya</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/pegawai.php') ?>">Presensi</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Filter Periode -->
        <form class="row gy-2 gx-3 align-items-end mb-3" method="get">
            <div class="col-md-3">
                <label class="form-label">Mode</label>
                <select name="mode" class="form-select" onchange="this.form.submit()">
                    <option value="month" <?= $mode === 'month' ? 'selected' : '' ?>>Bulanan</option>
                    <option value="week" <?= $mode === 'week' ? 'selected' : '' ?>>Mingguan (Senin–Minggu)</option>
                    <option value="day" <?= $mode === 'day' ? 'selected' : '' ?>>Harian</option>
                </select>
            </div>

            <?php if ($mode === 'month'): ?>
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <input type="month" name="bulan" class="form-control" value="<?= htmlspecialchars($bulan) ?>"
                        onchange="this.form.submit()">
                </div>
            <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label"><?= $mode === 'week' ? 'Tanggal Acuan Pekan' : 'Tanggal' ?></label>
                    <input type="date" name="tgl" class="form-control" value="<?= htmlspecialchars($tgl) ?>"
                        onchange="this.form.submit()">
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <button class="btn btn-primary w-100">Terapkan</button>
            </div>
        </form>

        <div class="alert alert-info py-2">
            <strong>Periode:</strong> <?= htmlspecialchars($period_label) ?>
        </div>

        <h5>Detail</h5>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Telat (jam)</th>
                        <th>Potongan</th>
                        <th>Lembur</th>
                        <th>Upah Lembur</th>
                        <th>Netto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$detail): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Tidak ada data</td>
                        </tr>
                    <?php else:
                        foreach ($detail as $d): ?>
                            <tr>
                                <td><?= fmt_date($d['date']) ?></td>
                                <td><?= fmt_time($d['in_time']) ?></td>
                                <td><?= fmt_time($d['out_time']) ?></td>
                                <td><?= $d['late_h'] ?></td>
                                <td><?= number_format($d['fine'], 0, ',', '.') ?></td>
                                <td><?= fmt_duration_hm($d['ot_min']) ?></td>
                                <td><?= number_format($d['otpay'], 0, ',', '.') ?></td>
                                <td><strong><?= number_format($d['net'], 0, ',', '.') ?></strong></td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <h5 class="mt-4">Ringkasan</h5>
        <table class="table table-sm table-bordered" style="max-width:600px">
            <tr>
                <th>Total Telat (jam)</th>
                <td><?= $summary['late_h'] ?></td>
            </tr>
            <tr>
                <th>Total Potongan</th>
                <td><?= number_format($summary['fine'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <th>Total Lembur</th>
                <td><?= fmt_duration_hm($summary['ot_min']) ?></td>
            </tr>
            <tr>
                <th>Total Upah Lembur</th>
                <td><?= number_format($summary['otpay'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <th><strong>Netto</strong></th>
                <td><strong><?= number_format($summary['net'], 0, ',', '.') ?></strong></td>
            </tr>
        </table>
    </div>
</body>

</html>