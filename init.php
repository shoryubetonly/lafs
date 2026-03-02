<?php
// init.php — เริ่ม session + include ส่วนกลาง
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config.php';    // สร้าง $pdo ผ่าน db() ภายใน
require_once __DIR__ . '/functions.php'; // esc(), csrf_token(), csrf_verify(), redirect() ฯลฯ

// สร้าง CSRF token ล่วงหน้า (ใช้ key เดียวกับทั้งโปรเจกต์)
csrf_token(); // จะตั้ง $_SESSION['csrf'] ให้เองถ้ายังไม่มี

// (ทางเลือก) ตั้งค่า timezone ไทย หากยังไม่ได้ตั้งใน config.php
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Bangkok');
}
