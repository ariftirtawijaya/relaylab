<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/upload.php';
require_once __DIR__ . '/app/kasbon.php';
require_role('admin');

$adminId = me_id();

// ACC / Tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    // Ambil kasbon
    $st = pdo()->prepare("
        SELECT ca.*, u.name, u.salary, u.max_cash_advance_pct 
        FROM cash_advances ca 
        JOIN users u ON u.id=ca.user_id 
        WHERE ca.id=? LIMIT 1
    ");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        flash_set('error', 'Data kasbon tidak ditemukan.');
        header('Location: ' . url('/admin_kasbon.php'));
        exit;
    }

    // Tolak
    if (isset($_POST['reject'])) {
        $note = trim($_POST['admin_note'] ?? '');
        $st = pdo()->prepare("
            UPDATE cash_advances 
               SET status='rejected', decided_at=?, decided_by=?, admin_note=? 
             WHERE id=?
        ");
        $st->execute([date('Y-m-d H:i:s'), $adminId, $note, $id]);
        flash_set('success', 'Pengajuan ditolak.');
        header('Location: ' . url('/admin_kasbon.php'));
        exit;
    }

    // Setujui (wajib upload bukti)
    if (isset($_POST['approve'])) {
        $note = trim($_POST['admin_note'] ?? '');
        try {
            $proof = kasbon_upload_proof($_FILES['proof'] ?? [], $id);
            if (!$proof) {
                flash_set('error', 'Wajib unggah bukti transfer (JPG/PNG/WEBP/PDF).');
                header('Location: ' . url('/admin_kasbon.php'));
                exit;
            }
            $st = pdo()->prepare("
                UPDATE cash_advances 
                   SET status='approved', decided_at=?, decided_by=?, admin_note=?, proof_file=? 
                 WHERE id=?
            ");
            $st->execute([date('Y-m-d H:i:s'), $adminId, $note, $proof, $id]);
            flash_set('success', 'Pengajuan disetujui & bukti tersimpan.');
        } catch (Throwable $e) {
            flash_set('error', 'Gagal menyimpan bukti: ' . $e->getMessage());
        }
        header('Location: ' . url('/admin_kasbon.php'));
        exit;
    }
}

// filter status (opsional)
$filter = $_GET['status'] ?? 'pending';
$filter = in_array($filter, ['pending', 'approved', 'rejected', 'all'], true) ? $filter : 'pending';

$where = '';
$params = [];
if ($filter !== 'all') {
    $where = "WHERE ca.status=?";
    $params[] = $filter;
}

// list kasbon
$sql = "
    SELECT ca.*, u.name, u.employee_id, u.salary, u.max_cash_advance_pct
    FROM cash_advances ca
    JOIN users u ON u.id=ca.user_id
    $where
    ORDER BY ca.id DESC
";
$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Kasbon</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .w-proof {
            max-width: 220px
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Kasbon</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/admin_rekap.php') ?>">Rekap</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_employees.php') ?>">Pegawai</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_locations.php') ?>">Lokasi</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_otdays.php') ?>">Hari Multiplier</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <form class="row g-2 mb-3" method="get">
            <div class="col-auto">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua</option>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pegawai</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Diajukan</th>
                        <th>Diputuskan</th>
                        <th>Limit Bln Ini</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $salary = isset($r['salary']) ? (int) $r['salary'] : 0;
                        $pct = isset($r['max_cash_advance_pct']) ? (int) $r['max_cash_advance_pct'] : 20;
                        if ($pct < 0)
                            $pct = 0;
                        if ($pct > 100)
                            $pct = 100;

                        // bulan referensi: untuk approved → pakai bulan decided_at, untuk pending/rejected → bulan sekarang
                        if (!empty($r['decided_at'])) {
                            $ym = substr($r['decided_at'], 0, 7); // Y-m
                        } else {
                            $ym = date('Y-m');
                        }

                        $limit = $salary > 0 ? (int) floor($salary * $pct / 100) : 0;
                        $used = $salary > 0 ? kasbon_total_approved_month((int) $r['user_id'], $ym) : 0;
                        $remain = max(0, $limit - $used);

                        $willExceed = ($r['status'] === 'pending'
                            && $salary > 0
                            && (int) $r['amount'] > $remain);
                        ?>
                        <tr>
                            <td><?= (int) $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['employee_id'] . ' — ' . $r['name']) ?></td>
                            <td>Rp <?= number_format((int) $r['amount'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <span class="badge text-bg-secondary">Pending</span>
                                <?php elseif ($r['status'] === 'approved'): ?>
                                    <span class="badge text-bg-success">Disetujui</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td><?= fmt_datetime($r['requested_at']) ?></td>
                            <td><?= $r['decided_at'] ? fmt_datetime($r['decided_at']) : '-' ?></td>
                            <td class="small">
                                <?php if ($salary <= 0): ?>
                                    <span class="text-muted">Gaji blm diset</span>
                                <?php else: ?>
                                    <div><strong>Periode:</strong> <?= htmlspecialchars($ym) ?></div>
                                    <div>Gaji: Rp <?= number_format($salary, 0, ',', '.') ?></div>
                                    <div>Batas (<?= $pct ?>%): Rp <?= number_format($limit, 0, ',', '.') ?></div>
                                    <div>Terpakai: Rp <?= number_format($used, 0, ',', '.') ?></div>
                                    <div>Sisa: Rp <?= number_format($remain, 0, ',', '.') ?></div>
                                    <?php if ($willExceed): ?>
                                        <span class="badge bg-danger mt-1">
                                            Jika ACC akan melebihi limit
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="w-proof">
                                <?php if (!empty($r['proof_file'])): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($r['proof_file']) ?>"
                                        target="_blank">Lihat</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td style="min-width:260px">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="post" enctype="multipart/form-data" class="d-flex flex-column gap-1">
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <input type="text" class="form-control form-control-sm" name="admin_note"
                                            placeholder="Catatan admin (opsional)">
                                        <div class="d-flex gap-1">
                                            <input type="file" class="form-control form-control-sm" name="proof"
                                                accept=".jpg,.jpeg,.png,.webp,.pdf">
                                            <button class="btn btn-sm btn-success" name="approve" value="1">
                                                ACC + Upload
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" name="reject" value="1"
                                                onclick="return confirm('Tolak pengajuan ini?')">
                                                Tolak
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="small text-muted"><?= htmlspecialchars($r['admin_note'] ?? '') ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (!$rows): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">Tidak ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($__flash = flash_get()): ?>
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            const FLASH = <?= json_encode($__flash, JSON_UNESCAPED_UNICODE) ?>;
            Toast.fire({
                icon: (FLASH && FLASH.type) ? FLASH.type : 'info',
                title: (FLASH && FLASH.text) ? FLASH.text : ''
            });
        </script>
    <?php endif; ?>
</body>

</html>