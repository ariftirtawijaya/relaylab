<?php
require_once __DIR__ . '/helpers.php';

/**
 * Upload bukti transfer kasbon.
 * - Terima: jpg/jpeg/png/webp/pdf
 * - Max ~ 5MB
 * Return: relative path (mis. /uploads/kasbon/abc123.png) atau null kalau tidak ada file.
 * Lempar Exception jika gagal.
 */
function kasbon_upload_proof(array $file, int $kasbonId): ?string
{
    if (empty($file) || !isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // tidak ada file di-request
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal (kode: ' . $file['error'] . ').');
    }

    $max = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max) {
        throw new RuntimeException('Ukuran file melebihi 5MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf'
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Tipe file tidak diizinkan. Hanya JPG/PNG/WEBP/PDF.');
    }

    $ext = $allowed[$mime];
    $dirFs = dirname(__DIR__) . '/uploads/kasbon';     // path fisik
    $dirUrl = '/uploads/kasbon';               // URL base (sesuai URL_BASE Kang: /presensi/public -> root /presensi)

    if (!is_dir($dirFs)) {
        if (!mkdir($dirFs, 0755, true)) {
            throw new RuntimeException('Folder upload tidak bisa dibuat.');
        }
    }

    // Nama file aman: kasbon-{id}-{random}.{ext}
    $name = 'kasbon-' . $kasbonId . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dirFs . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Gagal menyimpan file.');
    }

    // Optional: set permission
    @chmod($dest, 0644);

    return $dirUrl . '/' . $name;
}
