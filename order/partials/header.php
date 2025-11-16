<?php
// partials/header.php
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Relay Lab Production</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Select2 CSS (untuk dropdown searchable) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        body {
            background-color: #f8f9fa;
        }

        .card-header.bg-dark {
            background: #1f1f1f !important;
        }

        .navbar-brand {
            letter-spacing: .03em;
        }

        .table-danger .badge.bg-danger {
            font-size: .7rem;
        }

        /* Biar Select2 full width di form Bootstrap */
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">Relay Lab</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navmenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="components.php">Components</a></li>
                    <li class="nav-item"><a class="nav-link" href="bom.php">BOM</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="requirements.php">Kebutuhan Produksi</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">