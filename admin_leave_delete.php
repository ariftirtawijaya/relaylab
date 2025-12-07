<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_role('admin');

$id = intval($_GET['id']);
$tgl = $_GET['tgl'] ?? date('Y-m-d');

$st = pdo()->prepare("DELETE FROM leave_days WHERE id=? LIMIT 1");
$st->execute([$id]);

header("Location: admin_leave.php?tgl=$tgl");
exit;
