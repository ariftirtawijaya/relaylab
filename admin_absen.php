<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_role('admin');

// Ambil semua pegawai
$employees = pdo()->query("SELECT id, employee_id, name FROM users WHERE role='pegawai' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';                      // 'in' | 'out'
    $date = trim($_POST['work_date'] ?? '');          // YYYY-MM-DD
    $ids = $_POST['ids'] ?? [];                      // array of user_id (int)

    // Validasi form
    if (!$act || !in_array($act, ['in', 'out'], true)) {
        flash_set('warning', 'Aksi tidak valid.');
        header('Location: ' . url('/admin_absen.php'));
        exit;
    }
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        flash_set('warning', 'Tanggal tidak valid.');
        header('Location: ' . url('/admin_absen.php'));
        exit;
    }
    if (!is_array($ids) || count($ids) === 0) {
        flash_set('warning', 'Pilih minimal satu pegawai.');
        header('Location: ' . url('/admin_absen.php'));
        exit;
    }

    // Timestamp yang dicatat = tanggal yang dipilih + jam:menit:detik dari app_now()
    $now = app_now();
    $stamp = new DateTime($date . ' ' . $now->format('H:i:s'));

    $updated = 0;
    $skipped = 0;
    $created = 0;

    try {
        pdo()->beginTransaction();
        $sel = pdo()->prepare("SELECT * FROM attendance WHERE user_id=? AND work_date=? FOR UPDATE");
        $ins = pdo()->prepare("INSERT INTO attendance(user_id,work_date,in_time,out_time) VALUES(?,?,NULL,NULL)");
        $updIn = pdo()->prepare("UPDATE attendance SET in_time=?  WHERE id=?");
        $updOut = pdo()->prepare("UPDATE attendance SET out_time=? WHERE id=?");

        foreach ($ids as $uid) {
            $uid = intval($uid);
            if ($uid <= 0)
                continue;

            // Lock row
            $sel->execute([$uid, $date]);
            $row = $sel->fetch();

            if (!$row) {
                $ins->execute([$uid, $date]);
                $created++;
                // Ambil row baru (last insert id tak perlu, karena kita butuh check kolom)
                $sel->execute([$uid, $date]);
                $row = $sel->fetch();
            }

            if ($act === 'in') {
                if (!empty($row['in_time'])) {
                    $skipped++;
                    continue;
                }
                $updIn->execute([$stamp->format('Y-m-d H:i:s'), $row['id']]);
                $updated++;
            } else { // 'out'
                if (empty($row['in_time'])) {
                    // jika belum ada in_time, kita tetap izinkan set out_time (opsional)
                    // Kalau mau paksa harus punya in_time dulu, ganti baris di atas jadi: $skipped++; continue;
                }
                if (!empty($row['out_time'])) {
                    $skipped++;
                    continue;
                }
                $updOut->execute([$stamp->format('Y-m-d H:i:s'), $row['id']]);
                $updated++;
            }
        }
        pdo()->commit();
    } catch (Throwable $e) {
        pdo()->rollBack();
        flash_set('error', 'Gagal memproses.');
        header('Location: ' . url('/admin_absen.php'));
        exit;
    }

    // Ringkasan hasil
    $aksiLabel = $act === 'in' ? 'Absen Masuk' : 'Absen Keluar';
    $msg = $aksiLabel . ': ' . $updated . ' sukses, ' . $skipped . ' dilewati, ' . $created . ' hari dibuat.';
    flash_set('success', $msg);
    header('Location: ' . url('/admin_absen.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Meng-absenkan Pegawai</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Meng-absenkan Pegawai</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/admin_employees.php') ?>">Kelola Pegawai</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_rekap.php') ?>">Rekap Bulanan</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_time.php') ?>">Simulasi Waktu</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (app_sim_active()): ?>
            <div class="alert alert-warning">
                <strong>Mode Simulasi Aktif.</strong>
                Waktu sistem saat ini: <?= htmlspecialchars(app_now()->format('d-m-Y H:i:s')) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Kerja</label>
                            <input type="date" name="work_date" class="form-control"
                                value="<?= app_now()->format('Y-m-d') ?>">
                            <small class="text-muted">Time stamp akan pakai jam <?= app_now()->format('H:i:s') ?> dari
                                waktu aplikasi.</small>
                        </div>
                        <div class="col-md-5 d-flex align-items-end gap-2">
                            <button name="act" value="in" class="btn btn-primary">Absenkan Masuk</button>
                            <button name="act" value="out" class="btn btn-success">Absenkan Keluar</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="chk_all"></th>
                                    <th>ID Pegawai</th>
                                    <th>Nama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $e): ?>
                                    <tr>
                                        <td><input type="checkbox" class="chk_item" name="ids[]" value="<?= $e['id'] ?>">
                                        </td>
                                        <td><?= htmlspecialchars($e['employee_id']) ?></td>
                                        <td><?= htmlspecialchars($e['name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$employees): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada pegawai.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <small class="text-muted d-block mt-2">
                        • Fitur ini **mengabaikan batas waktu** (08:00/17:00) dan langsung menetapkan waktu berdasarkan
                        jam aplikasi.<br>
                        • Untuk backdate, pilih tanggal yang diinginkan. Perhitungan telat/lembur akan otomatis dihitung
                        saat rekap.
                    </small>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('chk_all')?.addEventListener('change', function () {
            document.querySelectorAll('.chk_item').forEach(ch => ch.checked = this.checked);
        });
        <?php if ($__flash = flash_get()): ?>
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
            Toast.fire({ icon: '<?= htmlspecialchars($__flash['type']) ?>', title: '<?= htmlspecialchars($__flash['text']) ?>' });
        <?php endif; ?>
    </script>
</body>

</html>