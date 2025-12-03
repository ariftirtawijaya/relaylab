<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_once __DIR__ . '/app/ot.php';
require_role('admin');

$mode = $_GET['mode'] ?? 'month';               // month|week|day
$bulan = preg_replace('/[^0-9\-]/', '', $_GET['bulan'] ?? date('Y-m')); // untuk mode=month
$tgl = preg_replace('/[^0-9\-]/', '', $_GET['tgl'] ?? date('Y-m-d'));   // untuk mode=week/day
$emp = trim($_GET['emp'] ?? '');               // employee_id (opsional)

// Tentukan rentang periode
[$start, $end, $period_label] = period_range($mode, $bulan, $tgl);

// Ambil semua pegawai (untuk filter)
$users = pdo()->query("SELECT id, employee_id, name FROM users WHERE role='pegawai' ORDER BY name")->fetchAll();

// Build WHERE
$params = [$start->format('Y-m-d'), $end->format('Y-m-d')];
$where = "a.work_date >= ? AND a.work_date < ?";

if ($emp !== '') {
  $stU = pdo()->prepare("SELECT id FROM users WHERE employee_id=? LIMIT 1");
  $stU->execute([$emp]);
  $target = $stU->fetch();
  if ($target) {
    $where .= " AND a.user_id = ?";
    $params[] = $target['id'];
  }
}

$sql = "SELECT a.*, u.employee_id, u.name, u.overtime_rate
        FROM attendance a
        JOIN users u ON u.id=a.user_id
        WHERE $where
        ORDER BY u.name, a.work_date";
$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$detail = [];
$summary = [];

