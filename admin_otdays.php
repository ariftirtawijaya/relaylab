<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/ot.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tambah / update
    if (isset($_POST['save'])) {
        $date = trim($_POST['work_date'] ?? '');
        $mult = floatval(str_replace(',', '.', $_POST['multiplier'] ?? '1'));
        $note = trim($_POST['note'] ?? '');

        if (!$date || !$mult || $mult <= 0) {
            flash_set('error', 'Tanggal/multiplier tidak valid.');
            header('Location: ' . url('/admin_otdays.php'));
            exit;
        }
        $st = pdo()->prepare("
            INSERT INTO overtime_days(work_date, multiplier, note)
            VALUES(?,?,?)
            ON DUPLICATE KEY UPDATE multiplier=VALUES(multiplier), note=VALUES(note)
        ");
        $st->execute([$date, $mult, $note]);
        flash_set('success', "Saved: $date x{$mult}");
        header('Location: ' . url('/admin_otdays.php'));
        exit;
    }

    // Hapus
    if (isset($_POST['delete'])) {
        $date = trim($_POST['work_date'] ?? '');
        if ($date) {
            $st = pdo()->prepare("DELETE FROM overtime_days WHERE work_date=?");
            $st->execute([$date]);
            flash_set('success', "Dihapus: $date");
        }
        header('Location: ' . url('/admin_otdays.php'));
        exit;
    }
}

$rows = ot_list();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Hari Lembur Multiplier</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Hari Lembur Multiplier</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/admin_rekap.php') ?>">Rekap</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_locations.php') ?>">Lokasi</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_employees.php') ?>">Pegawai</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Tambah / Ubah</div>
                    <div class="card-body">
                        <form method="post" class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Tanggal</label>
                                <input type="date" class="form-control" name="work_date" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Multiplier (x)</label>
                                <input type="number" step="0.1" min="0.1" class="form-control" name="multiplier"
                                    value="2.0" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan (opsional)</label>
                                <input type="text" class="form-control" name="note" placeholder="Mis. Sprint deadline">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100" name="save" value="1">Simpan</button>
                            </div>
                        </form>
                        <small class="text-muted d-block mt-2">Tanggal yang sama akan di-UPDATE jika disimpan
                            ulang.</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Daftar Hari Multiplier</div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Multiplier</th>
                                    <th>Catatan</th>
                                    <th style="width:120px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= fmt_date($r['work_date']) ?></td>
                                        <td>x<?= htmlspecialchars($r['multiplier']) ?></td>
                                        <td><?= htmlspecialchars($r['note'] ?? '') ?></td>
                                        <td>
                                            <form method="post" class="d-inline"
                                                onsubmit="return confirm('Hapus tanggal ini?')">
                                                <input type="hidden" name="work_date"
                                                    value="<?= htmlspecialchars($r['work_date']) ?>">
                                                <button class="btn btn-sm btn-outline-danger" name="delete"
                                                    value="1">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach;
                                if (!$rows): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="small text-muted">Hari tanpa entri multiplier = x1 (normal).</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($__flash = flash_get()): ?>
        <script>
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
            const FLASH = <?= json_encode($__flash, JSON_UNESCAPED_UNICODE) ?>;
            Toast.fire({ icon: (FLASH && FLASH.type) ? FLASH.type : 'info', title: (FLASH && FLASH.text) ? FLASH.text : '' });
        </script>
    <?php endif; ?>
</body>

</html>