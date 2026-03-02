-- =========================================================
-- database.sql — Lost & Found (XAMPP + PHP + MySQL)
-- เวอร์ชันล่าสุด: รองรับรูปโปรไฟล์แอดมิน + root role
-- ⚠️ สคริปต์นี้จะลบฐานข้อมูลเดิมและสร้างใหม่ทั้งหมด
-- =========================================================

/* 1) สร้างฐานข้อมูลใหม่ */
DROP DATABASE IF EXISTS lostfound_db;
CREATE DATABASE lostfound_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE lostfound_db;

/* 2) ตารางประกาศหลัก */
CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('lost','found') NOT NULL,                 -- ของหาย/เจอของ
  category VARCHAR(50) NOT NULL,                      -- หมวดหมู่
  title VARCHAR(120) NOT NULL,                        -- หัวข้อ
  description TEXT NOT NULL,                          -- รายละเอียด
  location VARCHAR(120) NOT NULL,                     -- สถานที่
  date_event DATE NOT NULL,                           -- วันที่เกิดเหตุ
  contact_name VARCHAR(80) NOT NULL,                  -- ผู้ติดต่อ
  contact_phone VARCHAR(50) NOT NULL,                 -- ช่องทางติดต่อ
  image_path VARCHAR(255) DEFAULT NULL,               -- (สคีมาเก่า) path รูป
  image_url  VARCHAR(255) DEFAULT NULL,               -- (สคีมาใหม่) url/พาธรูปหน้าปก
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (type), INDEX (category), INDEX (status),
  FULLTEXT KEY ft_title_desc (title, description)
) ENGINE=InnoDB;

/* 3) ตารางรูปของประกาศ (รองรับหลายรูป) */
CREATE TABLE item_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_item_images_item
    FOREIGN KEY (item_id) REFERENCES items(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX (item_id), INDEX (created_at)
) ENGINE=InnoDB;

/* 4) ข้อมูลตัวอย่างประกาศ */
INSERT INTO items
(type, category, title, description, location, date_event, contact_name, contact_phone, image_path, image_url, status, created_at) VALUES
('lost','electronics','ทำหูฟังไร้สายหาย (สีขาว)','หูฟัง TWS สีขาว เคสมีสติ๊กเกอร์รูปสายฟ้า','ตึก B ชั้น 3 หน้าห้อง 305','2025-09-25','ต๊ะ','08x-xxx-xxxx',NULL,NULL,'open', NOW() - INTERVAL 2 DAY),
('found','idcard','เจอบัตรนักศึกษา','เก็บได้แถวโรงอาหาร ฝั่งตึก A หลังเที่ยง','โรงอาหาร A','2025-09-28','บีม','Line: beem_it',NULL,NULL,'open', NOW() - INTERVAL 1 DAY),
('lost','stationery','ทำแฟลชไดรฟ์หาย 32GB','สีดำ ยี่ห้อ Kingston มีพวงกุญแจรูปคีย์บอร์ด','ห้องปฏิบัติการเครือข่าย','2025-09-29','เจ','FB: Jay Jay',NULL,NULL,'open', NOW());

/* 5) ตารางผู้ดูแลระบบ (รองรับโปรไฟล์รูป + role root/admin) */
CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) DEFAULT NULL,
  profile_image_url VARCHAR(255) DEFAULT NULL,        -- เก็บพาธ/URL รูปโปรไฟล์
  role ENUM('root','admin') NOT NULL DEFAULT 'admin', -- root สิทธิ์สูงสุด
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

/* 6) ตารางบันทึกประวัติล็อกอินแอดมิน */
CREATE TABLE admin_login_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  ok TINYINT(1) NOT NULL DEFAULT 0,                   -- 1=ผ่าน, 0=ไม่ผ่าน
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (username), INDEX (created_at)
) ENGINE=InnoDB;

/* 7) ผู้ใช้เริ่มต้น: root / password (bcrypt) */
INSERT INTO admin_users (username, password_hash, display_name, profile_image_url, role, is_active, created_at)
VALUES (
  'root',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- "password"
  'Root Admin',
  NULL,       -- ยังไม่ตั้งรูป -> UI จะแสดงไอคอนสีเทาแทน
  'root',
  1,
  NOW()
);
 UPDATE admin_users
 SET profile_image_url = '/lostfound/assets/img/account_circle.png'
WHERE username='root';
 

