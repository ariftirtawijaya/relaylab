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
$hasIn = $today && !empty($today['in_time']);
$hasOut = $today && !empty($today['out_time']);

// === Proses aksi ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // normalisasi action agar aman walau submit via form.submit()
    $action = $_POST['action'] ?? null;
    if (!$action) {
        if (isset($_POST['checkin']))
            $action = 'checkin';
        if (isset($_POST['checkout']))
            $action = 'checkout';
    }

    // koordinat (boleh kosong jika geo tidak wajib)
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;

    if ($geo_required) {
        // wajib ada titik aktif
        $locs = geo_active_locations();
        if (!$locs) {
            flash_set('error', 'Lokasi presensi belum disetel admin.');
            header('Location: ' . url('/pegawai.php'));
            exit;
        }
        // wajib ada koordinat valid
        if (!$lat || !$lng) {
            flash_set('error', 'Lokasi tidak terdeteksi. Izinkan GPS lalu coba lagi.');
            header('Location: ' . url('/pegawai.php'));
            exit;
        }
        // validasi radius server-side
        $near = geo_nearest_ok($lat, $lng);
        if (!$near['ok']) {
            $nm = $near['loc'] ? $near['loc']['name'] : '(tak ada lokasi aktif)';
            $rad = $near['loc'] ? intval($near['loc']['radius_m']) : 0;
            $dist = $near['distance_m'] !== null ? round($near['distance_m']) : null;
            $msg = $dist !== null
                ? "Di luar radius lokasi '$nm'. Jarak ~{$dist} m (radius {$rad} m)."
                : "Di luar lokasi yang diizinkan.";
            flash_set('error', $msg);
            header('Location: ' . url('/pegawai.php'));
            exit;
        }
    }

    if ($action === 'checkin') {
        if ($hasIn) {
            flash_set('warning', 'Anda sudah absen masuk hari ini.');
            header('Location: ' . url('/pegawai.php'));
            exit;
        }
        if (!can_checkin($now)) {
            flash_set('error', 'Belum waktunya Absen Masuk (minimal 08:00).');
            header('Location: ' . url('/pegawai.php'));
            exit;
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
            $lat ?: null,
            $lng ?: null,
            $geo_required ? 1 : null
        ]);
        flash_set('success', 'Absen Masuk Berhasil');
        header('Location: ' . url('/pegawai.php'));
        exit;
    }

    if ($action === 'checkout') {
        if (!$today || empty($today['in_time'])) {
            flash_set('error', 'Belum ada Absen Masuk hari ini.');
            header('Location: ' . url('/pegawai.php'));
            exit;
        }
        if ($hasOut) {
            flash_set('warning', 'Anda sudah absen pulang hari ini.');
            header('Location: ' . url('/pegawai.php'));
            exit;
        }
        if (!can_checkout($now)) {
            flash_set('error', 'Belum waktunya Absen Pulang (minimal 17:00).');
            header('Location: ' . url('/pegawai.php'));
            exit;
        }
        $st = pdo()->prepare("
            UPDATE attendance
               SET out_time=?, out_lat=?, out_lng=?, out_geo_ok=?
             WHERE user_id=? AND work_date=?
        ");
        $st->execute([
            $now->format('Y-m-d H:i:s'),
            $lat ?: null,
            $lng ?: null,
            $geo_required ? 1 : null,
            $uid,
            $ymd
        ]);
        flash_set('success', 'Absen Pulang Berhasil');
        header('Location: ' . url('/pegawai.php'));
        exit;
    }

    flash_set('error', 'Aksi tidak dikenal.');
    header('Location: ' . url('/pegawai.php'));
    exit;
}

// refresh data setelah aksi (view)
$st = pdo()->prepare("SELECT * FROM attendance WHERE user_id=? AND work_date=? LIMIT 1");
$st->execute([$uid, $ymd]);
$today = $st->fetch();
$hasIn = $today && !empty($today['in_time']);
$hasOut = $today && !empty($today['out_time']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Presensi • <?= htmlspecialchars($_SESSION['name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
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
    </div>

    <script>
        const GEO_REQUIRED = <?= $geo_required ? 'true' : 'false' ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($__flash = flash_get()): ?>
        <script>
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
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

        // Isi hidden & aktifkan tombol jika GEO wajib
        function primeButtons() {
            getGeoOnce(function (g) {
                document.getElementById('lat_in').value = g.lat.toFixed(8);
                document.getElementById('lng_in').value = g.lng.toFixed(8);
                document.getElementById('lat_out').value = g.lat.toFixed(8);
                document.getElementById('lng_out').value = g.lng.toFixed(8);
                const hint = document.getElementById('geo-hint');
                if (hint) hint.textContent = `Lokasi terbaca (akurasi ~${Math.round(g.acc)} m)`;
                if (GEO_REQUIRED) {
                    document.getElementById('btn-checkin')?.removeAttribute('disabled');
                    document.getElementById('btn-checkout')?.removeAttribute('disabled');
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
                lockBtn(btn, 'Memproses...');
                getGeoOnce((g) => {
                    document.getElementById('lat_in').value = g.lat.toFixed(8);
                    document.getElementById('lng_in').value = g.lng.toFixed(8);
                    e.target.submit();
                }, /*showLoading=*/true);
            } else {
                lockBtn(btn, 'Memproses...');
            }
        });

        document.getElementById('form-checkout').addEventListener('submit', function (e) {
            const btn = document.getElementById('btn-checkout');
            if (GEO_REQUIRED) {
                e.preventDefault();
                lockBtn(btn, 'Memproses...');
                getGeoOnce((g) => {
                    document.getElementById('lat_out').value = g.lat.toFixed(8);
                    document.getElementById('lng_out').value = g.lng.toFixed(8);
                    e.target.submit();
                }, /*showLoading=*/true);
            } else {
                lockBtn(btn, 'Memproses...');
            }
        });

        // Jalankan setelah render; beri jeda kecil agar toast tampil mulus
        window.addEventListener('DOMContentLoaded', () => { setTimeout(primeButtons, 350); });
    </script>
</body>