<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/mail.php';
require_role('pegawai');

$uid = me_id();

// ambil data user (untuk info nama/ID di email)
$stUser = pdo()->prepare("SELECT employee_id, name FROM users WHERE id=? LIMIT 1");
$stUser->execute([$uid]);
$user = $stUser->fetch();
$empId   = $user ? ($user['employee_id'] ?? '') : '';
$empName = $user ? ($user['name'] ?? '') : '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajukan'])) {
    $amount = max(0, intval(str_replace(['.',',',' '], '', $_POST['amount'] ?? '0')));
    $note   = trim($_POST['note'] ?? '');

    if ($amount < 1) {
        flash_set('error', 'Nominal kasbon tidak valid.');
        header('Location: '.url('/pegawai_kasbon.php')); exit;
    }

    // simpan kasbon
    $st = pdo()->prepare("INSERT INTO cash_advances(user_id,amount,note,status,requested_at) VALUES(?,?,?,?,?)");
    $st->execute([
        $uid,
        $amount,
        $note,
        'pending',
        date('Y-m-d H:i:s')
    ]);

    $kasbonId = (int) pdo()->lastInsertId();

    // ambil data user untuk info nama/ID
    $stUser = pdo()->prepare("SELECT employee_id, name FROM users WHERE id=? LIMIT 1");
    $stUser->execute([$uid]);
    $user = $stUser->fetch();
    $empId   = $user ? ($user['employee_id'] ?? '') : '';
    $empName = $user ? ($user['name'] ?? '') : '';

    // 🔔 KIRIM NOTIFIKASI (EMAIL + TELEGRAM)
    if ($empName || $empId) {
        notify_admin_kasbon_new($empName, $empId, $amount, $note, $kasbonId);
    }

    flash_set('success','Pengajuan kasbon terkirim. Menunggu verifikasi Admin.');
    header('Location: '.url('/pegawai_kasbon.php')); exit;
}

// ambil daftar kasbon saya (terbaru dulu)
$st = pdo()->prepare("SELECT * FROM cash_advances WHERE user_id=? ORDER BY id DESC");
$st->execute([$uid]);
$rows = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="RelayLab - SuperApp">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- The above 4 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <meta name="theme-color" content="#264655">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

    <!-- Title -->
    <title>RelayLab - Kasbon</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/img/icon-96x96.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/img/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="assets/img/icon-167x167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/icon-180x180.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/viewerjs@1.11.7/dist/viewer.min.css">
    <script src="https://cdn.jsdelivr.net/npm/viewerjs@1.11.7/dist/viewer.min.js"></script>

    <!-- Style CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
</head>

