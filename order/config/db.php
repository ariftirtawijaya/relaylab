<?php
$host = "localhost";
$user = "pupq3195_admin";
$pass = "0WJk,0)ttfAN(M1V";
$db = "pupq3195_order";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>