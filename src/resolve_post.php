<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: index.php"); exit; }

$host = 'db';
$dbname = 'lost_and_found_db';
$username = 'admin';
$password = 'password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $item_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) { header("Location: index.php"); exit; }

    // เช็กสิทธิ์ Admin
    $is_admin = false;
    $stmt_admin = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_admin->execute([$_SESSION['user_id']]);
    if ($stmt_admin->fetchColumn() === '66209010013@bncc.ac.th') { $is_admin = true; }

    if ($item['user_id'] != $_SESSION['user_id'] && !$is_admin) {
        header("Location: index.php"); exit;
    }

    $error_msg = '';

    // เมื่อมีการกดปุ่ม Submit ส่งคำตอบ
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $answer = trim($_POST['answer'] ?? '');
        $correct_answer = trim($item['secret_answer'] ?? '');

        // จุดที่แก้ไข: ตัด $is_admin ออก และบังคับตรวจคำตอบให้ตรงกัน (รองรับภาษาไทย)
        if (empty($item['secret_question']) || mb_strtolower($answer, 'UTF-8') === mb_strtolower($correct_answer, 'UTF-8')) {
            
            // ปิดเคส
            $pdo->prepare("UPDATE items SET status = 'RESOLVED' WHERE id = ?")->execute([$item_id]);
            
            // แจกแต้มฮีโร่ +10 คะแนนให้คนปิดเคส
            $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?")->execute([$_SESSION['user_id']]);
            
            header("Location: index.php");
            exit;
        } else {
            $error_msg = '❌ คำตอบไม่ถูกต้อง! ไม่อนุญาตให้ปิดเคสครับ ลองถามผู้รับของอีกครั้งนะ';
        }
    }

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการปิดเคส - CampusFinds</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', 'Noto Sans Thai', sans-serif; } </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-800 overflow-hidden relative">
        <div class="h-2 w-full bg-blue-500"></div>
        
        <div class="p-8">
            <div class="w-16 h-16 bg-blue-500/10 text-blue-500 rounded-full flex items-center justify-center text-3xl mb-6 mx-auto border border-blue-500/20">
                🔐
            </div>
            
            <h2 class="text-2xl font-black text-white text-center mb-2">ยืนยันการส่งมอบของ</h2>
            <p class="text-xs font-bold text-slate-400 text-center mb-8">
                เพื่อความปลอดภัย กรุณาสอบถาม <span class="text-blue-400">"คำถามลับ"</span> จากผู้ที่มารับของ หากตอบถูกถึงจะสามารถกดปิดเคสได้
            </p>

            <?php if ($error_msg): ?>
                <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 p-4 rounded-2xl text-xs font-bold text-center mb-6">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                
                <?php if (!empty($item['secret_question'])): ?>
                    <div class="bg-slate-950/50 p-5 rounded-2xl border border-slate-800 text-center">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">คำถามลับที่ตั้งไว้</p>
                        <p class="text-lg font-bold text-slate-200">"<?= htmlspecialchars($item['secret_question']) ?>"</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">กรอกคำตอบที่ผู้รับตอบ</label>
                        <input type="text" name="answer" required autofocus placeholder="พิมพ์คำตอบที่นี่..." class="w-full px-5 py-4 bg-slate-950 border border-slate-700 text-white rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-medium text-sm text-center">
                    </div>
                <?php else: ?>
                    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-2xl text-xs font-bold text-center mb-6">
                        ✅ โพสต์นี้ไม่ได้ตั้งคำถามลับไว้ สามารถกดยืนยันปิดเคสได้เลย
                    </div>
                <?php endif; ?>

                <div class="flex gap-3 pt-4">
                    <a href="index.php" class="flex-1 bg-slate-800 text-slate-300 py-3.5 rounded-2xl text-xs font-black text-center border border-slate-700 hover:bg-slate-700 transition-all uppercase">ยกเลิก</a>
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3.5 rounded-2xl text-xs font-black text-center shadow-lg shadow-blue-900/20 hover:bg-blue-500 transition-all uppercase">
                        ปิดเคส (Resolved)
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>