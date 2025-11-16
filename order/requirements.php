<?php
require_once 'config/db.php';

// Ambil range tanggal; default: awal bulan sampai hari ini
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

/*
Step 1:
Ambil semua order dalam range tanggal.
Gabungkan jumlah pcs per product_id.
*/
$sqlOrders = "
    SELECT o.product_id,
           SUM(o.qty_ordered) AS total_qty_ordered,
           p.name AS product_name
    FROM orders o
    JOIN products p ON p.id = o.product_id
    WHERE o.order_date >= '$start'
      AND o.order_date <= '$end'
    GROUP BY o.product_id, p.name
    ORDER BY p.name ASC
";
$resOrders = $conn->query($sqlOrders);

// Data yang akan disusun
$byProduct = []; // breakdown per produk
$globalReq = []; // kebutuhan total global semua produk

while ($ord = $resOrders->fetch_assoc()) {
    $pid = intval($ord['product_id']);
    $prodName = $ord['product_name'];
    $qtyOrdered = intval($ord['total_qty_ordered']);

    if (!isset($byProduct[$pid])) {
        $byProduct[$pid] = [
            'product_name' => $prodName,
            'total_order_qty' => 0,
            'components' => [] // per komponen
        ];
    }

    // total pcs produk ini dalam periode
    $byProduct[$pid]['total_order_qty'] += $qtyOrdered;

    // Ambil BOM untuk produk ini
    $bomRes = $conn->query("
        SELECT b.component_id,
               b.qty_per_product,
               c.name AS component_name
        FROM bom_items b
        JOIN components c ON c.id = b.component_id
        WHERE b.product_id = $pid
        ORDER BY LOWER(c.name) ASC
    ");

    while ($bom = $bomRes->fetch_assoc()) {
        $cid = intval($bom['component_id']);
        $perUnit = floatval($bom['qty_per_product']); // kebutuhan komponen ini per 1 pcs produk
        $needForProd = $perUnit * $qtyOrdered;             // kebutuhan total untuk batch produk ini

        // simpan ke breakdown per produk
        if (!isset($byProduct[$pid]['components'][$cid])) {
            $byProduct[$pid]['components'][$cid] = [
                'name' => $bom['component_name'],
                'needed' => 0
            ];
        }
        $byProduct[$pid]['components'][$cid]['needed'] += $needForProd;

        // simpan ke total global
        if (!isset($globalReq[$cid])) {
            $globalReq[$cid] = [
                'name' => $bom['component_name'],
                'needed' => 0
            ];
        }
        $globalReq[$cid]['needed'] += $needForProd;
    }
}

// Sort globalReq by nama komponen biar rapi
usort($globalReq, function ($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

/*
=================================================================
FITUR EXPORT CSV
=================================================================
?export=product&product_id=XX&start=YYYY-MM-DD&end=YYYY-MM-DD
?export=global&start=YYYY-MM-DD&end=YYYY-MM-DD
=================================================================
*/
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];

    if ($exportType === 'product' && isset($_GET['product_id'])) {
        $pidExport = intval($_GET['product_id']);

        if (isset($byProduct[$pidExport])) {
            $pdata = $byProduct[$pidExport];

            // nama file
            $filename = "kebutuhan_produk_" .
                preg_replace('/[^a-zA-Z0-9_\-]/', '_', $pdata['product_name']) .
                "_" . $start . "_to_" . $end . ".csv";

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $out = fopen('php://output', 'w');

            // Info header
            fputcsv($out, ["Produk", $pdata['product_name']]);
            fputcsv($out, ["Total Order (pcs)", $pdata['total_order_qty']]);
            fputcsv($out, ["Periode", $start . " s/d " . $end]);
            fputcsv($out, []); // baris kosong

            // Header tabel
            fputcsv($out, ["Komponen", "Total Kebutuhan"]);

            // Data komponen
            foreach ($pdata['components'] as $cid => $cdata) {
                fputcsv($out, [
                    $cdata['name'],
                    $cdata['needed']
                ]);
            }

            fclose($out);
            exit;
        }
    }

    if ($exportType === 'global') {
        $filename = "kebutuhan_total_" . $start . "_to_" . $end . ".csv";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');

        // Info header
        fputcsv($out, ["TOTAL SEMUA PRODUK DIGABUNG"]);
        fputcsv($out, ["Periode", $start . " s/d " . $end]);
        fputcsv($out, []);

        // Header tabel
        fputcsv($out, ["Komponen", "Total Kebutuhan"]);

        // Data gabungan
        foreach ($globalReq as $row) {
            fputcsv($out, [
                $row['name'],
                $row['needed']
            ]);
        }

        fclose($out);
        exit;
    }

    // kalau parameter export gak valid, lanjut render halaman normal di bawah
}

// Render halaman normal (HTML)
require_once 'partials/header.php';
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white"><strong>Hitung Kebutuhan Komponen</strong></div>
    <div class="card-body">
        <form class="row g-2 mb-4" method="get">
            <div class="col-sm-4">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($start) ?>">
            </div>
            <div class="col-sm-4">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($end) ?>">
            </div>
            <div class="col-sm-4 d-flex align-items-end">
                <button class="btn btn-primary w-100">Lihat</button>
            </div>
        </form>

        <?php if (empty($byProduct)): ?>
            <div class="alert alert-secondary">Belum ada order dalam range tanggal tersebut.</div>
        <?php else: ?>

            <!-- BAGIAN 1: Breakdown per Produk -->
            <?php foreach ($byProduct as $pid => $pdata): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap">
                        <div class="me-3">
                            <strong><?= htmlspecialchars($pdata['product_name']) ?></strong>
                            <div class="small text-white-50">
                                Total Order: <?= $pdata['total_order_qty'] ?> pcs
                            </div>
                            <div class="small text-white-50">
                                Produk ID: <?= $pid ?>
                            </div>
                            <div class="small text-white-50">
                                Periode: <?= $start ?> s/d <?= $end ?>
                            </div>
                        </div>

                        <div>
                            <a href="requirements.php?export=product&product_id=<?= $pid ?>&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>"
                                class="btn btn-sm btn-outline-light">
                                Export Produk Ini (CSV)
                            </a>
                        </div>
                    </div>

                    <div class="card-body table-responsive">
                        <?php if (empty($pdata['components'])): ?>
                            <div class="alert alert-warning mb-0">
                                Produk ini belum punya BOM (belum ada komponen).
                            </div>
                        <?php else: ?>
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Komponen</th>
                                        <th>Total Kebutuhan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pdata['components'] as $cid => $cdata): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cdata['name']) ?></td>
                                            <td><?= $cdata['needed'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- BAGIAN 2: Ringkasan Total Semua Produk Digabung -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between flex-wrap">
                    <div class="me-3">
                        <strong>Total Semua Produk Digabung</strong>
                        <div class="small text-white-50">
                            Periode: <?= $start ?> s/d <?= $end ?>
                        </div>
                        <div class="small text-white-50">
                            Ini buat nyiapin bahan total sekaligus.
                        </div>
                    </div>

                    <div>
                        <a href="requirements.php?export=global&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>"
                            class="btn btn-sm btn-outline-light">
                            Export Total Global (CSV)
                        </a>
                    </div>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th>Total Kebutuhan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($globalReq as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= $row['needed'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>