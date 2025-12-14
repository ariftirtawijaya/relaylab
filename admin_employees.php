<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_role('admin');

// Tambah/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $employee_id = trim($_POST['employee_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] === 'admin' ? 'admin' : 'pegawai';
    $overtime_rate = max(0, intval($_POST['overtime_rate'] ?? 0));
    $salary = max(0, intval($_POST['salary'] ?? 0));
    $max_cash_pct = max(0, min(100, intval($_POST['max_cash_advance_pct'] ?? 20)));
    $password = $_POST['password'] ?? '';

    if (!$employee_id || !$name) {
        flash_set('warning', 'ID dan Nama wajib diisi.');
        header('Location: ' . url('/admin_employees.php'));
        exit;
    }

    if ($id > 0) {
        if ($password !== '') {
            $st = pdo()->prepare("
                UPDATE users 
                SET employee_id=?, name=?, role=?, overtime_rate=?, salary=?, max_cash_advance_pct=?, password_hash=? 
                WHERE id=?
            ");
            $st->execute([
                $employee_id,
                $name,
                $role,
                $overtime_rate,
                $salary,
                $max_cash_pct,
                password_hash($password, PASSWORD_DEFAULT),
                $id
            ]);
        } else {
            $st = pdo()->prepare("
                UPDATE users 
                SET employee_id=?, name=?, role=?, overtime_rate=?, salary=?, max_cash_advance_pct=? 
                WHERE id=?
            ");
            $st->execute([
                $employee_id,
                $name,
                $role,
                $overtime_rate,
                $salary,
                $max_cash_pct,
                $id
            ]);
        }
        flash_set('success', 'Pegawai diperbarui.');
    } else {
        $st = pdo()->prepare("
            INSERT INTO users(employee_id,name,role,password_hash,overtime_rate,salary,max_cash_advance_pct) 
            VALUES(?,?,?,?,?,?,?)
        ");
        $st->execute([
            $employee_id,
            $name,
            $role,
            password_hash($password, PASSWORD_DEFAULT),
            $overtime_rate,
            $salary,
            $max_cash_pct
        ]);
        flash_set('success', 'Pegawai ditambahkan.');
    }
    header('Location: ' . url('/admin_employees.php'));
    exit;
}

$rows = pdo()->query("SELECT * FROM users ORDER BY role='admin' DESC, name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Pegawai</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Kelola Pegawai</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/admin_absen.php') ?>">Absenkan</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_rekap.php') ?>">Rekap Bulanan</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_time.php') ?>">Simulasi Waktu</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_locations.php') ?>">Lokasi</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_kasbon.php') ?>">Kasbon</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_leave.php') ?>">Izin / Sakit</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Tambah/Update Pegawai</h5>
                        <form method="post" class="row g-2">
                            <input type="hidden" name="id" id="f_id">
                            <div class="col-6">
                                <label class="form-label">ID Pegawai</label>
                                <input name="employee_id" id="f_employee_id" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Nama</label>
                                <input name="name" id="f_name" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Role</label>
                                <select name="role" id="f_role" class="form-select">
                                    <option value="pegawai">Pegawai</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Rate Lembur / jam (Rp)</label>
                                <input type="number" name="overtime_rate" id="f_overtime_rate" class="form-control"
                                    min="0" value="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Gaji Bulanan (Rp)</label>
                                <input type="number" name="salary" id="f_salary" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Maks. Kasbon per Bulan (%)</label>
                                <input type="number" name="max_cash_advance_pct" id="f_max_cash_advance_pct"
                                    class="form-control" min="0" max="100" value="20">
                                <small class="text-muted">Default 20% dari gaji.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password (kosongkan jika tidak ubah)</label>
                                <input type="password" name="password" id="f_password" class="form-control">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar Pegawai</h5>
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>Rate/jam</th>
                                    <th>Gaji</th>
                                    <th>Limit Kasbon</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['employee_id']) ?></td>
                                        <td><?= htmlspecialchars($r['name']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $r['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                                                <?= htmlspecialchars($r['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($r['overtime_rate'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format((int) ($r['salary'] ?? 0), 0, ',', '.') ?></td>
                                        <td><?= (int) ($r['max_cash_advance_pct'] ?? 20) ?>%</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick='fillForm(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                                            <form method="post" action="<?= url('/admin_employee_delete.php') ?>"
                                                onsubmit="return confirm('PERINGATAN!\n\nSemua data pegawai ini (absen, izin, kasbon, lembur) akan DIHAPUS PERMANEN.\n\nLanjutkan?')"
                                                style="display:inline">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button class="btn btn-sm btn-danger">
                                                    Hapus
                                                </button>
                                            </form>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function fillForm(r) {
            document.getElementById('f_id').value = r.id;
            document.getElementById('f_employee_id').value = r.employee_id;
            document.getElementById('f_name').value = r.name;
            document.getElementById('f_role').value = r.role;
            document.getElementById('f_overtime_rate').value = r.overtime_rate ?? 0;
            document.getElementById('f_salary').value = r.salary ?? 0;
            document.getElementById('f_max_cash_advance_pct').value = r.max_cash_advance_pct ?? 20;
            document.getElementById('f_password').value = '';
        }
        function confirmDel(id) {
            Swal.fire({
                icon: 'warning',
                title: 'Hapus pegawai ini?',
                text: 'Data absensi terkait juga akan ikut terhapus.',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location = '<?= url('/admin_employees.php') ?>?del=' + id;
                }
            });
            return false;
        }
        <?php if ($__flash = flash_get()): ?>
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
            Toast.fire({ icon: '<?= htmlspecialchars($__flash['type']) ?>', title: '<?= htmlspecialchars($__flash['text']) ?>' });
        <?php endif; ?>
    </script>
</body>

</html>