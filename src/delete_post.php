<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit; }

$pdo = new PDO("mysql:host=db;dbname=lost_and_found_db;charset=utf8mb4", "admin", "password123");

// ตรวจสอบว่าเป็นแอดมินหรือไม่
$stmt_admin = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt_admin->execute([$_SESSION['user_id']]);
$current_email = $stmt_admin->fetchColumn();
$is_admin = ($current_email === '66209010013@bncc.ac.th'); // อีเมลแอดมิน

if ($is_admin) {
    // แอดมินลบได้ทุกโพสต์
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$_GET['id']]);
} else {
    // คนทั่วไปลบได้แค่ของตัวเอง
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
}

header("Location: index.php");
exit;
?>