<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

require_once 'config.php';

try {
    // 1. ดึงข้อมูลผู้ใช้และแต้มฮีโร่ (ดึง profile_image มาด้วย)
    $stmt_user = $pdo->prepare("SELECT display_name, email, points, profile_image FROM users WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // 2. ดึงประวัติการโพสต์ของตัวเองทั้งหมด
    $stmt_items = $pdo->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt_items->execute([$_SESSION['user_id']]);
    $my_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 3. ระบบคำนวณยศ (Rank) จากแต้ม
    $points = $user['points'];
    if ($points >= 50) { $rank = '💎 เทพเจ้าแห่งการค้นหา (Legendary)'; $rank_color = 'from-cyan-400 to-blue-600'; }
    elseif ($points >= 20) { $rank = '🥇 ตำนานแคมปัส (Gold)'; $rank_color = 'from-amber-300 to-orange-500'; }
    elseif ($points >= 10) { $rank = '🥈 ฮีโร่ประจำตึก (Silver)'; $rank_color = 'from-slate-300 to-slate-500'; }
    elseif ($points >= 1) { $rank = '🥉 พลเมืองดี (Bronze)'; $rank_color = 'from-orange-400 to-rose-600'; }
    else { $rank = '🌱 ผู้เริ่มต้น (Beginner)'; $rank_color = 'from-emerald-400 to-teal-600'; }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

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
    <title>โปรไฟล์ของฉัน - CampusFinds</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+Thai:wght@400;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', 'Noto Sans Thai', sans-serif; } </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen pb-20">

    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center text-slate-400 hover:text-white transition font-bold text-sm uppercase tracking-widest group">
                <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back Home
            </a>
            <div class="text-xl font-black text-white tracking-tight">My<span class="text-blue-500">Profile</span></div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 mt-10">
        
        <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-800 overflow-hidden mb-12 relative">
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r <?= $rank_color ?> opacity-20"></div>
            <div class="p-8 sm:p-12 relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-8">
                
                <div class="w-32 h-32 rounded-full bg-gradient-to-br <?= $rank_color ?> p-1 shadow-2xl shrink-0">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($user['profile_image']) ?>" class="w-full h-full rounded-full object-cover border-4 border-slate-900 shadow-inner" alt="Profile" referrerpolicy="no-referrer">
                    <?php else: ?>
                        <div class="w-full h-full bg-slate-900 rounded-full flex items-center justify-center text-5xl font-black text-white uppercase border-4 border-slate-900 shadow-inner">
                            <?= mb_substr($user['display_name'], 0, 1, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex-grow text-center sm:text-left">
                    <h1 class="text-3xl sm:text-4xl font-black text-white mb-2"><?= htmlspecialchars($user['display_name']) ?></h1>
                    <p class="text-slate-400 font-medium mb-6"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <div class="inline-block bg-slate-950 border border-slate-800 rounded-2xl p-4 shadow-inner">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Current Rank</div>
                        <div class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r <?= $rank_color ?>">
                            <?= $rank ?>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 text-center min-w-[150px] shadow-xl shrink-0">
                    <div class="text-5xl font-black text-white mb-2"><?= $user['points'] ?></div>
                    <div class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em]">Hero Points</div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-black text-white flex items-center">
                <span class="w-2 h-8 bg-blue-600 rounded-full mr-3"></span> โพสต์ของฉัน (<?= count($my_items) ?>)
            </h2>
        </div>

        <?php if (empty($my_items)): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-[2rem] p-12 text-center">
                <div class="text-6xl mb-4 opacity-50">👻</div>
                <h3 class="text-xl font-bold text-slate-300 mb-2">คุณยังไม่เคยโพสต์อะไรเลย</h3>
                <p class="text-slate-500 text-sm mb-6">มาเริ่มต้นสร้างสังคมแห่งการแบ่งปันด้วยการโพสต์กันเถอะ!</p>
                <a href="create_post.php" class="inline-block bg-blue-600 hover:bg-blue-500 text-white font-bold px-6 py-3 rounded-xl transition-all shadow-lg shadow-blue-900/20 text-sm">
                    + สร้างโพสต์แรก
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($my_items as $item): ?>
                    <div class="bg-slate-900 rounded-3xl shadow-xl border border-slate-800/60 overflow-hidden flex flex-col group relative">
                        <div class="h-1.5 w-full <?= $item['status'] === 'RESOLVED' ? 'bg-slate-700' : ($item['post_type'] === 'LOST' ? 'bg-rose-500' : 'bg-emerald-500') ?>"></div>
                        
                        <div class="p-6 flex-grow <?= $item['status'] === 'RESOLVED' ? 'opacity-60' : '' ?>">
                            <div class="flex justify-between items-start mb-4">
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black <?= $item['post_type'] === 'LOST' ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' ?> border border-current uppercase">
                                    <?= $item['post_type'] ?>
                                </span>
                                <?php if ($item['status'] === 'RESOLVED'): ?>
                                    <span class="text-[9px] font-black text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-lg border border-emerald-500/30 uppercase tracking-tighter">✔ ปิดเคสแล้ว</span>
                                <?php else: ?>
                                    <span class="text-[9px] font-black text-amber-400 bg-amber-500/10 px-2 py-1 rounded-lg border border-amber-500/30 uppercase tracking-tighter">กำลังดำเนินการ</span>
                                <?php endif; ?>
                            </div>
                            
                            <a href="view_post.php?id=<?= $item['id'] ?>">
                                <h3 class="text-lg font-bold text-white mb-2 leading-tight group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($item['title']) ?></h3>
                            </a>
                            <p class="text-[10px] font-bold text-slate-500 uppercase mb-4"><?= getCatName($item['category']) ?> • <?= date('d M Y', strtotime($item['created_at'])) ?></p>
                        </div>

                        <div class="bg-slate-950 px-6 py-4 border-t border-slate-800 flex gap-2">
                            <a href="view_post.php?id=<?= $item['id'] ?>" class="flex-1 bg-slate-800 text-blue-400 py-2.5 rounded-xl text-[10px] font-black text-center hover:bg-blue-500/10 transition-all uppercase tracking-widest">ดูโพสต์</a>
                            
                            <?php if ($item['status'] !== 'RESOLVED'): ?>
                                <a href="resolve_post.php?id=<?= $item['id'] ?>" class="flex-1 bg-emerald-500/10 text-emerald-400 py-2.5 rounded-xl text-[10px] font-black text-center border border-emerald-500/30 hover:bg-emerald-500/20 transition-all uppercase tracking-widest" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปิดเคสนี้ (หาของเจอแล้ว / ส่งคืนแล้ว)?')">ปิดเคส</a>
                            <?php endif; ?>
                            
                            <a href="delete_post.php?id=<?= $item['id'] ?>" class="w-10 flex items-center justify-center bg-slate-800 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-colors" onclick="return confirm('ลบโพสต์นี้ถาวร ใช่หรือไม่?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>