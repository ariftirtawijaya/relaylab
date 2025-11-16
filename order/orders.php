<?php
require_once 'config/db.php';
require_once 'partials/header.php';

/*
------------------------------------------
TAMBAH ORDER BARU
------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order'])) {
    $order_date = $conn->real_escape_string($_POST['order_date']);
    $product_id = intval($_POST['product_id']);
    $qty_ordered = intval($_POST['qty_ordered']);

    if ($qty_ordered > 0 && $product_id > 0 && $order_date !== '') {
        $conn->query("INSERT INTO orders (order_date, product_id, qty_ordered)
                      VALUES ('$order_date', $product_id, $qty_ordered)");
    }

    header("Location: orders.php");
    exit;
}

/*
------------------------------------------
HAPUS ORDER
------------------------------------------
*/
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM orders WHERE id=$del_id");
    header("Location: orders.php");
    exit;
}

/*
------------------------------------------
DROPDOWN PRODUK UNTUK FORM INPUT
------------------------------------------
*/
$prodRes = $conn->query("SELECT id, name FROM products ORDER BY name ASC");

/*
------------------------------------------
AMBIL LIST ORDER TERBARU
------------------------------------------
*/
$listOrders = $conn->query("
    SELECT o.id,
           o.order_date,
           o.qty_ordered,
           p.name AS product_name
    FROM orders o
    JOIN products p ON p.id = o.product_id
    ORDER BY o.order_date DESC, o.id DESC
    LIMIT 50
");
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white"><strong>Input Order Baru</strong></div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-2">
                        <label class="form-label">Tanggal Order</label>
                        <input type="date" name="order_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Produk</label>
                        <select class="form-select" name="product_id" required>
                            <?php while ($p = $prodRes->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Jumlah (pcs)</label>
                        <input type="number" name="qty_ordered" class="form-control" required placeholder="misal 10">
                    </div>

                    <button class="btn btn-primary w-100" name="add_order">Simpan</button>
                </form>
            </div>
        </div>

    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white"><strong>Riwayat Order Terbaru</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($o = $listOrders->fetch_assoc()): ?>
                            <tr>
                                <td><?= $o['order_date'] ?></td>
                                <td><?= htmlspecialchars($o['product_name']) ?></td>
                                <td><?= $o['qty_ordered'] ?></td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-danger" href="orders.php?delete_id=<?= $o['id'] ?>"
                                        onclick="return confirm('Hapus order ini?');">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>