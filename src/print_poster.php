<?php
session_start();
if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

// ดึงการเชื่อมต่อฐานข้อมูลจาก config.php มาใช้
require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT items.*, users.display_name FROM items JOIN users ON items.user_id = users.id WHERE items.id = ?");
    $stmt->execute([$_GET['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) { header("Location: index.php"); exit; }

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

// 🚨 สำคัญมาก: เมื่อนำขึ้นเซิร์ฟเวอร์จริง อย่าลืมเปลี่ยน http://localhost:8088 ให้เป็นชื่อโดเมน หรือ IP เซิร์ฟเวอร์ของคุณนะครับ!
// เช่น $post_url = "http://110.78.30.118/lafs/lost-and-found/src/view_post.php?id=" . $item['id'];
$post_url = "http://localhost:8088/view_post.php?id=" . $item['id'];
$qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($post_url);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ป้ายประกาศ - <?= htmlspecialchars($item['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;700;900&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Noto Sans Thai', sans-serif; background-color: #f1f5f9; }
        @media print {
            body { background-color: white; }
            .no-print { display: none !important; }
            .print-container { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="flex justify-center py-10">

    <div class="fixed top-5 right-5 flex gap-4 no-print z-50">
        <button onclick="window.location.href='view_post.php?id=<?= $item['id'] ?>'" class="bg-slate-800 text-white px-6 py-3 rounded-2xl font-bold shadow-lg">ย้อนกลับ</button>
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg hover:bg-blue-500">🖨️ พิมพ์ป้ายประกาศ (A4)</button>
    </div>

    <div class="print-container bg-white w-[794px] min-h-[1123px] shadow-2xl p-12 relative overflow-hidden border border-slate-200">
        
        <div class="absolute top-0 left-0 w-full h-8 <?= $item['post_type'] === 'LOST' ? 'bg-rose-600' : 'bg-emerald-600' ?>"></div>

        <div class="text-center mt-6 mb-10">
            <h1 class="text-7xl font-black <?= $item['post_type'] === 'LOST' ? 'text-rose-600' : 'text-emerald-600' ?> uppercase tracking-widest mb-4">
                <?= $item['post_type'] === 'LOST' ? 'ตามหาของหาย!' : 'ประกาศเก็บของได้!' ?>
            </h1>
            <p class="text-2xl font-bold text-slate-500">ระบบบริหารจัดการ CampusFinds (BNCC)</p>
        </div>

        <div class="text-center mb-10">
            <h2 class="text-5xl font-black text-slate-900 leading-tight border-y-4 border-slate-100 py-6 mb-8">
                <?= htmlspecialchars($item['title']) ?>
            </h2>
        </div>

        <div class="flex gap-10">
            <div class="w-1/2 flex flex-col items-center">
                <?php if ($item['image_path']): ?>
                    <div class="w-full aspect-square rounded-3xl overflow-hidden border-4 border-slate-100 shadow-md mb-6">
                        <img src="uploads/<?= htmlspecialchars($item['image_path']) ?>" class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="w-full aspect-square rounded-3xl bg-slate-100 border-4 border-slate-200 flex items-center justify-center mb-6">
                        <span class="text-slate-400 font-bold text-xl">ไม่มีรูปภาพประกอบ</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="w-1/2 flex flex-col">
                <div class="bg-slate-50 rounded-3xl p-8 mb-8 flex-grow border border-slate-200">
                    <div class="mb-6">
                        <p class="text-slate-500 font-bold mb-1">📍 สถานที่:</p>
                        <p class="text-2xl font-black text-slate-800"><?= htmlspecialchars($item['location']) ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500 font-bold mb-1">📝 รายละเอียดเพิ่มเติม:</p>
                        <p class="text-xl font-bold text-slate-700 whitespace-pre-line"><?= htmlspecialchars($item['description'] ?: '-') ?></p>
                    </div>
                </div>

                <div class="flex items-center bg-blue-50 rounded-3xl p-6 border-2 border-blue-200">
                    <img src="<?= $qr_api ?>" alt="QR Code" class="w-32 h-32 rounded-xl shadow-sm mr-6">
                    <div>
                        <p class="text-blue-800 font-black text-2xl mb-2">สแกนเพื่อแจ้งเบาะแส!</p>
                        <p class="text-blue-600 font-bold text-sm leading-relaxed">ใช้กล้องมือถือสแกน QR Code นี้เพื่อดูรายละเอียดเพิ่มเติม และพิมพ์พูดคุยกับผู้ประกาศผ่านเว็บไซต์</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="absolute bottom-10 left-0 w-full text-center">
            <p class="text-slate-400 font-bold text-lg">ประกาศเมื่อ: <?= date('d M Y', strtotime($item['created_at'])) ?> | แจ้งโดย: <?= htmlspecialchars($item['display_name']) ?></p>
        </div>
    </div>

</body>
</html>