foreach ($rows as $r) {
  $d = $r['work_date'];
  $in = $r['in_time'] ? new DateTime($r['in_time']) : null;
  $out = $r['out_time'] ? new DateTime($r['out_time']) : null;

  // TELAT: versi baru pakai menit
  $late_min = $in ? calc_late_minutes($in) : 0;

  // LEMBUR: pakai fungsi record-aware (hari biasa vs Minggu)
  $ot_min = calc_overtime_minutes_record($d, $r['in_time'], $r['out_time']);

  // MULTIPLIER: per user + per tanggal (dari tabel overtime_multiplier)
  $mult = ot_get_multiplier((int) $r['user_id'], $d);

  // Potongan telat
  $fine = $in ? calc_late_fine($in) : 0;

  $otpay = round(($ot_min / 60) * intval($r['overtime_rate']) * $mult);
  $net = $otpay - $fine;

  $key = $r['employee_id'] . '|' . $r['name'];
  if (!isset($summary[$key])) {
    $summary[$key] = [
      'late_min' => 0,
      'fine' => 0,
      'ot_min' => 0,
      'otpay' => 0,
      'net' => 0,
    ];
  }
  $summary[$key]['late_min'] += $late_min;
  $summary[$key]['fine'] += $fine;
  $summary[$key]['ot_min'] += $ot_min;
  $summary[$key]['otpay'] += $otpay;
  $summary[$key]['net'] += $net;

  $detail[] = [
    'employee_id' => $r['employee_id'],
    'name' => $r['name'],
    'date' => $d,
    'in_time' => $r['in_time'] ?: null,
    'out_time' => $r['out_time'] ?: null,
    'late_min' => $late_min,
    'fine' => $fine,
    'ot_min' => $ot_min,
    'mult' => $mult,
    'otpay' => $otpay,
    'net' => $net,
  ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Admin • Rekap (Bulan/Minggu/Hari)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
  <nav class="navbar navbar-light bg-light">
    <div class="container">
      <span class="navbar-brand">Admin • Rekap</span>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= url('/admin_employees.php') ?>">Kelola Pegawai</a>
        <a class="btn btn-outline-primary" href="<?= url('/admin_absen.php') ?>">Absenkan</a>
        <a class="btn btn-outline-primary" href="<?= url('/admin_ot_multiplier.php') ?>">Multiplier Lembur</a>
        <a class="btn btn-outline-primary" href="<?= url('/admin_time.php') ?>">Simulasi Waktu</a>
        <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <?php if (app_sim_active()): ?>
      <div class="alert alert-warning">
        <strong>Mode Simulasi Aktif.</strong>
        Waktu sistem: <?= htmlspecialchars(app_now()->format('d-m-Y H:i:s')) ?>
      </div>
    <?php endif; ?>

    <!-- Filter Periode -->
    <form class="row gy-2 gx-3 align-items-end mb-3" method="get">
      <div class="col-md-2">
        <label class="form-label">Mode</label>
        <select name="mode" class="form-select" onchange="this.form.submit()">
          <option value="month" <?= $mode === 'month' ? 'selected' : '' ?>>Bulanan</option>
          <option value="week" <?= $mode === 'week' ? 'selected' : '' ?>>Mingguan (Senin–Minggu)</option>
          <option value="day" <?= $mode === 'day' ? 'selected' : '' ?>>Harian</option>
        </select>
        <small class="text-muted">Bulanan/Mingguan/Harian</small>
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
          <?php if ($mode === 'week'): ?>
            <small class="text-muted"><?= htmlspecialchars($period_label) ?></small>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="col-md-4">
        <label class="form-label">Pegawai (opsional)</label>
        <input class="form-control" list="list-emp" name="emp" value="<?= htmlspecialchars($emp) ?>"
          placeholder="Employee ID">
        <datalist id="list-emp">
          <?php foreach ($users as $u): ?>
            <option value="<?= $u['employee_id'] ?>"><?= $u['employee_id'] ?> — <?= $u['name'] ?></option>
          <?php endforeach; ?>
        </datalist>
        <small class="text-muted">Kosongkan untuk semua pegawai.</small>
      </div>

      <div class="col-md-3">
        <button class="btn btn-primary w-100">Terapkan</button>
      </div>
    </form>

    <div class="alert alert-info py-2">
      <strong>Periode:</strong> <?= htmlspecialchars($period_label) ?>
    </div>

    <h5 class="mb-2">Detail</h5>
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Tanggal</th>
            <th>In</th>
            <th>Out</th>
            <th>Telat</th>
            <th>Pot. Telat</th>
            <th>Lembur</th>
            <th>Mult</th>
            <th>Upah Lembur</th>
            <th>Net</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$detail): ?>
            <tr>
              <td colspan="11" class="text-center text-muted">Tidak ada data.</td>
            </tr>
          <?php else:
            foreach ($detail as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['employee_id']) ?></td>
                <td><?= htmlspecialchars($d['name']) ?></td>
                <td><?= fmt_date($d['date']) ?></td>
                <td><?= fmt_time($d['in_time']) ?></td>
                <td><?= fmt_time($d['out_time']) ?></td>
                <td><?= fmt_minutes_jam_menit($d['late_min']) ?></td>
                <td><?= number_format($d['fine'], 0, ',', '.') ?></td>
                <td><?= fmt_duration_hm($d['ot_min']) ?></td>
                <td>x<?= rtrim(rtrim(number_format($d['mult'], 2, ',', '.'), '0'), ',') ?></td>
                <td><?= number_format($d['otpay'], 0, ',', '.') ?></td>
                <td><strong><?= number_format($d['net'], 0, ',', '.') ?></strong></td>
              </tr>
            <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <h5 class="mt-4 mb-2">Ringkasan per Pegawai</h5>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Total Telat</th>
            <th>Total Potongan</th>
            <th>Total Lembur</th>
            <th>Total Upah Lembur</th>
            <th><strong>Netto</strong></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$summary): ?>
            <tr>
              <td colspan="7" class="text-center text-muted">Tidak ada data.</td>
            </tr>
          <?php else:
            foreach ($summary as $k => $s):
              list($eid, $nm) = explode('|', $k, 2); ?>
              <tr>
                <td><?= htmlspecialchars($eid) ?></td>
                <td><?= htmlspecialchars($nm) ?></td>
                <td><?= fmt_minutes_jam_menit($s['late_min']) ?></td>
                <td><?= number_format($s['fine'], 0, ',', '.') ?></td>
                <td><?= fmt_duration_hm($s['ot_min']) ?></td>
                <td><?= number_format($s['otpay'], 0, ',', '.') ?></td>
                <td><strong><?= number_format($s['net'], 0, ',', '.') ?></strong></td>
              </tr>
            <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php if ($__flash = flash_get()): ?>
    <script>
      const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
      Toast.fire({ icon: '<?= htmlspecialchars($__flash['type']) ?>', title: '<?= htmlspecialchars($__flash['text']) ?>' });
    </script>
  <?php endif; ?>
</body>

</html>