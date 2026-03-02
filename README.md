# Lost & Found (แผนก IT) — XAMPP + PHP + MySQL

## โครงสร้างโฟลเดอร์
```
lostfound/
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── img/no-image.png
├── uploads/                # โฟลเดอร์รูปอัปโหลด (ต้องให้สิทธิ์เขียน)
├── config.php              # ค่าเชื่อมต่อฐานข้อมูล PDO
├── init.php                # เรียกใช้ session + รวมฟังก์ชันพื้นฐาน
├── functions.php           # ฟังก์ชันช่วยเหลือ/ความปลอดภัย (escape, csrf ฯลฯ)
├── header.php              # ส่วนหัว + นำทาง (Navbar)
├── footer.php              # ส่วนท้าย
├── index.php               # หน้าแรก/ค้นหา/กรองรายการ
├── create.php              # ฟอร์มเพิ่มรายการของหาย/ของเจอ
├── save_item.php           # รับ POST จากฟอร์มแล้วบันทึก DB + อัปโหลดรูป
├── view.php                # หน้ารายละเอียดรายการ
├── claim.php               # เปลี่ยนสถานะเป็น "ส่งคืนแล้ว/ปิดเคส"
└── database.sql            # สคริปต์สร้างฐานข้อมูลและตัวอย่างข้อมูล
```

## วิธีติดตั้ง (บน Windows + XAMPP)
1) แตกไฟล์โฟลเดอร์ `lostfound` ไปไว้ที่:  
   `C:\xampp\htdocs\lostfound`
2) เปิด **XAMPP Control Panel** แล้ว Start `Apache` และ `MySQL`  
3) เปิด `http://localhost/phpmyadmin` แล้ว **Import** ไฟล์ `database.sql`
4) เปิด/แก้ไข `config.php` หากรหัสผ่าน root ของ MySQL ไม่ว่างเปล่า ให้แก้ให้ตรงกับเครื่องคุณ
5) ให้สิทธิ์เขียนโฟลเดอร์ `uploads/` (ปกติบน Windows ใช้ได้เลย)
6) เปิดเว็บ: `http://localhost/lostfound/`

## บัญชีตัวอย่าง (ถ้ามีระบบล็อกอินในอนาคต)
ตอนนี้เวอร์ชันนี้ยังไม่บังคับล็อกอิน เพื่อความง่ายในการใช้งานในแผนก
