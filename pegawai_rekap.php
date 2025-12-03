<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_once __DIR__ . '/app/ot.php';
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

    $late_h = $in ? calc_late_hours($in) : 0;

    // LEMBUR: pakai fungsi record-aware (hari biasa vs Minggu)
    $ot_min = calc_overtime_minutes_record($d, $r['in_time'], $r['out_time']);

    // MULTIPLIER: per user + per tanggal
    $mult = ot_get_multiplier($uid, $d);

    $fine = $late_h * LATE_FINE_PER_H;
    $otpay = round(($ot_min / 60) * intval($r['overtime_rate']) * $mult);
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
        'mult' => $mult,
        'otpay' => $otpay,
        'net' => $net,
    ];
}

$summary = [
    'late_h' => $tot_late_h,
    'fine' => $tot_fine,
    'ot_min' => $tot_ot_min,
    'otpay' => $tot_otpay,
    'net' => $tot_otpay - $tot_fine,
];
?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="RelayLab - SuperApp">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="theme-color" content="#264655">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

    <title>RelayLab - Presensi</title>

    <link rel="icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/img/icon-96x96.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/img/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="assets/img/icon-167x167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/icon-180x180.png">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">

</head>

<body>

    <div id="preloader">
        <div class="spinner-grow text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="internet-connection-status" id="internetStatus"></div>

    <div class="header-area" id="headerArea">
        <div class="container">
            <div
                class="header-content header-style-four position-relative d-flex align-items-center justify-content-between">
                <div class="back-button"></div>
                <div class="page-heading">
                    <h6 class="mb-0">Rekap</h6>
                </div>
                <div class="user-profile-wrapper"></div>
            </div>
        </div>
    </div>

    <div class="page-content-wrapper py-3">
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <!-- Filter Periode -->
                    <form class="row gy-2 gx-3 align-items-end mb-3" method="get">
                        <div class="col-md-3">
                            <label class="form-label">Mode</label>
                            <select name="mode" class="form-select" onchange="this.form.submit()">
                                <option value="month" <?= $mode === 'month' ? 'selected' : '' ?>>Bulanan</option>
                                <option value="week" <?= $mode === 'week' ? 'selected' : '' ?>>Mingguan (Senin–Minggu)
                                </option>
                                <option value="day" <?= $mode === 'day' ? 'selected' : '' ?>>Harian</option>
                            </select>
                        </div>

                        <?php if ($mode === 'month'): ?>
                            <div class="col-md-3">
                                <label class="form-label">Bulan</label>
                                <input type="month" name="bulan" class="form-control"
                                    value="<?= htmlspecialchars($bulan) ?>" onchange="this.form.submit()">
                            </div>
                        <?php else: ?>
                            <div class="col-md-3">
                                <label
                                    class="form-label"><?= $mode === 'week' ? 'Tanggal Acuan Pekan' : 'Tanggal' ?></label>
                                <input type="date" name="tgl" class="form-control" value="<?= htmlspecialchars($tgl) ?>"
                                    onchange="this.form.submit()">
                            </div>
                        <?php endif; ?>
                    </form>
                    <div class="alert alert-info">
                        <strong>Periode:</strong> <?= htmlspecialchars($period_label) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-3"></div>

        <div class="container">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-center">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Tanggal</th>
                                    <th class="text-nowrap">Masuk</th>
                                    <th class="text-nowrap">Keluar</th>
                                    <th class="text-nowrap">Telat (jam)</th>
                                    <th class="text-nowrap">Potongan</th>
                                    <th class="text-nowrap">Lembur</th>
                                    <th class="text-nowrap">Dikali</th>
                                    <th class="text-nowrap">Upah Lembur</th>
                                    <th class="text-nowrap">Netto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$detail): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Tidak ada data</td>
                                    </tr>
                                <?php else:
                                    foreach ($detail as $d): ?>
                                        <tr>
                                            <td class="text-nowrap"><?= fmt_date($d['date']) ?></td>
                                            <td class="text-nowrap"><?= fmt_time($d['in_time']) ?></td>
                                            <td class="text-nowrap"><?= fmt_time($d['out_time']) ?></td>
                                            <td class="text-nowrap"><?= $d['late_h'] ?></td>
                                            <td class="text-nowrap"><?= number_format($d['fine'], 0, ',', '.') ?></td>
                                            <td class="text-nowrap"><?= fmt_duration_hm($d['ot_min']) ?></td>
                                            <td class="text-nowrap">
                                                x<?= rtrim(rtrim(number_format($d['mult'], 2, ',', '.'), '0'), ',') ?>
                                            </td>
                                            <td class="text-nowrap"><?= number_format($d['otpay'], 0, ',', '.') ?></td>
                                            <td class="text-nowrap">
                                                <strong><?= number_format($d['net'], 0, ',', '.') ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="pt-3"></div>

        <div class="container">
            <div class="card">
                <div class="card-body">
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
            </div>
        </div>

    </div>

    <!-- Footer Nav -->
    <div class="footer-nav-area" id="footerNav">
        <div class="container px-0">
            <div class="footer-nav position-relative">
                <ul class="h-100 d-flex align-items-center justify-content-between ps-0">
                    <li>
                        <a href="<?= url('/pegawai.php') ?>">
                            <i class="bi bi-house"></i>
                            <span>Beranda</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="<?= url('/pegawai_rekap.php') ?>">
                            <i class="bi bi-calendar2-check"></i>
                            <span>Rekap</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= url('/settings.php') ?>">
                            <i class="bi bi-person"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- All JavaScript Files -->
    <script src="assets/js/pwa.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/slideToggle.min.js"></script>
    <script src="assets/js/internet-status.js"></script>
    <script src="assets/js/tiny-slider.js"></script>
    <script src="assets/js/venobox.min.js"></script>
    <script src="assets/js/countdown.js"></script>
    <script src="assets/js/rangeslider.min.js"></script>
    <script src="assets/js/vanilla-dataTables.min.js"></script>
    <script src="assets/js/index.js"></script>
    <script src="assets/js/imagesloaded.pkgd.min.js"></script>
    <script src="assets/js/isotope.pkgd.min.js"></script>
    <script src="assets/js/dark-rtl.js"></script>
    <script src="assets/js/active.js"></script>
</body>

</html>