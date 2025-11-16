<?php
require_once __DIR__ . '/db.php';

/**
 * Ambil nilai setting (string) dari tabel app_settings (kolom: k, v).
 * Return $default kalau tidak ada.
 */
function settings_get(string $key, ?string $default = null): ?string
{
    $st = pdo()->prepare("SELECT v FROM app_settings WHERE k = ? LIMIT 1");
    $st->execute([$key]);
    $row = $st->fetch();
    if ($row && array_key_exists('v', $row)) {
        return (string) $row['v'];
    }
    return $default;
}

/** Ambil nilai setting boolean: '1' => true, selain itu false. */
function settings_get_bool(string $key, bool $default = false): bool
{
    $val = settings_get($key, $default ? '1' : '0');
    return $val === '1';
}

/** Set nilai setting (string). Insert/update by key (k). */
function settings_set(string $key, string $val): void
{
    // Upsert sederhana: coba update dulu; jika 0 row affected -> insert
    $st = pdo()->prepare("UPDATE app_settings SET v = ? WHERE k = ?");
    $st->execute([$val, $key]);
    if ($st->rowCount() === 0) {
        $ins = pdo()->prepare("INSERT INTO app_settings (k, v) VALUES (?, ?)");
        $ins->execute([$key, $val]);
    }
}

/** Set nilai setting (boolean). */
function settings_set_bool(string $key, bool $val): void
{
    settings_set($key, $val ? '1' : '0');
}
