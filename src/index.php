<?php
session_start();

$host = 'db';
$dbname = 'lost_and_found_db';
$username = 'admin';
$password = 'password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage());
}

$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $stmt_admin = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_admin->execute([$_SESSION['user_id']]);
    if ($stmt_admin->fetchColumn() === '66209010013@bncc.ac.th') {
        $is_admin = true;
    }
}

// ดึงข้อมูล Leaderboard (ฮีโร่ของวิทยาลัย) ดัก Try-Catch ไว้เผื่อยังไม่ได้รัน SQL
$top_users = [];
try {
    $stmt_top = $pdo->query("SELECT display_name, points FROM users WHERE points > 0 ORDER BY points DESC LIMIT 5");
    $top_users = $stmt_top->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$cat_filter = $_GET['category'] ?? ''; 

$query = "SELECT items.*, users.display_name FROM items JOIN users ON items.user_id = users.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (items.title LIKE ? OR items.description LIKE ? OR items.location LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($type_filter) { $query .= " AND items.post_type = ?"; $params[] = $type_filter; }
if ($cat_filter) { $query .= " AND items.category = ?"; $params[] = $cat_filter; }

$query .= " ORDER BY items.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getCatName($cat) {
    $cats = ['ELECTRONICS' => '📱 ไอที', 'STATIONERY' => '✏️ เครื่องเขียน', 'WALLET' => '👛 กระเป๋า', 'DOCUMENTS' => '📄 บัตร/เอกสาร', 'ACCESSORIES' => '💍 เครื่องประดับ', 'OTHER' => '📦 อื่นๆ'];
    return $cats[$cat] ?? '📦 อื่นๆ';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Lost & Found (Dark)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', 'Noto Sans Thai', sans-serif; } </style>
</head>
<body class="bg-slate-950 text-slate-100"> 
    
    <nav class="bg-slate-900/80 backdrop-blur-md shadow-lg border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="bg-blue-600/20 p-2 rounded-lg mr-3">
                        <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <span class="text-xl font-extrabold text-white tracking-tight">Campus<span class="text-blue-500">Finds</span></span>
                </div>
                <div class="flex items-center">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        
                        <?php 
                            $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                            $stmt_unread->execute([$_SESSION['user_id']]);
                            $unread_count = $stmt_unread->fetchColumn();
                        ?>
                        <div class="relative group cursor-pointer mr-5">
                            <div class="bg-slate-800 w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-700 transition-colors border border-slate-700 relative">
                                <span class="text-sm">🔔</span>
                                <?php if($unread_count > 0): ?>
                                    <span class="absolute -top-1 -right-1 bg-rose-500 text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full animate-pulse border border-slate-900">
                                        <?= $unread_count ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="absolute right-0 mt-2 w-80 bg-slate-900 border border-slate-800 shadow-2xl rounded-2xl overflow-hidden hidden group-hover:block z-50">
                                <div class="p-3 border-b border-slate-800 bg-slate-950/50 flex justify-between items-center">
                                    <span class="text-xs font-black text-white uppercase tracking-widest">การแจ้งเตือน</span>
                                    <?php if($unread_count > 0): ?>
                                        <span class="text-[9px] bg-rose-500/20 text-rose-400 px-2 py-1 rounded-lg font-bold">ใหม่ <?= $unread_count ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <?php 
                                        $stmt_noti = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                                        $stmt_noti->execute([$_SESSION['user_id']]);
                                        $notifications = $stmt_noti->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <?php if(empty($notifications)): ?>
                                        <div class="p-6 text-center text-xs text-slate-500 font-bold bg-slate-900">ไม่มีการแจ้งเตือนใหม่</div>
                                    <?php else: ?>
                                        <?php foreach($notifications as $noti): ?>
                                            <a href="<?= $noti['link'] ?>" class="block p-4 border-b border-slate-800/50 hover:bg-slate-800 transition-colors <?= $noti['is_read'] ? 'opacity-50' : 'bg-blue-900/10' ?>">
                                                <p class="text-xs font-medium text-slate-200 leading-relaxed"><?= htmlspecialchars($noti['message']) ?></p>
                                                <p class="text-[9px] font-bold text-slate-500 mt-2"><?= date('d M H:i', strtotime($noti['created_at'])) ?></p>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:flex flex-col text-right mr-4">
                            <span class="text-sm font-bold text-slate-100"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            <?php if($is_admin): ?><span class="text-[10px] text-blue-400 font-black uppercase tracking-widest">Admin</span><?php endif; ?>
                        </div>
                        
                        <?php if($is_admin): ?>
                            <a href="admin_report.php" class="bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition-all border border-indigo-500/30 mr-2 uppercase tracking-widest flex items-center">
                                <span class="mr-1">📄</span> Report
                            </a>
                        <?php endif; ?>
                        
                        <a href="logout.php" class="bg-slate-800 text-red-400 hover:bg-red-500/20 px-4 py-2 rounded-xl text-xs font-bold transition-all border border-slate-700">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-900/20 transition-all">Sign in</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="bg-gradient-to-br from-blue-900 via-blue-800 to-slate-950 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-black tracking-tight sm:text-5xl">Lost it? Found it?</h1>
            <p class="mt-4 text-lg text-slate-300 max-w-2xl mx-auto">แพลตฟอร์มศูนย์กลางสำหรับติดตามของหายและแจ้งเก็บของได้ภายในวิทยาลัย</p>
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="mt-8">
                <a href="create_post.php" class="inline-flex items-center bg-blue-600 text-white font-bold px-8 py-3.5 rounded-full shadow-xl hover:bg-blue-500 hover:scale-105 transition-all">
                    + แจ้งของหาย / เก็บของได้
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <form method="GET" action="index.php" class="mb-10 bg-slate-900 p-5 rounded-3xl shadow-2xl border border-slate-800 flex flex-col lg:flex-row gap-4 relative z-10 -mt-20">
            <div class="flex-grow">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Search Keywords</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ระบุชื่อสิ่งของ..." class="w-full px-4 py-3 bg-slate-800 border border-slate-700 text-white rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-medium">
            </div>
            <div class="grid grid-cols-2 gap-4 lg:w-[400px]">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Type</label>
                    <select name="type" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 text-white rounded-2xl outline-none font-medium">
                        <option value="">ทุกประเภท</option>
                        <option value="LOST" <?= $type_filter === 'LOST' ? 'selected' : '' ?>>🚨 ตามหาของ</option>
                        <option value="FOUND" <?= $type_filter === 'FOUND' ? 'selected' : '' ?>>✨ เก็บได้</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Category</label>
                    <select name="category" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 text-white rounded-2xl outline-none font-medium">
                        <option value="">ทุกหมวดหมู่</option>
                        <option value="ELECTRONICS" <?= $cat_filter == 'ELECTRONICS' ? 'selected' : '' ?>>📱 ไอที</option>
                        <option value="STATIONERY" <?= $cat_filter == 'STATIONERY' ? 'selected' : '' ?>>✏️ เครื่องเขียน</option>
                        <option value="WALLET" <?= $cat_filter == 'WALLET' ? 'selected' : '' ?>>👛 กระเป๋า</option>
                        <option value="DOCUMENTS" <?= $cat_filter == 'DOCUMENTS' ? 'selected' : '' ?>>📄 เอกสาร</option>
                        <option value="OTHER" <?= $cat_filter == 'OTHER' ? 'selected' : '' ?>>📦 อื่นๆ</option>
                    </select>
                </div>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-8 py-3 rounded-xl hover:bg-blue-500 transition-all font-bold shadow-lg shadow-blue-900/20">SEARCH</button>
            </div>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
            <div class="bg-slate-900 p-4 rounded-3xl border border-slate-800 shadow-lg">
                <iframe src="http://localhost:3000/d-solo/ad7pchd/db18a9b?orgId=1&from=now-24h&to=now&timezone=browser&panelId=panel-1&theme=dark" width="100%" height="200" frameborder="0"></iframe>
            </div>
            <div class="bg-slate-900 p-4 rounded-3xl border border-slate-800 shadow-lg">
                <iframe src="http://localhost:3000/d-solo/adnvb7d/d64164f?orgId=1&from=now-24h&to=now&timezone=browser&panelId=panel-1&theme=dark" width="100%" height="200" frameborder="0"></iframe>
            </div>
        </div>

        <?php if (count($top_users) > 0): ?>
        <h2 class="text-2xl font-black text-white mb-6 flex items-center">
            <span class="w-2 h-8 bg-amber-500 rounded-full mr-3"></span> ฮีโร่ของวิทยาลัย (Top 5)
        </h2>
        <div class="bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-lg mb-12">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <?php foreach($top_users as $index => $u): ?>
                    <div class="bg-slate-950/50 p-4 rounded-2xl border <?= $index === 0 ? 'border-amber-500/50 shadow-lg shadow-amber-500/10' : 'border-slate-800' ?> flex flex-col items-center justify-center text-center hover:border-blue-500/50 transition-all group">
                        <div class="w-12 h-12 rounded-full <?= $index === 0 ? 'bg-amber-500/20 text-amber-400' : 'bg-blue-500/20 text-blue-400' ?> flex items-center justify-center font-black text-xl mb-3 group-hover:scale-110 transition-transform">
                            <?= $index === 0 ? '👑' : $index + 1 ?>
                        </div>
                        <span class="text-xs font-bold text-slate-300 truncate w-full mb-1"><?= htmlspecialchars($u['display_name']) ?></span>
                        <span class="text-[10px] font-black <?= $index === 0 ? 'text-amber-500' : 'text-emerald-400' ?> uppercase tracking-widest"><?= $u['points'] ?> PTS</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <h2 class="text-2xl font-black text-white mb-6 flex items-center">
            <span class="w-2 h-8 bg-blue-600 rounded-full mr-3"></span> รายการล่าสุด
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-slate-100">
            <?php foreach ($items as $item): ?>
                <div class="bg-slate-900 rounded-3xl shadow-xl border border-slate-800/60 overflow-hidden transition-all duration-300 flex flex-col group hover:border-blue-500/50">
                    <div class="h-1.5 w-full <?= $item['post_type'] === 'LOST' ? 'bg-rose-500' : 'bg-emerald-500' ?>"></div>
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex flex-col gap-2">
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black <?= $item['post_type'] === 'LOST' ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' ?> border border-current uppercase">
                                    <?= $item['post_type'] ?>
                                </span>
                                <span class="text-[9px] font-bold text-slate-500 uppercase"><?= getCatName($item['category']) ?></span>
                            </div>
                            <?php if ($item['status'] === 'RESOLVED'): ?>
                                <span class="text-[9px] font-black text-slate-400 bg-slate-800 px-2 py-1 rounded-lg border border-slate-700 uppercase tracking-tighter">Resolved</span>
                            <?php endif; ?>
                        </div>
                        
                        <a href="view_post.php?id=<?= $item['id'] ?>">
                            <h3 class="text-lg font-bold text-white mb-2 leading-tight group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($item['title']) ?></h3>
                        </a>
                        
                        <p class="text-slate-400 text-xs mb-5 line-clamp-2 leading-relaxed"><?= htmlspecialchars($item['description']) ?></p>
                        <div class="flex items-center text-[10px] font-bold text-slate-500 bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z" /></svg>
                            <?= htmlspecialchars($item['location']) ?>
                        </div>
                    </div>
                    <div class="bg-slate-900 px-6 py-4 border-t border-slate-800 flex flex-col mt-auto">
                        <div class="flex justify-between items-center w-full">
                            <span class="text-[10px] font-bold text-slate-500 tracking-tight"><?= htmlspecialchars($item['display_name']) ?></span>
                            <span class="text-[10px] font-bold text-slate-600"><?= date('d M Y', strtotime($item['created_at'])) ?></span>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $item['user_id'] || $is_admin)): ?>
                            <div class="mt-4 pt-4 border-t border-slate-800 flex gap-2">
                                <?php if ($item['status'] !== 'RESOLVED'): ?>
                                    <a href="resolve_post.php?id=<?= $item['id'] ?>" class="flex-1 bg-slate-800 text-emerald-400 py-2 rounded-xl text-[9px] font-black text-center border border-emerald-500/30 hover:bg-emerald-500/10 transition-all uppercase">Check</a>
                                <?php endif; ?>
                                <a href="delete_post.php?id=<?= $item['id'] ?>" class="flex-1 bg-slate-800 text-rose-400 py-2 rounded-xl text-[9px] font-black text-center border border-rose-500/30 hover:bg-rose-500/10 transition-all uppercase">Delete</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>