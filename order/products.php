<?php
require_once 'config/db.php';
require_once 'partials/header.php';

/*
------------------------------------------
TAMBAH PRODUK BARU
------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);

    if ($name !== '') {
        $conn->query("INSERT INTO products (name) VALUES ('$name')");
    }

    header("Location: products.php");
    exit;
}

/*
------------------------------------------
UPDATE PRODUK (EDIT)
------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);

    if ($id > 0 && $name !== '') {
        $conn->query("UPDATE products SET name='$name' WHERE id=$id");
    }

    header("Location: products.php");
    exit;
}

/*
------------------------------------------
HAPUS PRODUK
------------------------------------------
*/
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM products WHERE id=$del_id");
    header("Location: products.php");
    exit;
}

/*
------------------------------------------
MODE EDIT (AMBIL DATA PRODUK YANG MAU DIUBAH)
------------------------------------------
*/
$editData = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $resEdit = $conn->query("SELECT * FROM products WHERE id=$edit_id LIMIT 1");
    if ($resEdit && $resEdit->num_rows > 0) {
        $editData = $resEdit->fetch_assoc();
    }
}

/*
------------------------------------------
LIST SEMUA PRODUK
------------------------------------------
*/
$resProducts = $conn->query("SELECT * FROM products ORDER BY LOWER(name) ASC");
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <strong><?= $editData ? 'Edit Produk' : 'Tambah Produk Baru' ?></strong>
            </div>

            <div class="card-body">
                <form method="post">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" name="name" class="form-control" required autofocus
                            placeholder="Contoh: Relay Set BILED Foglamp"
                            value="<?= htmlspecialchars($editData['name'] ?? '') ?>">
                    </div>

                    <?php if ($editData): ?>
                        <button class="btn btn-primary w-100 mb-2" name="update_product">Simpan Perubahan</button>
                        <a class="btn btn-secondary w-100" href="products.php">Batal Edit</a>
                    <?php else: ?>
                        <button class="btn btn-primary w-100" name="add_product">Simpan</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white"><strong>Daftar Produk</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $resProducts->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary mb-1"
                                        href="products.php?edit_id=<?= $row['id'] ?>">Edit</a>
                                    <a class="btn btn-sm btn-outline-danger" href="products.php?delete_id=<?= $row['id'] ?>"
                                        onclick="return confirm('Yakin hapus produk ini?');">Hapus</a>
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