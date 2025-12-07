<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/leave.php';
require_role('admin');

// Ambil semua pegawai
$users = pdo()->query("SELECT id, employee_id, name FROM users WHERE role='pegawai' ORDER BY name")->fetchAll();

// Jika filter tanggal dipilih
$tgl = $_GET['tgl'] ?? date('Y-m-d');

$rows = pdo()->prepare("
    SELECT l.*, u.employee_id, u.name 
    FROM leave_days l
    JOIN users u ON u.id = l.user_id
    WHERE l.leave_date = ?
    ORDER BY u.name
");
$rows->execute([$tgl]);
$list = $rows->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Set Izin/Sakit</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>

    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Izin & Sakit</span>
            <a class="btn btn-secondary" href="<?= url('/admin_employees.php') ?>">Dashboard</a>
        </div>
    </nav>

    <div class="container py-4">

        <h4>Atur Status Izin / Sakit Pegawai</h4>

        <form class="row g-3 mb-4" action="admin_leave_save.php" method="post">
            <div class="col-md-4">
                <label class="form-label">Pegawai</label>
                <select name="user_id" class="form-select" required>
                    <option value="">-- Pilih Pegawai --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>">
                            <?= $u['employee_id'] ?> — <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="leave_date" class="form-control" value="<?= $tgl ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Jenis</label>
                <select name="type" class="form-select" required>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label">Catatan (opsional)</label>
                <input type="text" name="note" class="form-control">
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary w-100 mt-3">Simpan</button>
            </div>
        </form>

        <h5>Status Izin / Sakit Tanggal <?= fmt_date($tgl) ?></h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Pegawai</th>
                        <th>Jenis</th>
                        <th>Catatan</th>
                        <th>Dibuat oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$list): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Tidak ada data.</td>
                        </tr>
                    <?php else:
                        foreach ($list as $l): ?>
                            <tr>
                                <td><?= $l['employee_id'] ?> — <?= htmlspecialchars($l['name']) ?></td>
                                <td><?= strtoupper($l['type']) ?></td>
                                <td><?= htmlspecialchars($l['note']) ?></td>
                                <td><?= $l['created_by'] ?></td>
                                <td>
                                    <a href="admin_leave_delete.php?id=<?= $l['id'] ?>&tgl=<?= $tgl ?>"
                                        class="btn btn-danger btn-sm" onclick="return confirm('Hapus status izin?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>

</html>