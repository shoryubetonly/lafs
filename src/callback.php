<?php
session_start();

// 1. ดึงไฟล์ความลับเข้ามา
require_once 'config.php'; 

// 2. ตั้งค่าตัวแปรโดยดึงความลับจาก config.php มาใช้
$client_id = '528445645740-03tu4jaoovaj4ndmqsjt13474nc08elo.apps.googleusercontent.com'; // Client ID วางทิ้งไว้ได้ ไม่ใช่ความลับ
$client_secret = GOOGLE_CLIENT_SECRET; // <--- ดึงรหัสมาจาก config.php (ไม่โชว์รหัสจริงในหน้านี้แล้ว!)
$redirect_uri = 'http://localhost:8088/callback.php';

if (isset($_GET['code'])) {
    // 1. นำ Code ที่ได้ไปแลกเป็น Token
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
        // 2. ดึงข้อมูลอีเมลและชื่อผู้ใช้จาก Google
        $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $options = ['http' => ['header' => "Authorization: Bearer " . $token_data['access_token'] . "\r\n"]];
        $context = stream_context_create($options);
        $user_response = file_get_contents($user_info_url, false, $context);
        $user_info = json_decode($user_response, true);

        $email = $user_info['email'];
        $name = $user_info['name'];

        // 3. ตรวจสอบว่าเป็นอีเมลของวิทยาลัยหรือไม่
        if (strpos($email, '@bncc.ac.th') === false) {
            die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
                    <h2 style='color:red;'>เข้าสู่ระบบไม่สำเร็จ</h2>
                    <p>ต้องใช้อีเมลของวิทยาลัย (@bncc.ac.th) ในการเข้าใช้งานเท่านั้นครับ</p>
                    <a href='index.php'>กลับหน้าแรก</a>
                 </div>");
        }

        // 4. บันทึกข้อมูลลง Database
        $pdo = new PDO("mysql:host=db;dbname=lost_and_found_db;charset=utf8mb4", "admin", "password123");
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // ถ้ายังไม่เคย Login ให้เพิ่มลงตาราง users ใหม่
            $stmt = $pdo->prepare("INSERT INTO users (email, display_name) VALUES (?, ?)");
            $stmt->execute([$email, $name]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $user['id'];
        }

        // 5. บันทึกสถานะการล็อกอินลง Session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;

        header("Location: index.php");
        exit;
    }
}
?>