<?php
session_start();
require_once 'config.php';

$client_id = GOOGLE_CLIENT_ID; 

// ⚠️ เปลี่ยนกลับมาใช้ localhost สำหรับรันใน Docker
$redirect_uri = 'https://110.78.30.118/lafs/lafs/src/callback.php';

$url = "https://accounts.google.com/o/oauth2/v2/auth?client_id={$client_id}&redirect_uri={$redirect_uri}&response_type=code&scope=email profile&prompt=select_account";

header("Location: $url");
exit;
?>