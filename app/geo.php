<?php
require_once __DIR__ . '/db.php';

/** Haversine distance dalam meter */
function geo_distance_m(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $R = 6371000.0; // meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

/** Ambil semua lokasi aktif */
function geo_active_locations(): array
{
    $st = pdo()->query("SELECT * FROM locations WHERE active=1 ORDER BY id DESC");
    return $st->fetchAll();
}

/**
 * Cari lokasi aktif terdekat.
 * Return: ['loc'=>row|NULL, 'distance_m'=>float|NULL, 'ok'=>bool]
 */
function geo_nearest_ok(float $lat, float $lng): array
{
    $locs = geo_active_locations();
    $best = null;
    $bestDist = null;
    foreach ($locs as $L) {
        $d = geo_distance_m($lat, $lng, floatval($L['lat']), floatval($L['lng']));
        if ($best === null || $d < $bestDist) {
            $best = $L;
            $bestDist = $d;
        }
    }
    if ($best === null) {
        return ['loc' => null, 'distance_m' => null, 'ok' => false];
    }
    $ok = $bestDist <= intval($best['radius_m']);
    return ['loc' => $best, 'distance_m' => $bestDist, 'ok' => $ok];
}
