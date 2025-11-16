<?php
require_once 'config/db.php';
require_once 'partials/header.php';

// ambil semua produk untuk dropdown
$allProducts = $conn->query("SELECT id, name FROM products ORDER BY LOWER(name) ASC");

// produk yang dipilih sekarang
$currentProductId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$currentProductName = '';
if ($currentProductId > 0) {
    $resName = $conn->query("SELECT name FROM products WHERE id = $currentProductId LIMIT 1");
    if ($resName && $resName->num_rows > 0) {
        $currentProductName = $resName->fetch_assoc()['name'];
    }
}

/*
------------------------------------------
TAMBAH / UPDATE ITEM BOM UNTUK PRODUK
------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bom_item'])) {
    $product_id = intval($_POST['product_id']);
    $component_id = intval($_POST['component_id']);
    $qty = floatval($_POST['qty_per_product']);

    // INSERT kalau belum ada, UPDATE kalau sudah ada (pakai UNIQUE (product_id, component_id))
    $conn->query("
        INSERT INTO bom_items (product_id, component_id, qty_per_product)
        VALUES ($product_id, $component_id, $qty)
        ON DUPLICATE KEY UPDATE qty_per_product = $qty
    ");

    header("Location: bom.php?product_id=" . $product_id);
    exit;
}

/*
------------------------------------------
HAPUS ITEM BOM DARI PRODUK
------------------------------------------
*/
if (isset($_GET['delete_bom_id']) && $currentProductId > 0) {
    $bom_id = intval($_GET['delete_bom_id']);
    $conn->query("DELETE FROM bom_items WHERE id=$bom_id");
    header("Location: bom.php?product_id=" . $currentProductId);
    exit;
}

/*
------------------------------------------
AMBIL LIST KOMPONEN (untuk dropdown tambah ke BOM)
------------------------------------------
*/
$allComponents = $conn->query("SELECT id, name FROM components ORDER BY name ASC");

/*
------------------------------------------
AMBIL BOM UNTUK PRODUK YANG DIPILIH
------------------------------------------
*/
$currBOM = null;
if ($currentProductId > 0) {
    $currBOM = $conn->query("
        SELECT b.id,
               c.name AS component_name,
               b.qty_per_product
        FROM bom_items b
        JOIN components c ON c.id = b.component_id
        WHERE b.product_id = $currentProductId
        ORDER BY c.name ASC
    ");
}
?>

<div class="row">
    <div class="col-lg-4">
        <!-- PILIH PRODUK -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white"><strong>Pilih Produk</strong></div>
            <div class="card-body">
                <form method="get">
                    <div class="mb-2">
                        <label class="form-label">Produk</label>
                        <select class="form-select" name="product_id" onchange="this.form.submit()">
                            <option value="0">-- pilih produk --</option>
                            <?php while ($p = $allProducts->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" <?= ($p['id'] == $currentProductId ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- FORM TAMBAH KOMPONEN KE BOM -->
        <?php if ($currentProductId > 0): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <strong>Tambah Komponen ke BOM</strong>
                    <div class="small text-white-50">
                        <?= htmlspecialchars($currentProductName) ?>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="product_id" value="<?= $currentProductId ?>">

                        <div class="mb-2">
                            <label class="form-label">Komponen</label>
                            <select class="form-select js-component-select" name="component_id" required>
                                <option value="">-- pilih / cari komponen --</option>
                                <?php
                                $allComponents2 = $conn->query("SELECT id, name FROM components ORDER BY LOWER(name) ASC");
                                while ($c = $allComponents2->fetch_assoc()):
                                    ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>

                        </div>

                        <div class="mb-2">
                            <label class="form-label">Qty per 1 Produk</label>
                            <input type="number" step="0.01" class="form-control" name="qty_per_product"
                                id="qty_per_product_input" required placeholder="contoh: 2">
                        </div>

                        <button class="btn btn-primary w-100" name="add_bom_item">Simpan / Update</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <?php if ($currentProductId == 0): ?>
            <div class="alert alert-secondary shadow-sm">
                Silakan pilih produk terlebih dahulu di sebelah kiri 👈
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <strong>BOM untuk <?= $currentProductName ?></strong>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th>Qty / Produk</th>
                                <th width="1">Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($currBOM && $currBOM->num_rows > 0): ?>
                                <?php while ($b = $currBOM->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($b['component_name']) ?></td>
                                        <td><?= $b['qty_per_product'] ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-danger"
                                                href="bom.php?product_id=<?= $currentProductId ?>&delete_bom_id=<?= $b['id'] ?>"
                                                onclick="return confirm('Hapus komponen ini dari BOM produk ini?');">
                                                X
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-muted">Belum ada komponen untuk produk ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>