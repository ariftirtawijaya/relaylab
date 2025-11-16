<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

// Ambil setting by key
function app_get_setting(string $key, $default = null)
{
    $st = pdo()->prepare("SELECT v FROM app_settings WHERE k=? LIMIT 1");
    $st->execute([$key]);
    $row = $st->fetch();
    return $row ? $row['v'] : $default;
}

// Set setting (upsert)
function app_set_setting(string $key, $value): void
{
    $st = pdo()->prepare("INSERT INTO app_settings (k,v) VALUES(?,?)
                        ON DUPLICATE KEY UPDATE v=VALUES(v)");
    $st->execute([$key, $value]);
}

// Hapus/clear setting (set NULL)
function app_clear_setting(string $key): void
{
    $st = pdo()->prepare("UPDATE app_settings SET v=NULL WHERE k=?");
    $st->execute([$key]);
}

/**
 * Sumber kebenaran waktu aplikasi.
 * Jika sim_now ada (format 'Y-m-d H:i:s'), gunakan itu.
 * Jika tidak, pakai DateTime('now') normal.
 */
function app_now(): DateTime
{
    $sim = app_get_setting('sim_now', null);
    if ($sim) {
        // Pastikan string valid
        try {
            return new DateTime($sim); // Asia/Jakarta sudah di-set di config.php
        } catch (Throwable $e) { /* fallback ke now */
        }
    }
    return new DateTime('now');
}

/** True jika mode simulasi aktif */
function app_sim_active(): bool
{
    return app_get_setting('sim_now', null) !== null;
}
