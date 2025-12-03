<?php
require_once __DIR__ . '/db.php';

/**
 * Total kasbon yang SUDAH DISETUJUI (approved) pada bulan tertentu (Y-m),
 * dihitung berdasarkan tanggal approved (decided_at).
 */
function kasbon_total_approved_month(int $userId, string $ym): int
{
    $pdo = pdo();
    $st = $pdo->prepare("
        SELECT SUM(amount) AS total
        FROM cash_advances
        WHERE user_id = ?
          AND status = 'approved'
          AND decided_at IS NOT NULL
          AND DATE_FORMAT(decided_at, '%Y-%m') = ?
    ");
    $st->execute([$userId, $ym]);
    $row = $st->fetch();
    return (int) ($row['total'] ?? 0);
}

/**
 * Hitung info limit kasbon per bulan untuk satu user.
 *
 * @return array{
 *   salary:int,
 *   pct:int,
 *   limit:int,
 *   used:int,
 *   remain:int,
 *   ym:string
 * }
 */
function kasbon_limit_info(int $userId, ?string $ym = null): array
{
    $pdo = pdo();
    if ($ym === null) {
        $ym = date('Y-m');
    }

    $st = $pdo->prepare("SELECT salary, max_cash_advance_pct FROM users WHERE id=? LIMIT 1");
    $st->execute([$userId]);
    $u = $st->fetch();

    $salary = isset($u['salary']) ? (int) $u['salary'] : 0;
    $pct = isset($u['max_cash_advance_pct']) ? (int) $u['max_cash_advance_pct'] : 20;

    if ($pct < 0)
        $pct = 0;
    if ($pct > 100)
        $pct = 100;

    $limit = (int) floor($salary * $pct / 100);
    $used = $salary > 0 ? kasbon_total_approved_month($userId, $ym) : 0;
    $remain = max(0, $limit - $used);

    return [
        'salary' => $salary,
        'pct' => $pct,
        'limit' => $limit,
        'used' => $used,
        'remain' => $remain,
        'ym' => $ym,
    ];
}
