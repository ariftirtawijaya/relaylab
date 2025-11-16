<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_role('pegawai');

$uid = me_id();

// ambil data user (pakai SELECT * agar aman di skema yang beda-beda)
$st = pdo()->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$st->execute([$uid]);
$user = $st->fetch();
if (!$user) {
  flash_set('error', 'User tidak ditemukan.');
  header('Location: ' . url('/pegawai.php'));
  exit;
}

// deteksi kolom & gaya penyimpanan lama
$hasColHash = array_key_exists('password_hash', $user);
$stored = null;
$storedCol = null;

if ($hasColHash && isset($user['password_hash']) && $user['password_hash'] !== null && $user['password_hash'] !== '') {
  $stored = (string) $user['password_hash'];
  $storedCol = 'password_hash';
} elseif (array_key_exists('password', $user)) {
  $stored = (string) $user['password'];
  $storedCol = 'password';
} else {
  // fallback ekstrem: anggap belum ada kolom; pakai password_hash nantinya
  $stored = '';
  $storedCol = 'password_hash';
}

$isHashed = is_string($stored) && strlen($stored) > 0 && $stored[0] === '$';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old = trim($_POST['old_password'] ?? '');
  $new1 = trim($_POST['new_password'] ?? '');
  $new2 = trim($_POST['new_password2'] ?? '');

  // validasi dasar
  if ($new1 === '' || $new2 === '' || $old === '') {
    flash_set('error', 'Semua kolom wajib diisi.');
    header('Location: ' . url('/pegawai_password.php'));
    exit;
  }
  if (strlen($new1) < 6) {
    flash_set('error', 'Password baru minimal 6 karakter.');
    header('Location: ' . url('/pegawai_password.php'));
    exit;
  }
  if ($new1 !== $new2) {
    flash_set('error', 'Konfirmasi password baru tidak sama.');
    header('Location: ' . url('/pegawai_password.php'));
    exit;
  }

  // verifikasi password lama
  $ok = false;
  if ($isHashed) {
    $ok = password_verify($old, $stored);
  } else {
    // skema lama simpan plaintext (tidak disarankan, tapi kita hormati agar login lama tidak rusak)
    $ok = hash_equals($stored, $old);
  }

  if (!$ok) {
    flash_set('error', 'Password lama salah.');
    header('Location: ' . url('/pegawai_password.php'));
    exit;
  }

  // siap update
  $newHash = password_hash($new1, PASSWORD_DEFAULT);
  try {
    if ($storedCol === 'password_hash' || $hasColHash) {
      // skema modern: simpan hash ke password_hash
      $st = pdo()->prepare("UPDATE users SET password_hash=? WHERE id=?");
      $st->execute([$newHash, $uid]);
    } else {
      // tidak ada kolom password_hash → cek gaya lama
      if ($isHashed) {
        // kolom password berisi hash → tetap simpan hash di kolom password
        $st = pdo()->prepare("UPDATE users SET password=? WHERE id=?");
        $st->execute([$newHash, $uid]);
      } else {
        // kolom password berisi plaintext → simpan plaintext (agar kompatibel login lama)
        // (REKOMENDASI: migrasi login ke password_verify lalu pindah ke password_hash)
        $st = pdo()->prepare("UPDATE users SET password=? WHERE id=?");
        $st->execute([$new1, $uid]);
      }
    }
  } catch (Throwable $e) {
    // fallback: coba perintah alternatif (misal skema beda)
    try {
      $st = pdo()->prepare("UPDATE users SET password_hash=? WHERE id=?");
      $st->execute([$newHash, $uid]);
    } catch (Throwable $e2) {
      flash_set('error', 'Gagal mengubah password. Hubungi admin.');
      header('Location: ' . url('/pegawai_password.php'));
      exit;
    }
  }

  flash_set('success', 'Password berhasil diubah.');
  header('Location: ' . url('/settings.php'));
  exit;
}
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
  <title>RelayLab - Ubah Password</title>

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
  <div class="login-back-button">
    <a href="settings.php">
      <i class="bi bi-arrow-left-short"></i>
    </a>
  </div>

  <div class="login-wrapper d-flex align-items-center justify-content-center">
    <div class="custom-container">
      <div class="text-center px-4">
        <img class="login-intro-img" src="assets/img/profile.png" alt="">
      </div>

      <!-- Register Form -->
      <div class="register-form mt-4">
        <form method="post" class="row g-3" autocomplete="off">
          <h6 class="mb-3 text-center">Ubah Password</h6>

          <div class="form-group text-start mb-3">
            <input class="form-control" type="password" name="old_password" required
              placeholder="Masukan password lama">
          </div>

          <div class="form-group text-start mb-3 position-relative">
            <input type="password" class="form-control" id="psw-input" name="new_password" minlength="6" required
              placeholder="Password baru (6 karakter)">
            <div class="position-absolute" id="password-visibility">
              <i class="bi bi-eye"></i>
              <i class="bi bi-eye-slash"></i>
            </div>
          </div>

          <div class="mb-3" id="pswmeter"></div>

          <div class="form-group text-start mb-3">
            <input type="password" class="form-control" name="new_password2" minlength="6" required
              placeholder="Ketik ulang password baru">
          </div>

          <button class="btn btn-primary w-100" type="submit">Update Password</button>
        </form>
      </div>
    </div>
  </div>

  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/internet-status.js"></script>
  <script src="assets/js/dark-rtl.js"></script>
  <script src="assets/js/active.js"></script>
  <script src="assets/js/pswmeter.js"></script>
  <script src="assets/js/pwa.js"></script>

  <script>
    function togglePw(btn) {
      const input = btn.parentElement.querySelector('input');
      if (!input) return;
      input.type = (input.type === 'password') ? 'text' : 'password';
      btn.textContent = (input.type === 'password') ? 'Lihat' : 'Sembunyikan';
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php if ($__flash = flash_get()): ?>
    <script>
      const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
      const FLASH = <?= json_encode($__flash, JSON_UNESCAPED_UNICODE) ?>;
      Toast.fire({ icon: (FLASH && FLASH.type) ? FLASH.type : 'info', title: (FLASH && FLASH.text) ? FLASH.text : '' });
    </script>
  <?php endif; ?>
</body>

</html>