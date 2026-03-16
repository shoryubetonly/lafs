<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit; }

// 1. เรียกใช้ไฟล์ config เพื่อเชื่อมต่อฐานข้อมูลด้วยรหัสผ่านใหม่ (แก้ปัญหา Access denied)
require_once 'config.php';

try {
    // 2. ตรวจสอบว่าเป็นแอดมินหรือไม่
    $stmt_admin = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_admin->execute([$_SESSION['user_id']]);
    $current_email = $stmt_admin->fetchColumn();
    
    // อีเมลแอดมิน (เปลี่ยนเป็นอีเมลของคุณเองได้เลยครับ)
    $is_admin = ($current_email === '66209010013@bncc.ac.th'); 

    if ($is_admin) {
        // แอดมินลบได้ทุกโพสต์
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } else {
        // คนทั่วไปลบได้แค่ของตัวเอง
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    }

    // ลบเสร็จให้เด้งกลับไปหน้าแรก
    header("Location: index.php");
    exit;
    
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage());
}
?>