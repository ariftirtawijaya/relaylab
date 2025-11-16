<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/time.php';
require_once __DIR__ . '/app/geo.php';
require_once __DIR__ . '/app/settings.php';
require_role('pegawai');
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
                    <h6 class="mb-0">Pengaturan</h6>
                </div>

                <!-- User Profile-->
                <div class="user-profile-wrapper">
                </div>
            </div>
        </div>
    </div>

    <div class="page-content-wrapper py-3">
        <div class="container">
            <div class="card user-info-card mb-3 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="user-profile me-3">
                        <img src="assets/img/profile.png"
                            alt="">
                        <!-- <form action="#">
                            <input class="form-control" type="file">
                        </form> -->
                    </div>
                    <div class="user-info">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-1"><?= htmlspecialchars($_SESSION['name']) ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3 shadow-sm">
                <div class="card-body direction-rtl">
                    <div class="single-setting-panel">
                        <a href="/pegawai_password.php">
                            <div class="icon-wrapper bg-info">
                                <i class="bi bi-lock"></i>
                            </div>
                            Ubah Password
                        </a>
                    </div>
<div class="single-setting-panel">
                        <a href="/pegawai_kasbon.php">
                            <div class="icon-wrapper bg-info">
                                <i class="bi bi-cash"></i>
                            </div>
                            Kasbon
                        </a>
                    </div>
                    <div class="single-setting-panel">
                        <a href="/logout.php">
                            <div class="icon-wrapper bg-danger">
                                <i class="bi bi-box-arrow-right"></i>
                            </div>
                            Logout
                        </a>
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
                            <i class=" bi bi-calendar2-check"></i>
                            <span>Rekap</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="/settings.php">
                            <i class=" bi bi-person"></i>
                            <span>Profil</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($__flash = flash_get()): ?>
        <script>
            const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            const FLASH = <?= json_encode($__flash, JSON_UNESCAPED_UNICODE) ?>;
            Toast.fire({ icon: (FLASH && FLASH.type) ? FLASH.type : 'info', title: (FLASH && FLASH.text) ? FLASH.text : '' });
        </script>
    <?php endif; ?>
</body>