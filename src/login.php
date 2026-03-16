<?php
session_start();

$client_id = '528445645740-03tu4jaoovaj4ndmqsjt13474nc08elo.apps.googleusercontent.com'; // เอา Client ID มาใส่ในเครื่องหมายคำพูดนี้
$redirect_uri = 'http://localhost:8088/callback.php';

// สร้าง URL สำหรับไปหน้า Login ของ Google
$url = "https://accounts.google.com/o/oauth2/v2/auth?client_id={$client_id}&redirect_uri={$redirect_uri}&response_type=code&scope=email profile&prompt=select_account";

// สั่ง Redirect เด้งไปหน้า Google
header("Location: $url");
exit;
?>