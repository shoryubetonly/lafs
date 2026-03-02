<?php
// admin_logout.php — ออกจากระบบผู้ดูแล (บังคับ POST + CSRF)
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  flash_set('error', 'วิธีการออกจากระบบไม่ถูกต้อง');
  redirect('/lostfound/');
}

csrf_verify();

if (!empty($_SESSION['admin'])) {
  unset($_SESSION['admin']);          // ล้างข้อมูลแอดมินออกจากเซสชัน
  session_regenerate_id(true);        // เปลี่ยน session id กัน fixation
}

flash_set('success', 'ออกจากระบบผู้ดูแลเรียบร้อยแล้ว');
redirect('/lostfound/');
