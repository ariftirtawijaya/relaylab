<?php
// admin_ot_multiplier.php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_role('admin');

$pdo = pdo();

// Tanggal yang sedang diedit
$tgl = preg_replace('/[^0-9\-]/', '', $_REQUEST['tgl'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
    $tgl = date('Y-m-d');
}

// Proses POST: simpan multiplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tglPost = preg_replace('/[^0-9\-]/', '', $_POST['tgl'] ?? $tgl);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglPost)) {
        $tglPost = $tgl;
    }

    $mults = $_POST['mult'] ?? [];

    try {
        $pdo->beginTransaction();

        foreach ($mults as $uidStr => $valStr) {
            $userId = (int) $uidStr;
            $raw = trim((string) $valStr);

            // kosong → anggap default 1.0 → hapus jika ada
            if ($raw === '') {
                $del = $pdo->prepare("DELETE FROM overtime_multiplier WHERE user_id=? AND work_date=?");
                $del->execute([$userId, $tglPost]);
                continue;
            }

            // parsing float (support koma)
            $raw = str_replace(['.', ','], ['.', '.'], $raw); // kalau Kang mau titik ribuan, bisa diubah; sementara simple
            $val = (float) $raw;

            if ($val == 1.0) {
                // 1.0 = normal → tidak usah disimpan; hapus jika ada
                $del = $pdo->prepare("DELETE FROM overtime_multiplier WHERE user_id=? AND work_date=?");
                $del->execute([$userId, $tglPost]);
                continue;
            }

            // simpan / update
            $st = $pdo->prepare("
                INSERT INTO overtime_multiplier (user_id, work_date, multiplier)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE multiplier = VALUES(multiplier)
            ");
            $st->execute([$userId, $tglPost, $val]);
        }

        $pdo->commit();
        flash_set('success', 'Multiplier lembur berhasil disimpan untuk tanggal ' . fmt_date($tglPost));
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('admin_ot_multiplier save error: ' . $e->getMessage());
        flash_set('error', 'Gagal menyimpan multiplier. Silakan coba lagi.');
    }

    header('Location: ' . url('/admin_ot_multiplier.php?tgl=' . urlencode($tglPost)));
    exit;
}

// Ambil daftar pegawai
$st = $pdo->query("SELECT id, employee_id, name FROM users WHERE role='pegawai' ORDER BY name");
$employees = $st->fetchAll();

// Ambil multiplier yang sudah diset untuk tanggal ini
$st2 = $pdo->prepare("SELECT user_id, multiplier FROM overtime_multiplier WHERE work_date=?");
$st2->execute([$tgl]);
$rowsMult = $st2->fetchAll();
$multMap = [];
foreach ($rowsMult as $r) {
    $multMap[(int) $r['user_id']] = (float) $r['multiplier'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Multiplier Lembur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Multiplier Lembur</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/admin_rekap.php') ?>">Rekap</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_employees.php') ?>">Kelola Pegawai</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_absen.php') ?>">Absenkan</a>
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

        <div class="mb-3">
            <h4>Atur Multiplier Lembur per Pegawai</h4>
            <p class="text-muted mb-1">
                Pilih tanggal dan set multiplier untuk pegawai tertentu.
            </p>
            <ul class="text-muted small mb-0">
                <li>Biarkan kosong atau isi <strong>1</strong> untuk lembur normal (tanpa multiplier khusus).</li>
                <li>Isi <strong>2</strong> untuk 2x, <strong>1.5</strong> untuk 1.5x, <strong>3</strong> untuk 3x, dst.
                </li>
                <li>Isi <strong>0</strong> bila lembur hari itu <em>tidak dibayar</em> untuk pegawai tersebut.</li>
            </ul>
        </div>

        <form method="get" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tgl" class="form-control" value="<?= htmlspecialchars($tgl) ?>"
                    onchange="this.form.submit()">
            </div>
        </form>

        <form method="post">
            <input type="hidden" name="tgl" value="<?= htmlspecialchars($tgl) ?>">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px">No</th>
                            <th>ID</th>
                            <th>Nama Pegawai</th>
                            <th style="width:180px">Multiplier (x)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$employees): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada pegawai.</td>
                            </tr>
                        <?php else:
                            $no = 1;
                            foreach ($employees as $e):
                                $uid = (int) $e['id'];
                                $multV = $multMap[$uid] ?? 1.0;
                                $display = '';
                                if ($multV !== 1.0) {
                                    // format rapi: buang nol di belakang
                                    $display = rtrim(rtrim(number_format($multV, 2, ',', '.'), '0'), ',');
                                }
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($e['employee_id']) ?></td>
                                    <td><?= htmlspecialchars($e['name']) ?></td>
                                    <td>
                                        <input type="text" name="mult[<?= $uid ?>]" class="form-control form-control-sm"
                                            placeholder="kosong = x1" value="<?= htmlspecialchars($display) ?>">
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    Simpan Multiplier untuk <?= fmt_date($tgl) ?>
                </button>
            </div>
        </form>
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