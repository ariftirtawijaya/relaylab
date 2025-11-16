<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set'])) {
        $dl = trim($_POST['sim_datetime'] ?? ''); // datetime-local: YYYY-MM-DDTHH:MM
        if ($dl !== '') {
            $dl = str_replace('T', ' ', $dl);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dl))
                $dl .= ':00';
            try {
                $dt = new DateTime($dl);
                app_set_setting('sim_now', $dt->format('Y-m-d H:i:s'));
                flash_set('success', 'Waktu simulasi diaktifkan: ' . $dt->format('d-m-Y H:i:s'));
            } catch (Throwable $e) {
                flash_set('error', 'Format tanggal tidak valid.');
            }
        } else {
            flash_set('warning', 'Isi tanggal & jam simulasi.');
        }
        header('Location: ' . url('/admin_time.php'));
        exit;
    }
    if (isset($_POST['clear'])) {
        app_clear_setting('sim_now');
        flash_set('info', 'Waktu simulasi dimatikan.');
        header('Location: ' . url('/admin_time.php'));
        exit;
    }
}

$sim_now = app_get_setting('sim_now', null);
$server_now = (new DateTime('now'))->format('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Simulasi Waktu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Simulasi Waktu</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/admin_employees.php') ?>">Kelola Pegawai</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_absen.php') ?>">Absenkan</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_rekap.php') ?>">Rekap Bulanan</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Status</h5>
                <p class="mb-1">Waktu Server: <strong><?= fmt_datetime($server_now) ?></strong> (Asia/Jakarta)</p>
                <p class="mb-0">Waktu Simulasi: <strong><?= $sim_now ? fmt_datetime($sim_now) : '-' ?></strong></p>
                <small class="text-muted">Jika simulasi aktif, seluruh perhitungan absen memakai Waktu Simulasi.</small>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Atur Waktu Simulasi</h5>
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal & Jam (Asia/Jakarta)</label>
                        <input type="datetime-local" name="sim_datetime" class="form-control">
                        <small class="text-muted">Contoh: set 09:00 untuk uji Telat 1 jam; 18:00 untuk uji Lembur 1
                            jam.</small>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" name="set" value="1">Aktifkan Simulasi</button>
                        <button class="btn btn-outline-danger" name="clear" value="1">Matikan Simulasi</button>
                    </div>
                </form>
            </div>
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