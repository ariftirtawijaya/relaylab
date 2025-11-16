<?php
require_once 'config/db.php';
require_once 'partials/header.php';

/*
------------------------------------------
SUMMARY / DASHBOARD DATA
------------------------------------------
*/

// hitung total produk
$totProd = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];

// total komponen
$totComp = $conn->query("SELECT COUNT(*) AS c FROM components")->fetch_assoc()['c'];

// order hari ini
$today = date('Y-m-d');
$todayOrdersRes = $conn->query("
    SELECT o.id,
           o.qty_ordered,
           o.order_date,
           p.name AS product_name
    FROM orders o
    JOIN products p ON p.id = o.product_id
    WHERE o.order_date = '$today'
    ORDER BY o.id DESC
");

// total row order hari ini + total pcs
$totOrderRowToday = 0;
$totalPcsToday = 0;
$todayOrders = [];

while ($row = $todayOrdersRes->fetch_assoc()) {
    $todayOrders[] = $row;
    $totOrderRowToday++;
    $totalPcsToday += intval($row['qty_ordered']);
}

?>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Produk</div>
                <div class="fs-4 fw-bold"><?= $totProd ?></div>
                <div class="text-muted small">Varian relay set / harness</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Komponen</div>
                <div class="fs-4 fw-bold"><?= $totComp ?></div>
                <div class="text-muted small">Kabel, soket, sekring, dll</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Order Hari Ini (row)</div>
                <div class="fs-4 fw-bold"><?= $totOrderRowToday ?></div>
                <div class="text-muted small"><?= $today ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Qty Hari Ini</div>
                <div class="fs-4 fw-bold"><?= $totalPcsToday ?> pcs</div>
                <div class="text-muted small">Semua produk digabung</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap">
        <strong>Order Hari Ini (<?= $today ?>)</strong>
        <a class="btn btn-sm btn-outline-light" href="orders.php">+ Tambah Order</a>
    </div>
    <div class="card-body table-responsive">
        <?php if (empty($todayOrders)): ?>
            <div class="alert alert-secondary mb-0">Belum ada order hari ini.</div>
        <?php else: ?>
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todayOrders as $ord): ?>
                        <tr>
                            <td><?= htmlspecialchars($ord['product_name']) ?></td>
                            <td><?= $ord['qty_ordered'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>


<?php require_once 'partials/footer.php'; ?>