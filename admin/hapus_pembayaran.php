<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin_login.php");
    exit();
}
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM pembayaran WHERE id_pembayaran = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: laporan.php?bulan=$bulan&tahun=$tahun&deleted=1");
exit();
