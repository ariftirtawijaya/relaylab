<?php
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = trim($_POST['employee_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($employee_id && $password) {
        $st = pdo()->prepare("SELECT * FROM users WHERE employee_id=? LIMIT 1");
        $st->execute([$employee_id]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            login_user($u);
            flash_set('success', 'Login berhasil');
            header('Location: ' . url('/index.php'));
            exit;
        }
    }
    flash_set('error', 'ID atau password salah');
    header('Location: ' . url('/login.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="RelayLab Autolight">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="theme-color" content="#0134d4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

    <title>Login Presensi</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/img/icon-96x96.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/img/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="assets/img/icon-167x167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/icon-180x180.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#264655">

</head>



<body>
    <!-- <div class="container py-5" style="max-width:420px">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3">Login Presensi</h4>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">ID Pegawai</label>
                        <input name="employee_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Masuk</button>
                </form>
            </div>
        </div>
    </div> -->

    <!-- Preloader -->
    <div id="preloader">
        <div class="spinner-grow text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Internet Connection Status -->
    <div class="internet-connection-status" id="internetStatus"></div>

    <!-- Login Wrapper Area -->
    <div class="login-wrapper d-flex align-items-center justify-content-center">
        <div class="custom-container">
            <div class="text-center px-4">
                <img class="login-intro-img" src="assets/img/bg.png" alt="">
            </div>

            <!-- Register Form -->
            <div class="register-form mt-4">
                <h6 class="mb-3 text-center">Masuk untuk presensi</h6>

                <form method="post">
                    <div class="form-group">
                        <input name="employee_id" class="form-control" autocomplete="one-time-code" placeholder="ID"
                            required>
                        <!-- <input class="form-control" type="text" id="username" placeholder="Username"> -->
                    </div>

                    <div class="form-group position-relative">
                        <input type="password" id="psw-input" name="password" class="form-control"
                            autocomplete="new-password" placeholder="Password" required>
                        <!-- <input class="form-control" id="psw-input" type="password" placeholder="Enter Password"> -->
                        <div class="position-absolute" id="password-visibility">
                            <i class="bi bi-eye"></i>
                            <i class="bi bi-eye-slash"></i>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">Masuk</button>
                </form>
            </div>


        </div>
    </div>

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
            const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
            Toast.fire({ icon: '<?= htmlspecialchars($__flash['type']) ?>', title: '<?= htmlspecialchars($__flash['text']) ?>' });
        </script>
    <?php endif; ?>
</body>

</html>