<?php
/**
 * config.php — PDO connection (hardened) + env override + timezone/strict mode
 * ใช้ร่วมกับไฟล์อื่นๆ ในโปรเจกต์ lostfound/
 */

declare(strict_types=1);

/* ===== 1) ENV (สลับ dev/prod ได้) ===== */
if (!defined('APP_ENV')) {
  define('APP_ENV', 'dev'); // 'dev' หรือ 'prod'
}

/* ===== 2) Error reporting ตาม ENV ===== */
if (APP_ENV === 'dev') {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

/* ===== 3) Timezone ไทย (สำคัญกับ NOW(), date()) ===== */
date_default_timezone_set('Asia/Bangkok');

/* ===== 4) ค่าพื้นฐานการเชื่อมต่อ ===== */
$CFG = [
  'DB_HOST' => 'localhost',
  'DB_NAME' => 'lostfound_db',
  'DB_USER' => 'root',
  'DB_PASS' => '', // ถ้ามีรหัสผ่าน root ให้ใส่ที่นี่
];

/* ===== 5) Optional: override ด้วย .env.local.php (ถ้ามี) =====
 * สร้างไฟล์ .env.local.php แล้ว return array ที่จะ override $CFG
 * เช่น: return ['DB_PASS' => 'mypassword', 'DB_HOST' => '127.0.0.1'];
 */
$envLocal = __DIR__ . '/.env.local.php';
if (is_file($envLocal)) {
  $over = include $envLocal;
  if (is_array($over)) $CFG = array_merge($CFG, $over);
}

/* ===== 6) สร้าง PDO แบบ Singleton ผ่านฟังก์ชัน db() ===== */
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  global $CFG;
  $dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $CFG['DB_HOST'],
    $CFG['DB_NAME']
  );

  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    // เปิด persistent ได้ถ้าต้องการ: PDO::ATTR_PERSISTENT => true,
  ];

  try {
    $pdo = new PDO($dsn, $CFG['DB_USER'], $CFG['DB_PASS'], $options);

    // ตั้งค่าการทำงานของ session MySQL ให้เหมาะกับไทย + ป้องกันข้อมูลเพี้ยน
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("SET time_zone = '+07:00'");
    // โหมดเข้มงวดช่วยจับข้อมูลผิดฟิลด์ตั้งแต่ INSERT/UPDATE
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

    // health check เบาๆ
    $pdo->query('SELECT 1');

  } catch (PDOException $e) {
    if (APP_ENV === 'dev') {
      // โหมด dev แสดง error ชัดเจน (ไม่ควรใช้ใน prod)
      die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }
    http_response_code(500);
    die('Database connection failed.');
  }

  return $pdo;
}

/* ===== 7) ช่วยให้ไฟล์อื่น ๆ ใช้ตัวแปร $pdo เดิมได้ หากเขียนสไตล์เดิม ===== */
$pdo = db();
