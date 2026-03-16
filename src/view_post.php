<?php
session_start();
if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

$host = 'db';
$dbname = 'lost_and_found_db';
$username = 'admin';
$password = 'password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $item_id = $_GET['id'];

    // 1. รับค่าคอมเมนต์ใหม่และบันทึกลงฐานข้อมูล
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO comments (item_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$item_id, $_SESSION['user_id'], trim($_POST['comment_text'])]);
        // บันทึกเสร็จให้รีเฟรชหน้าเดิม
        header("Location: view_post.php?id=" . $item_id);
        exit;
    }

    // 2. ดึงข้อมูลโพสต์ และชื่อเจ้าของโพสต์
    $stmt_item = $pdo->prepare("SELECT items.*, users.display_name FROM items JOIN users ON items.user_id = users.id WHERE items.id = ?");
    $stmt_item->execute([$item_id]);
    $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

    if (!$item) { header("Location: index.php"); exit; }

    // 3. ดึงข้อมูลคอมเมนต์ทั้งหมดของโพสต์นี้
    $stmt_comments = $pdo->prepare("SELECT comments.*, users.display_name FROM comments JOIN users ON comments.user_id = users.id WHERE comments.item_id = ? ORDER BY comments.created_at ASC");
    $stmt_comments->execute([$item_id]);
    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

function getCatName($cat) {
    $cats = ['ELECTRONICS' => '📱 ไอที', 'STATIONERY' => '✏️ เครื่องเขียน', 'WALLET' => '👛 กระเป๋า', 'DOCUMENTS' => '📄 เอกสาร', 'ACCESSORIES' => '💍 เครื่องประดับ', 'OTHER' => '📦 อื่นๆ'];
    return $cats[$cat] ?? '📦 อื่นๆ';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['title']) ?> - CampusFinds</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', 'Noto Sans Thai', sans-serif; } </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen pb-12">

    <nav class="bg-slate-900/80 backdrop-blur-md shadow-lg border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <a href="index.php" class="flex items-center text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="font-bold text-sm tracking-widest uppercase">Back to Home</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-800 overflow-hidden">
                    <div class="h-2 w-full <?= $item['post_type'] === 'LOST' ? 'bg-rose-500' : 'bg-emerald-500' ?>"></div>
                    
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div class="flex flex-wrap gap-2">
                                <span class="px-4 py-1.5 rounded-xl text-xs font-black <?= $item['post_type'] === 'LOST' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' ?> uppercase tracking-widest">
                                    <?= $item['post_type'] ?>
                                </span>
                                <?php if ($item['status'] === 'RESOLVED'): ?>
                                    <span class="px-4 py-1.5 rounded-xl text-xs font-black bg-slate-800 text-slate-400 border border-slate-700 uppercase tracking-widest">RESOLVED</span>
                                <?php endif; ?>
                                
                                <a href="print_poster.php?id=<?= $item['id'] ?>" target="_blank" class="px-4 py-1.5 rounded-xl text-xs font-black bg-blue-600/20 text-blue-400 border border-blue-500/20 hover:bg-blue-600 hover:text-white transition-colors flex items-center uppercase tracking-widest">
                                    🖨️ สร้างป้าย (PDF)
                                </a>
                            </div>
                            <span class="text-xs font-bold text-slate-500"><?= date('d M Y H:i', strtotime($item['created_at'])) ?></span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl font-black text-white mb-4"><?= htmlspecialchars($item['title']) ?></h1>
                        
                        <div class="flex flex-wrap gap-4 mb-8">
                            <div class="flex items-center text-sm font-bold text-slate-400 bg-slate-950 px-4 py-2 rounded-xl border border-slate-800">
                                <span class="mr-2">📁</span> <?= getCatName($item['category']) ?>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-400 bg-slate-950 px-4 py-2 rounded-xl border border-slate-800">
                                <span class="mr-2">📍</span> <?= htmlspecialchars($item['location']) ?>
                            </div>
                            <div class="flex items-center text-sm font-bold text-blue-400 bg-blue-500/10 px-4 py-2 rounded-xl border border-blue-500/20">
                                <span class="mr-2">👤</span> <?= htmlspecialchars($item['display_name']) ?>
                            </div>
                        </div>

                        <?php if ($item['image_path']): ?>
                            <div class="rounded-3xl overflow-hidden mb-8 border border-slate-800 flex justify-center bg-slate-950/50">
                                <img src="uploads/<?= htmlspecialchars($item['image_path']) ?>" class="max-w-full object-contain max-h-[500px]" alt="รูปภาพสิ่งของ">
                            </div>
                        <?php endif; ?>

                        <div class="bg-slate-950/50 p-6 rounded-3xl border border-slate-800">
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-3">รายละเอียดเพิ่มเติม</h3>
                            <p class="text-slate-300 leading-relaxed font-medium text-lg whitespace-pre-line"><?= htmlspecialchars($item['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 flex flex-col h-[800px]">
                <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-800 flex flex-col h-full overflow-hidden">
                    
                    <div class="p-6 border-b border-slate-800 bg-slate-900 z-10">
                        <h2 class="text-lg font-black text-white flex items-center">
                            <span class="mr-2">💬</span> แจ้งเบาะแส / พูดคุย
                            <span class="ml-2 bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded-full"><?= count($comments) ?></span>
                        </h2>
                    </div>

                    <div class="flex-grow overflow-y-auto p-6 space-y-4 bg-slate-950/30">
                        <?php if (empty($comments)): ?>
                            <div class="text-center text-slate-500 font-bold text-sm py-10">
                                ยังไม่มีข้อมูลเบาะแส<br>มาเป็นคนแรกที่ช่วยหาของสิ!
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="bg-slate-800/50 p-4 rounded-2xl border border-slate-700/50">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-black text-blue-400"><?= htmlspecialchars($comment['display_name']) ?></span>
                                        <span class="text-[9px] font-bold text-slate-500"><?= date('d M H:i', strtotime($comment['created_at'])) ?></span>
                                    </div>
                                    <p class="text-sm text-slate-300 font-medium whitespace-pre-line"><?= htmlspecialchars($comment['comment_text']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 border-t border-slate-800 bg-slate-900">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if ($item['status'] !== 'RESOLVED'): ?>
                                <form action="view_post.php?id=<?= $item_id ?>" method="POST" class="flex flex-col gap-3">
                                    <textarea name="comment_text" required rows="2" placeholder="พิมพ์เบาะแสหรือข้อความที่นี่..." class="w-full px-4 py-3 bg-slate-950 border border-slate-800 text-white rounded-2xl focus:ring-2 focus:ring-blue-600 outline-none font-medium text-sm resize-none"></textarea>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all shadow-lg shadow-blue-900/20">
                                        ส่งข้อความ
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="text-center p-4 bg-slate-950 rounded-2xl border border-slate-800 text-slate-500 text-xs font-bold uppercase tracking-widest">
                                    🔒 ปิดเคสแล้ว ไม่สามารถคอมเมนต์ได้
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <a href="login.php" class="text-blue-500 font-bold text-sm hover:underline">เข้าสู่ระบบเพื่อคอมเมนต์</a>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            
        </div>
    </div>
</body>
</html>