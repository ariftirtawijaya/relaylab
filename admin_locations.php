<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/geo.php';
require_once __DIR__ . '/app/settings.php';
require_role('admin');

/* Handle toggle geo_enforce */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_geo_enforce'])) {
    $enf = isset($_POST['geo_enforce']) && $_POST['geo_enforce'] === '1';
    settings_set_bool('geo_enforce', $enf);
    flash_set('success', 'Pengaturan geolokasi diperbarui: ' . ($enf ? 'WAJIB' : 'TIDAK wajib'));
    header('Location: ' . url('/admin_locations.php'));
    exit;
}

/* Handle CRUD lokasi */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = trim($_POST['name'] ?? '');
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $rad = max(1, intval($_POST['radius_m'] ?? 50));
        if ($name && $lat && $lng) {
            $st = pdo()->prepare("INSERT INTO locations(name,lat,lng,radius_m,active) VALUES(?,?,?,?,1)");
            $st->execute([$name, $lat, $lng, $rad]);
            flash_set('success', 'Lokasi ditambahkan.');
        } else {
            flash_set('error', 'Nama/koordinat tidak valid.');
        }
        header('Location: ' . url('/admin_locations.php'));
        exit;
    }
    if (isset($_POST['toggle'])) {
        $id = intval($_POST['id'] ?? 0);
        $st = pdo()->prepare("UPDATE locations SET active = 1 - active WHERE id=?");
        $st->execute([$id]);
        flash_set('success', 'Status lokasi diubah.');
        header('Location: ' . url('/admin_locations.php'));
        exit;
    }
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id'] ?? 0);
        $st = pdo()->prepare("DELETE FROM locations WHERE id=?");
        $st->execute([$id]);
        flash_set('success', 'Lokasi dihapus.');
        header('Location: ' . url('/admin_locations.php'));
        exit;
    }
}

$geo_enforce = settings_get_bool('geo_enforce', false);
$locs = geo_active_locations();
$all = pdo()->query("SELECT * FROM locations ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Admin • Lokasi Presensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <span class="navbar-brand">Admin • Lokasi Presensi</span>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= url('/admin_rekap.php') ?>">Rekap</a>
                <a class="btn btn-outline-primary" href="<?= url('/admin_employees.php') ?>">Pegawai</a>
                <a class="btn btn-outline-secondary" href="<?= url('/logout.php') ?>">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">

        <!-- Toggle Wajib Geolokasi -->
        <div class="card mb-3">
            <div class="card-header">Pengaturan Geolokasi</div>
            <div class="card-body">
                <form method="post" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="geo_enforce" name="geo_enforce"
                                value="1" <?= $geo_enforce ? 'checked' : '' ?>>
                            <label class="form-check-label" for="geo_enforce">
                                Wajib geolokasi saat absen (radius & titik lokasi berlaku)
                            </label>
                        </div>
                        <small class="text-muted">
                            Jika dimatikan, pegawai dapat absen tanpa verifikasi lokasi (seperti semula).
                        </small>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" name="save_geo_enforce" value="1">Simpan
                            Pengaturan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Tambah Lokasi</div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-2">
                                <label class="form-label">Nama Lokasi</label>
                                <input name="name" class="form-control" placeholder="Workshop Sukabumi" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label class="form-label">Latitude</label>
                                    <input name="lat" class="form-control" placeholder="-6.9xxxx" required>
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="form-label">Longitude</label>
                                    <input name="lng" class="form-control" placeholder="106.9xxxx" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Radius (meter)</label>
                                <input name="radius_m" type="number" class="form-control" value="50" min="1">
                            </div>
                            <button class="btn btn-primary" name="add" value="1">Simpan</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="getMyLocation()">Ambil
                                Koordinat Saya</button>
                            <small class="text-muted d-block mt-2">Tips: berdiri di titik yang diizinkan lalu klik
                                “Ambil Koordinat Saya”.</small>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Daftar Lokasi</div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Koordinat</th>
                                    <th>Radius</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all as $L): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($L['name']) ?></td>
                                        <td><?= htmlspecialchars($L['lat'] . ', ' . $L['lng']) ?></td>
                                        <td><?= intval($L['radius_m']) ?> m</td>
                                        <td><?= $L['active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?>
                                        </td>
                                        <td class="d-flex gap-2">
                                            <form method="post" class="m-0">
                                                <input type="hidden" name="id" value="<?= $L['id'] ?>">
                                                <button class="btn btn-sm btn-outline-primary" name="toggle"
                                                    value="1">Toggle</button>
                                            </form>
                                            <form method="post" class="m-0" onsubmit="return confirm('Hapus lokasi ini?')">
                                                <input type="hidden" name="id" value="<?= $L['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" name="delete"
                                                    value="1">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach;
                                if (!$all): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada lokasi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="small text-muted">Lokasi aktif saat ini: <?= count($locs) ?> titik. Geolokasi:
                            <strong><?= $geo_enforce ? 'WAJIB' : 'TIDAK wajib' ?></strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function getMyLocation() {
            if (!navigator.geolocation) { alert('Geolocation tidak didukung browser.'); return; }
            navigator.geolocation.getCurrentPosition(function (pos) {
                const lat = pos.coords.latitude.toFixed(8);
                const lng = pos.coords.longitude.toFixed(8);
                document.querySelector('input[name="lat"]').value = lat;
                document.querySelector('input[name="lng"]').value = lng;
            }, function (err) {
                alert('Gagal ambil posisi: ' + err.message);
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($__flash = flash_get()): ?>
        <script>
            Swal.fire({ icon: '<?= htmlspecialchars($__flash['type']) ?>', title: 'Info', text: '<?= htmlspecialchars($__flash['text']) ?>' });
        </script>
    <?php endif; ?>
</body>

</html>