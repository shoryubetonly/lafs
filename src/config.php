<?php
// ==========================================
// 📁 ไฟล์ src/config.php (สำหรับรันบน Docker / Local)
// ==========================================

// 1. ความลับของ Google Login
define('GOOGLE_CLIENT_ID', '528445645740-03tu4jaoovaj4ndmqsjt13474nc08elo.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// 2. ตั้งค่าฐานข้อมูล (สำหรับรันใน Docker)
define('DB_HOST', 'db');                // ⚠️ สำคัญมาก: ใน Docker ต้องใช้คำว่า 'db'
define('DB_NAME', 'lafs');              // ตรงกับ docker-compose.yml
define('DB_USER', 'lafs');              // ตรงกับ docker-compose.yml
define('DB_PASS', 'bncclafsconfig');    // ตรงกับ docker-compose.yml

// 3. สร้างการเชื่อมต่อ $pdo
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage());
}
?>