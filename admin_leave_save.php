<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_role('admin');

$user_id = intval($_POST['user_id']);
$date = $_POST['leave_date'];
$type = $_POST['type'];
$note = $_POST['note'] ?? '';

$admin = me_id();

$st = pdo()->prepare("
    INSERT INTO leave_days (user_id, leave_date, type, note, created_at, created_by)
    VALUES (?, ?, ?, ?, NOW(), ?)
    ON DUPLICATE KEY UPDATE type=VALUES(type), note=VALUES(note), created_by=VALUES(created_by)
");
$st->execute([$user_id, $date, $type, $note, $admin]);

header("Location: admin_leave.php?tgl=$date");
exit;
