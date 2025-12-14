<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/helpers.php';

require_role('admin');

$id = (int) ($_POST['user_id'] ?? 0);

if ($id <= 0) {
    flash_set('error', 'ID pegawai tidak valid.');
    header('Location: ' . url('/admin_employees.php'));
    exit;
}

// Cegah admin hapus dirinya sendiri
if ($id === me_id()) {
    flash_set('error', 'Tidak bisa menghapus akun sendiri.');
    header('Location: ' . url('/admin_employees.php'));
    exit;
}

$pdo = pdo();

try {
    $pdo->beginTransaction();

    // Pastikan user ada & role pegawai
    $st = $pdo->prepare("SELECT id, name, role FROM users WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $user = $st->fetch();

    if (!$user || $user['role'] !== 'pegawai') {
        throw new Exception('Pegawai tidak ditemukan.');
    }

    // 1. Hapus overtime_multiplier (TIDAK ada FK cascade)
    $pdo->prepare("DELETE FROM overtime_multiplier WHERE user_id=?")
        ->execute([$id]);

    // 2. Hapus user (attendance, leave_days, cash_advances ikut CASCADE)
    $pdo->prepare("DELETE FROM users WHERE id=?")
        ->execute([$id]);

    $pdo->commit();

    flash_set('success', 'Pegawai "' . htmlspecialchars($user['name']) . '" dan seluruh datanya berhasil dihapus.');

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Delete pegawai error: ' . $e->getMessage());
    flash_set('error', 'Gagal menghapus pegawai.');
}

header('Location: ' . url('/admin_employees.php'));
exit;
