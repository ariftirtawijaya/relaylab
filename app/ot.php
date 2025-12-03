<?php
// app/ot.php
require_once __DIR__ . '/db.php';

/**
 * Ambil multiplier lembur untuk user & tanggal tertentu.
 * - Jika ada di tabel overtime_multiplier → pakai nilai tersebut.
 * - Jika tidak ada → default 1.0
 * - Jika nilai <= 0 → dianggap 0 (tidak dibayar lemburnya).
 */
function ot_get_multiplier(int $userId, string $ymd): float
{
    try {
        $pdo = pdo();
        $st = $pdo->prepare("
            SELECT multiplier 
            FROM overtime_multiplier 
            WHERE user_id = ? AND work_date = ? 
            LIMIT 1
        ");
        $st->execute([$userId, $ymd]);
        $row = $st->fetch();
        if ($row) {
            $val = (float) $row['multiplier'];
            if ($val <= 0) {
                return 0.0;
            }
            return $val;
        }
    } catch (Throwable $e) {
        // fallback kalau ada error DB
        error_log('ot_get_multiplier error: ' . $e->getMessage());
    }
    return 1.0;
}
