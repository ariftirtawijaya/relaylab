<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_once __DIR__ . '/app/geo.php';
require_once __DIR__ . '/app/settings.php';
require_role('pegawai');

$uid = me_id();
$now = app_now();              // hormati mode simulasi jika aktif
$ymd = $now->format('Y-m-d');

$geo_required = settings_get_bool('geo_enforce', false); // OPSI: wajib geolokasi atau tidak

// (opsional) info user
$me = pdo()->prepare("SELECT id, employee_id, name, overtime_rate FROM users WHERE id=? LIMIT 1");
$me->execute([$uid]);
$user = $me->fetch();

// data hari ini (sebelum proses)
$st = pdo()->prepare("SELECT * FROM attendance WHERE user_id=? AND work_date=? LIMIT 1");
$st->execute([$uid, $ymd]);
$today = $st->fetch();
$hasIn  = $today && !empty($today['in_time']);
$hasOut = $today && !empty($today['out_time']);

// izinkan aksi menurut status
$canCheckin  = !$hasIn;
$canCheckout = $hasIn && !$hasOut;

// === Proses aksi ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // normalisasi action agar aman walau submit via form.submit()
    $action = $_POST['action'] ?? null;
    if (!$action) {
        if (isset($_POST['checkin']))  $action = 'checkin';
        if (isset($_POST['checkout'])) $action = 'checkout';
    }

    // koordinat (boleh kosong jika geo tidak wajib)
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;

    if ($geo_required) {
        // wajib ada titik aktif
        $locs = geo_active_locations();
        if (!$locs) {
            flash_set('error', 'Lokasi presensi belum disetel admin.');
            header('Location: ' . url('/pegawai.php')); exit;
        }
        // wajib ada koordinat valid
        if (!$lat || !$lng) {
            flash_set('error', 'Lokasi tidak terdeteksi. Izinkan GPS lalu coba lagi.');
            header('Location: ' . url('/pegawai.php')); exit;
        }
        // validasi radius server-side
        $near = geo_nearest_ok($lat, $lng);
        if (!$near['ok']) {
            $nm   = $near['loc'] ? $near['loc']['name'] : '(tak ada lokasi aktif)';
            $rad  = $near['loc'] ? intval($near['loc']['radius_m']) : 0;
            $dist = $near['distance_m'] !== null ? round($near['distance_m']) : null;
            $msg  = $dist !== null
                ? "Di luar radius lokasi '$nm'. Jarak ~{$dist} m (radius {$rad} m)."
                : "Di luar lokasi yang diizinkan.";
            flash_set('error', $msg);
            header('Location: ' . url('/pegawai.php')); exit;
        }
    }

    if ($action === 'checkin') {
        if (!$canCheckin) {
            flash_set('warning', 'Anda sudah absen masuk hari ini.');
            header('Location: ' . url('/pegawai.php')); exit;
        }
        if (!can_checkin($now)) {
            flash_set('error', 'Belum waktunya Absen Masuk (minimal 08:00).');
            header('Location: ' . url('/pegawai.php')); exit;
        }
        $st = pdo()->prepare("
            INSERT INTO attendance (user_id, work_date, in_time, in_lat, in_lng, in_geo_ok)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              in_time   = VALUES(in_time),
              in_lat    = VALUES(in_lat),
              in_lng    = VALUES(in_lng),
              in_geo_ok = VALUES(in_geo_ok)
        ");
        $st->execute([
            $uid,
            $ymd,
            $now->format('Y-m-d H:i:s'),
            $lat ?: null, $lng ?: null,
            $geo_required ? 1 : null
        ]);
        flash_set('success', 'Absen Masuk Berhasil');
        header('Location: ' . url('/pegawai.php')); exit;
    }

    if ($action === 'checkout') {
        if (!$canCheckout) {
            $msg = !$hasIn ? 'Belum ada Absen Masuk hari ini.' : 'Anda sudah absen pulang hari ini.';
            flash_set('warning', $msg);
            header('Location: ' . url('/pegawai.php')); exit;
        }
        if (!can_checkout($now)) {
            flash_set('error', 'Belum waktunya Absen Pulang (minimal 17:00).');
            header('Location: ' . url('/pegawai.php')); exit;
        }
        $st = pdo()->prepare("
            UPDATE attendance
               SET out_time=?, out_lat=?, out_lng=?, out_geo_ok=?
             WHERE user_id=? AND work_date=?
        ");
        $st->execute([
            $now->format('Y-m-d H:i:s'),
            $lat ?: null, $lng ?: null,
            $geo_required ? 1 : null,
            $uid, $ymd
        ]);
        flash_set('success', 'Absen Pulang Berhasil');
        header('Location: ' . url('/pegawai.php')); exit;
    }

    flash_set('error', 'Aksi tidak dikenal.');
    header('Location: ' . url('/pegawai.php')); exit;
}

// refresh data setelah aksi (view)
$st = pdo()->prepare("SELECT * FROM attendance WHERE user_id=? AND work_date=? LIMIT 1");
$st->execute([$uid, $ymd]);
$today = $st->fetch();
$hasIn  = $today && !empty($today['in_time']);
$hasOut = $today && !empty($today['out_time']);
$canCheckin  = !$hasIn;
$canCheckout = $hasIn && !$hasOut;
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
    <title>RelayLab - Presensi</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/img/icon-96x96.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/img/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="assets/img/icon-167x167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/icon-180x180.png">

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
                    <h6 class="mb-0">RelayLab Presensi</h6>
                </div>

                <!-- User Profile-->
                <div class="user-profile-wrapper">
                </div>
            </div>
        </div>
    </div>

    <div class="page-content-wrapper">
        <!-- Welcome Toast -->
        <!--<div class="toast toast-autohide custom-toast-1 toast-primary home-page-toast shadow" role="alert"-->
        <!--    aria-live="assertive" aria-atomic="true" data-bs-delay="60000" data-bs-autohide="true" id="installWrap">-->
        <!--    <div class="toast-body p-4">-->
        <!--        <div class="toast-text me-2">-->
        <!--            <h6 class="text-white">Selamat Datang <?= htmlspecialchars($_SESSION['name']) ?>!</h6>-->
        <!--            <span class="d-block mb-2">Klik tombol <strong>Install Sekarang</strong> untuk menginstall app ke-->
        <!--                ponsel anda.</span>-->
        <!--            <button id="installRelaylab" class="btn btn-sm btn-warning">Install Sekarang</button>-->
        <!--        </div>-->
        <!--    </div>-->
        <!--    <button class="btn btn-close btn-close-white position-absolute p-2" type="button" data-bs-dismiss="toast"-->
        <!--        aria-label="Close"></button>-->
        <!--</div>-->
        <div class="pt-3"></div>


        <div class="container">
            <?php if (app_sim_active()): ?>
                <div class="alert custom-alert-three alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <div class="alert-text">
                        <h6>Mode Simulasi Aktif</h6>
                        <span>Waktu sistem: <?= htmlspecialchars(app_now()->format('d-m-Y H:i:s')) ?></span>
                    </div>
                    <button class="btn btn-close position-relative p-1 ms-auto" type="button" data-bs-dismiss="alert"
                        aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!--<?php if ($geo_required): ?>-->
            <!--    <div class="alert custom-alert-three alert-secondary alert-dismissible fade show" role="alert">-->
            <!--        <i class="bi bi-geo-alt"></i>-->
            <!--        <div class="alert-text">-->
            <!--            <h6><strong>WAJIB</strong> Geolokasi</h6>-->
            <!--            <span>Pastikan GPS Aktif!</span>-->
            <!--        </div>-->
            <!--        <button class="btn btn-close position-relative p-1 ms-auto" type="button" data-bs-dismiss="alert"-->
            <!--            aria-label="Close"></button>-->
            <!--    </div>-->
            <!--<?php else: ?>-->
            <!--    <div class="alert custom-alert-three alert-secondary alert-dismissible fade show" role="alert">-->
            <!--        <i class="bi bi-person-plus"></i>-->
            <!--        <div class="alert-text">-->
            <!--            <h6>Geolokasi <strong>TIDAK Wajib</strong></h6>-->
            <!--            <span>Anda bisa absen tanpa GPS.</span>-->
            <!--        </div>-->
            <!--        <button class="btn btn-close position-relative p-1 ms-auto" type="button" data-bs-dismiss="alert"-->
            <!--            aria-label="Close"></button>-->
            <!--    </div>-->
            <!--<?php endif; ?>-->


            <div class="card timeline-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="timeline-text mb-2">
                            <!-- <span class="badge mb-2 rounded-pill"><?= fmt_date($ymd) ?></span> -->
                            <h4>Status Hari Ini</h4>
                        </div>
                        <div class="timeline-icon mb-2">
                            <i class="bi bi-info-circle h1 mb-0"></i>
                        </div>
                    </div>
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <th>Nama</th>
                                <td><?= htmlspecialchars($_SESSION['name']) ?></td>
                                </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td><?= fmt_date($ymd) ?></td>
                            </tr>
                            <tr>
                                <th>Absen Masuk</th>
                                <td><?= isset($today['in_time']) ? fmt_time($today['in_time']) : '-' ?></td>
                            </tr>
                            <tr>
                                <th>Absen Pulang</th>
                                <td><?= isset($today['out_time']) ? fmt_time($today['out_time']) : '-' ?></td>
                            </tr>
                            <?php if (!empty($today['in_lat']) && !empty($today['in_lng'])): ?>
                                <tr>
                                    <th>Lokasi Masuk</th>
                                    <td><?= htmlspecialchars($today['in_lat'] . ', ' . $today['in_lng']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($today['out_lat']) && !empty($today['out_lng'])): ?>
                                <tr>
                                    <th>Lokasi Pulang</th>
                                    <td><?= htmlspecialchars($today['out_lat'] . ', ' . $today['out_lng']) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pt-2"></div>
                    <?php if ($hasIn && !$hasOut): ?>
                        <div class="small text-success mt-2">Status: Sedang bekerja (belum absen pulang).</div>
                    <?php elseif ($hasIn && $hasOut): ?>
                        <div class="small text-secondary mt-2">Status: Selesai (sudah absen pulang).</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="pt-3"></div>
        <div class="container">
            <div class="card timeline-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="timeline-text mb-2">
                            <!-- <span class="badge mb-2 rounded-pill"><?= fmt_date($ymd) ?></span> -->
                            <h4>Aksi Presensi</h4>
                        </div>
                        <div class="timeline-icon mb-2">
                            <i class="bi bi-clock h1 mb-0"></i>
                        </div>
                    </div>
                    <p class="text-muted small">
                        <?= $geo_required
                            ? 'Izinkan akses lokasi (GPS) agar tombol aktif.'
                            : 'Geolokasi opsional. Anda tetap bisa mengirim lokasi jika tersedia.' ?>
                    </p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <form method="post" id="form-checkin">
                                <input type="hidden" name="action" value="checkin">
                                <input type="hidden" name="lat" id="lat_in">
                                <input type="hidden" name="lng" id="lng_in">
                                <button
                                  class="btn btn-success w-100"
                                  name="checkin" value="1" id="btn-checkin"
                                  data-can-checkin="<?= $canCheckin ? '1':'0' ?>"
                                  <?= ($geo_required || !$canCheckin) ? 'disabled' : '' ?>>
                                  <?= $hasIn ? 'Sudah Absen Masuk' : 'Absen Masuk' ?>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form method="post" id="form-checkout">
                                <input type="hidden" name="action" value="checkout">
                                <input type="hidden" name="lat" id="lat_out">
                                <input type="hidden" name="lng" id="lng_out">
                                <button
                                  class="btn btn-danger w-100"
                                  name="checkout" value="1" id="btn-checkout"
                                  data-can-checkout="<?= $canCheckout ? '1':'0' ?>"
                                  <?= ($geo_required || !$canCheckout) ? 'disabled' : '' ?>>
                                  <?= $hasOut ? 'Sudah Absen Pulang' : 'Absen Pulang' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <hr>
                    <div class="col-md-6">

                        <button class="btn btn-outline-secondary w-100" type="button" onclick="primeButtons()">Ambil
                            Ulang
                            Lokasi</button>
                        <span id="geo-hint" class="ms-2 small text-muted"></span>
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
                    <li class="active">
                        <a href="<?= url('/pegawai.php') ?>">
                            <i class="bi bi-house"></i>
                            <span>Beranda</span>
                        </a>
                    </li>
                    <li>
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


    <!-- <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Presensi Pegawai</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/pegawai_rekap.php') ?>">Rekap Saya</a>
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

        <?php if ($geo_required): ?>
            <div class="alert alert-info py-2">Geolokasi <strong>WAJIB</strong> saat absen. Pastikan GPS aktif.</div>
        <?php else: ?>
            <div class="alert alert-secondary py-2">Geolokasi <strong>TIDAK wajib</strong>. Anda bisa absen tanpa GPS.</div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Status Hari Ini</div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th style="width:150px">Tanggal</th>
                                <td><?= fmt_date($ymd) ?></td>
                            </tr>
                            <tr>
                                <th>Absen Masuk</th>
                                <td><?= isset($today['in_time']) ? fmt_time($today['in_time']) : '-' ?></td>
                            </tr>
                            <tr>
                                <th>Absen Pulang</th>
                                <td><?= isset($today['out_time']) ? fmt_time($today['out_time']) : '-' ?></td>
                            </tr>
                            <?php if (!empty($today['in_lat']) && !empty($today['in_lng'])): ?>
                                <tr>
                                    <th>Lokasi Masuk</th>
                                    <td><?= htmlspecialchars($today['in_lat'] . ', ' . $today['in_lng']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($today['out_lat']) && !empty($today['out_lng'])): ?>
                                <tr>
                                    <th>Lokasi Pulang</th>
                                    <td><?= htmlspecialchars($today['out_lat'] . ', ' . $today['out_lng']) ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>

                        <?php if ($hasIn && !$hasOut): ?>
                            <div class="small text-success mt-2">Status: Sedang bekerja (belum absen pulang).</div>
                        <?php elseif ($hasIn && $hasOut): ?>
                            <div class="small text-secondary mt-2">Status: Selesai (sudah absen pulang).</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Aksi Presensi</div>
                    <div class="card-body">
                        <p class="text-muted small">
                            <?= $geo_required
                                ? 'Izinkan akses lokasi (GPS) agar tombol aktif. Radius & titik ditentukan Admin.'
                                : 'Geolokasi opsional. Anda tetap bisa mengirim lokasi jika tersedia.' ?>
                        </p>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <form method="post" id="form-checkin">
                                    <input type="hidden" name="action" value="checkin">
                                    <input type="hidden" name="lat" id="lat_in">
                                    <input type="hidden" name="lng" id="lng_in">
                                    <button class="btn btn-success w-100" name="checkin" value="1" id="btn-checkin"
                                        <?= $geo_required ? 'disabled ' : '' ?><?= $hasIn ? 'disabled ' : '' ?>>
                                        <?= $hasIn ? 'Sudah Absen Masuk' : 'Absen Masuk' ?>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="post" id="form-checkout">
                                    <input type="hidden" name="action" value="checkout">
                                    <input type="hidden" name="lat" id="lat_out">
                                    <input type="hidden" name="lng" id="lng_out">
                                    <button class="btn btn-danger w-100" name="checkout" value="1" id="btn-checkout"
                                        <?= $geo_required ? 'disabled ' : '' ?><?= (!$hasIn || $hasOut) ? 'disabled ' : '' ?>>
                                        <?= $hasOut ? 'Sudah Absen Pulang' : 'Absen Pulang' ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <hr>
                        <button class="btn btn-outline-secondary" type="button" onclick="primeButtons()">Ambil Ulang
                            Lokasi</button>
                        <span id="geo-hint" class="ms-2 small text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

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
    
    <script>
        const GEO_REQUIRED = <?= $geo_required ? 'true' : 'false' ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($__flash = flash_get()): ?>
        <script>
            const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            const FLASH = <?= json_encode($__flash, JSON_UNESCAPED_UNICODE) ?>;
            Toast.fire({ icon: (FLASH && FLASH.type) ? FLASH.type : 'info', title: (FLASH && FLASH.text) ? FLASH.text : '' });
        </script>
    <?php endif; ?>

    <script>
        // Ambil lokasi sekali (tanpa loading) agar toast tidak ketutup
        function getGeoOnce(cb, showLoading) {
            if (!navigator.geolocation) {
                if (showLoading) Swal.fire({ icon: 'error', title: 'GPS tidak didukung', text: 'Aktifkan layanan lokasi atau ganti browser.' });
                return;
            }
            if (showLoading) Swal.showLoading();
            navigator.geolocation.getCurrentPosition(function (pos) {
                if (showLoading) Swal.close();
                cb({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    acc: pos.coords.accuracy
                });
            }, function (err) {
                if (showLoading) {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Gagal ambil lokasi', text: err.message });
                }
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        }

        // Isi hidden & hanya enable tombol yang diizinkan server
        function primeButtons() {
            getGeoOnce(function (g) {
                const lat = g.lat.toFixed(8), lng = g.lng.toFixed(8);
                document.getElementById('lat_in').value = lat;
                document.getElementById('lng_in').value = lng;
                document.getElementById('lat_out').value = lat;
                document.getElementById('lng_out').value = lng;

                const hint = document.getElementById('geo-hint');
                if (hint) hint.textContent = `Lokasi terbaca (akurasi ~${Math.round(g.acc)} m)`;

                if (GEO_REQUIRED) {
                    const btnIn = document.getElementById('btn-checkin');
                    const btnOut = document.getElementById('btn-checkout');

                    if (btnIn && btnIn.dataset.canCheckin === '1') btnIn.removeAttribute('disabled');
                    // jangan enable kalau server bilang tidak boleh
                    if (btnOut && btnOut.dataset.canCheckout === '1') btnOut.removeAttribute('disabled');
                }
            }, /*showLoading=*/false);
        }

        // Kunci tombol saat submit (anti dobel klik)
        function lockBtn(btn, text) {
            if (!btn) return;
            btn.setAttribute('disabled', 'disabled');
            if (text) { btn.dataset.originalText = btn.textContent; btn.textContent = text; }
        }
        function restoreBtn(btn) {
            if (!btn) return;
            if (btn.dataset.originalText) btn.textContent = btn.dataset.originalText;
        }

        // Handler submit: satu tempat untuk GEO wajib / tidak
        document.getElementById('form-checkin').addEventListener('submit', function (e) {
            const btn = document.getElementById('btn-checkin');
            if (GEO_REQUIRED) {
                e.preventDefault();
                if (btn.dataset.canCheckin !== '1') return; // safety
                lockBtn(btn, 'Memproses...');
                getGeoOnce((g) => {
                    document.getElementById('lat_in').value = g.lat.toFixed(8);
                    document.getElementById('lng_in').value = g.lng.toFixed(8);
                    e.target.submit();
                }, /*showLoading=*/true);
            } else {
                if (btn.dataset.canCheckin !== '1') { e.preventDefault(); return; }
                lockBtn(btn, 'Memproses...');
            }
        });

        document.getElementById('form-checkout').addEventListener('submit', function (e) {
            const btn = document.getElementById('btn-checkout');
            if (GEO_REQUIRED) {
                e.preventDefault();
                if (btn.dataset.canCheckout !== '1') return; // safety
                lockBtn(btn, 'Memproses...');
                getGeoOnce((g) => {
                    document.getElementById('lat_out').value = g.lat.toFixed(8);
                    document.getElementById('lng_out').value = g.lng.toFixed(8);
                    e.target.submit();
                }, /*showLoading=*/true);
            } else {
                if (btn.dataset.canCheckout !== '1') { e.preventDefault(); return; }
                lockBtn(btn, 'Memproses...');
            }
        });

        // Jalankan setelah render; beri jeda kecil agar toast tampil mulus
        window.addEventListener('DOMContentLoaded', () => { setTimeout(primeButtons, 350); });
    </script>
</body>