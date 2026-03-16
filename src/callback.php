<?php
session_start();

// 1. โหลดไฟล์ตั้งค่า (เพราะอยู่โฟลเดอร์ src เหมือนกัน เลยใช้ชื่อไฟล์ตรงๆ ได้เลย)
require_once 'config.php'; 

// 2. ดึงรหัส Google จาก config.php
$client_id     = GOOGLE_CLIENT_ID;
$client_secret = GOOGLE_CLIENT_SECRET;

// ⚠️ แก้ตรงนี้เป็น URL จริงของเซิร์ฟเวอร์วิทยาลัย (ห้ามมีคำว่า src)
$redirect_uri = 'https://110.78.30.118/lafs/lafs/src/callback.php';

if (isset($_GET['code'])) {
    try {
        // แลก Code เป็น Token
        $token_url = 'https://oauth2.googleapis.com/token';
        $data = [
            'code' => $_GET['code'],
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context  = stream_context_create($options);
        $response = file_get_contents($token_url, false, $context);
        $token_data = json_decode($response, true);

        if (isset($token_data['access_token'])) {
            // ดึงข้อมูลผู้ใช้จาก Google (รอบนี้เราจะเอา picture มาด้วย!)
            $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
            $options = ['http' => ['header' => "Authorization: Bearer " . $token_data['access_token'] . "\r\n"]];
            $context = stream_context_create($options);
            $user_response = file_get_contents($user_info_url, false, $context);
            $user_info = json_decode($user_response, true);

            $email = $user_info['email'];
            $name = $user_info['name'];
            $picture = $user_info['picture'] ?? null; // ✨ ดูดรูปลิงก์จาก Google

            // เช็กว่าเป็นเมลวิทยาลัยไหม
            if (strpos($email, '@bncc.ac.th') === false) {
                die("<div style='text-align:center; padding:50px; font-family:sans-serif; background:#0f172a; color:white; min-height:100vh;'>
                        <h2 style='color:#f43f5e;'>❌ เข้าสู่ระบบไม่สำเร็จ</h2>
                        <p style='color:#94a3b8;'>ระบบนี้อนุญาตเฉพาะนักศึกษาและบุคลากร BNCC เท่านั้นครับ</p>
                        <br>
                        <a href='index.php' style='color:#3b82f6; text-decoration:none;'>กลับหน้าแรก</a>
                     </div>");
            }

            // เช็กว่าเคยมีบัญชีหรือยัง
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // สร้างใหม่ พร้อมเซฟรูปลงฐานข้อมูล
                $stmt = $pdo->prepare("INSERT INTO users (email, display_name, profile_image) VALUES (?, ?, ?)");
                $stmt->execute([$email, $name, $picture]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $user['id'];
                // ถ้ามีบัญชีอยู่แล้ว ให้อัปเดตรูปใหม่ล่าสุดเสมอเผื่อเขาเปลี่ยนรูปใน Google
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ?, display_name = ? WHERE id = ?");
                $stmt->execute([$picture, $name, $user_id]);
            }

            // บันทึก Session (เอาลิงก์รูปไปใช้ในหน้าอื่นได้ด้วย)
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_picture'] = $picture; // ✨ เก็บรูปลง Session

            header("Location: index.php");
            exit;
        }
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาดในการล็อกอิน: " . $e->getMessage());
    }
}
?>