<body>

    <!-- Preloader -->
    <div id="preloader">
        <div class="spinner-grow text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Internet Connection Status -->
    <div class="internet-connection-status" id="internetStatus"></div>

    <!-- Header Area-->
    <div class="header-area" id="headerArea">
        <div class="container">
            <!-- Header Content-->
            <div
                class="header-content header-style-four position-relative d-flex align-items-center justify-content-between">
                <!-- Back Button-->
                <div class="back-button">

                </div>

                <!-- Page Title-->
                <div class="page-heading">
                    <h6 class="mb-0">Kasbon</h6>
                </div>

                <!-- User Profile-->
                <div class="user-profile-wrapper">
                </div>
            </div>
        </div>
    </div>

    <div class="page-content-wrapper py-3">
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <h5 class="">Ajukan Kasbon</h5>
                    <form method="post" class="row g-2" autocomplete="off">
                        <div class="col-12">
                            <label class="form-label">Nominal</label>
                            <input type="number" class="form-control" name="amount" min="1" placeholder="contoh: 200000"
                                required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan (opsional)</label>
                            <input type="text" class="form-control" name="note" maxlength="255"
                                placeholder="Keperluan kasbon">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100" name="ajukan" value="1">Ajukan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="pt-3"></div>
        <div class="container">
            <div class="card">
                <div class="card-body">
                    <h5 class="">Riwayat Pengajuan</h5>
                    <div class="table-responsive">
                        <table class="table text-center">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Diajukan</th>
                                    <th>Diputuskan</th>
                                    <th>Bukti</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= (int) $r['id'] ?></td>
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
                                        <td>
                                            <?php if ($r['status'] === 'approved' && !empty($r['proof_file'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="showProof('<?= htmlspecialchars($r['proof_file']) ?>')">
                                                    Lihat
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($r['admin_note'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach;
                                if (!$rows): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada pengajuan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Footer Nav -->
    <div class="footer-nav-area" id="footerNav">
        <div class="container px-0">
            <!-- Footer Content -->
            <div class="footer-nav position-relative">
                <ul class="h-100 d-flex align-items-center justify-content-between ps-0">
                    <li>
                        <a href="/pegawai.php">
                            <i class="bi bi-house"></i>
                            <span>Beranda</span>
                        </a>
                    </li>

                    <li>
                        <a href="/pegawai_rekap.php">
                            <i class="bi bi-calendar2-check"></i>
                            <span>Rekap</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="/settings.php">
                            <i class="bi bi-person"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!--   <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Kasbon Saya</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/pegawai.php') ?>">Presensi</a>
                <a class="btn btn-outline-primary" href="<?= url('/pegawai_rekap.php') ?>">Rekap</a>
                <a class="btn btn-outline-primary" href="<?= url('/pegawai_password.php') ?>">Ubah Password</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">

       <div class="row g-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Ajukan Kasbon</div>
                    <div class="card-body">
                        <form method="post" class="row g-2" autocomplete="off">
                            <div class="col-12">
                                <label class="form-label">Nominal</label>
                                <input type="number" class="form-control" name="amount" min="1"
                                    placeholder="contoh: 200000" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan (opsional)</label>
                                <input type="text" class="form-control" name="note" maxlength="255"
                                    placeholder="Keperluan kasbon">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100" name="ajukan" value="1">Ajukan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Riwayat Pengajuan</div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Diajukan</th>
                                    <th>Diputuskan</th>
                                    <th>Bukti</th>
                                    <th>Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= (int) $r['id'] ?></td>
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
                                        <td>
                                            <?php if ($r['status'] === 'approved' && !empty($r['proof_file'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="showProof('<?= htmlspecialchars($r['proof_file']) ?>')">
                                                    Lihat
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($r['admin_note'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach;
                                if (!$rows): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada pengajuan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if ($__flash = flash_get()): ?>
        <script>
            const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            const FLASH = <?= json_encode($__flash, JSON_UNESCAPED_UNICODE) ?>;
            Toast.fire({ icon: (FLASH && FLASH.type) ? FLASH.type : 'info', title: (FLASH && FLASH.text) ? FLASH.text : '' });
        </script>
    <?php endif; ?>
    <script>
        function showProof(url) {
            if (!url) {
                if (window.Swal) {
                    Swal.fire({ icon: 'info', title: 'Bukti tidak tersedia' });
                } else {
                    alert('Bukti tidak tersedia');
                }
                return;
            }

            const isImage = /\.(jpe?g|png|webp)$/i.test(url);
            const isPdf = /\.pdf$/i.test(url);

            if (isImage) {
                // Buat elemen <img> baru khusus untuk viewer ini
                const img = new Image();
                img.src = url;
                img.alt = 'Bukti Transfer';

                // Bikin viewer baru setiap kali, lalu destroy saat ditutup
                let viewer = new Viewer(img, {
                    toolbar: true,
                    navbar: false,
                    title: false,
                    fullscreen: true,
                    movable: true,
                    zoomable: true,
                    rotatable: false,
                    scalable: false,
                    transition: true,
                    hidden() {
                        viewer.destroy();
                        viewer = null;
                    }
                });

                viewer.show();
            } else if (isPdf) {
                // Untuk PDF masih simpel: buka di tab baru
                window.open(url, '_blank');
            } else {
                if (window.Swal) {
                    Swal.fire({ icon: 'info', title: 'Tipe file tidak didukung' });
                } else {
                    alert('Tipe file tidak didukung');
                }
            }
        }
    </script>

</body>

</html>