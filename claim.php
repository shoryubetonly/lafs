<?php
// claim.php
require_once __DIR__ . '/init.php';
csrf_verify();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'รหัสรายการไม่ถูกต้อง');
    redirect('index.php');
}

// ตรวจสอบว่ามีรายการและยังเปิดเคสอยู่ไหม
$chk = $pdo->prepare("SELECT status FROM items WHERE id = :id");
$chk->execute([':id' => $id]);
$row = $chk->fetch();

if (!$row) {
    flash_set('error', 'ไม่พบรายการที่ต้องการ');
    redirect('index.php');
}
if ($row['status'] === 'closed') {
    flash_set('info', 'รายการนี้ปิดเคสไว้แล้ว');
    redirect('view.php?id=' . $id);
}

// อัปเดตสถานะ
$stmt = $pdo->prepare("UPDATE items SET status = 'closed' WHERE id = :id");
$stmt->execute([':id' => $id]);

flash_set('success', 'ปิดเคสเรียบร้อยแล้ว ✅');
redirect('view.php?id=' . $id);
