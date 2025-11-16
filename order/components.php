<?php
require_once 'config/db.php';
require_once 'partials/header.php';

/*
------------------------------------------
TAMBAH KOMPONEN BARU
------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_component'])) {
    $name = $conn->real_escape_string($_POST['name']);

    if ($name !== '') {
        $conn->query("INSERT INTO components (name) VALUES ('$name')");
    }
    header("Location: components.php");
    exit;
}

/*
------------------------------------------
UPDATE / EDIT KOMPONEN
------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_component'])) {
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);

    if ($id > 0 && $name !== '') {
        $conn->query("UPDATE components SET name='$name' WHERE id=$id");
    }
    header("Location: components.php");
    exit;
}

/*
------------------------------------------
HAPUS KOMPONEN
------------------------------------------
*/
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM components WHERE id=$del_id");
    header("Location: components.php");
    exit;
}

/*
------------------------------------------
MODE EDIT (AMBIL DATA)
------------------------------------------
*/
$editData = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $resEdit = $conn->query("SELECT * FROM components WHERE id=$edit_id LIMIT 1");
    if ($resEdit && $resEdit->num_rows > 0) {
        $editData = $resEdit->fetch_assoc();
    }
}

/*
------------------------------------------
LIST SEMUA KOMPONEN
------------------------------------------
*/
$resComp = $conn->query("SELECT * FROM components ORDER BY LOWER(name) ASC");

?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <strong><?= $editData ? 'Edit Komponen' : 'Tambah Komponen Baru' ?></strong>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-2">
                        <label class="form-label">Nama Komponen</label>
                        <input type="text" name="name" class="form-control" required autofocus
                            placeholder="Contoh: Merah Besar 60"
                            value="<?= htmlspecialchars($editData['name'] ?? '') ?>">
                    </div>

                    <?php if ($editData): ?>
                        <button class="btn btn-primary w-100 mb-2" name="update_component">Simpan Perubahan</button>
                        <a href="components.php" class="btn btn-secondary w-100">Batal Edit</a>
                    <?php else: ?>
                        <button class="btn btn-primary w-100" name="add_component">Simpan</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white"><strong>Daftar Komponen</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nama Komponen</th>
                            <th width="1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = $resComp->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary mb-1"
                                        href="components.php?edit_id=<?= $c['id'] ?>">Edit</a>
                                    <a class="btn btn-sm btn-outline-danger" href="components.php?delete_id=<?= $c['id'] ?>"
                                        onclick="return confirm('Yakin hapus komponen ini?');">Hapus</a>
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