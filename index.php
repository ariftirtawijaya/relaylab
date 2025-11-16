<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/helpers.php';

if (!me_id()) {
    header('Location: ' . url('/login.php'));
    exit;
}
if (me_role() === 'admin') {
    header('Location: ' . url('/admin_employees.php'));
} else {
    header('Location: ' . url('/pegawai.php'));
}
