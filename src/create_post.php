<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$host = 'localhost';
$dbname = 'lafs';
$username = 'lafs';
$password = 'bncclafsconfig';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $image_name = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = time() . '.' . $ext;
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
        }

// โค้ดส่วนบันทึกข้อมูล (อัปเดตใหม่)
        $stmt = $pdo->prepare("INSERT INTO items (user_id, title, description, post_type, location, category, image_path, secret_question, secret_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['post_type'],
            $_POST['location'],
            $_POST['category'],
            $image_name,
            $_POST['secret_question'] ?? null,  // รับค่าคำถามลับ
            $_POST['secret_answer'] ?? null     // รับค่าคำตอบ
        ]);
        // ==========================================
        // 🧠 ระบบ SMART MATCH & NOTIFICATION
        // ==========================================
        $new_item_id = $pdo->lastInsertId();
        $post_type = $_POST['post_type']; // LOST หรือ FOUND
        $category = $_POST['category'];   // หมวดหมู่
        $title = $_POST['title'];         // ชื่อสิ่งของ
        
        // หาว่าเราต้องไปจับคู่กับโพสต์ประเภทไหน (ถ้าเราโพสต์ LOST ต้องหา FOUND)
        $target_type = ($post_type === 'LOST') ? 'FOUND' : 'LOST';
        
        // ค้นหาโพสต์ที่ยังไม่ปิดเคส ในหมวดหมู่เดียวกัน
        $stmt_match = $pdo->prepare("SELECT id, user_id, title FROM items WHERE post_type = ? AND category = ? AND status != 'RESOLVED' AND user_id != ?");
        $stmt_match->execute([$target_type, $category, $_SESSION['user_id']]);
        $matches = $stmt_match->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matches as $match) {
            // 1. ส่งแจ้งเตือนไปหา "เจ้าของโพสต์เดิม" ว่ามีคนโพสต์ของที่ตรงกัน!
            $msg_to_old = "💡 Smart Match: พบเบาะแสใหม่สำหรับ '" . $match['title'] . "' อาจเป็นของที่คุณตามหา!";
            $link_to_new = "view_post.php?id=" . $new_item_id;
            $noti1 = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $noti1->execute([$match['user_id'], $msg_to_old, $link_to_new]);

            // 2. ส่งแจ้งเตือนหา "คนที่เพิ่งโพสต์ใหม่" ด้วยว่ามีคนเคยโพสต์ของคล้ายกันไว้
            $msg_to_new = "🎯 Smart Match: เราพบรายการ '" . $match['title'] . "' ในระบบ ลองเช็กดูสิ!";
            $link_to_old = "view_post.php?id=" . $match['id'];
            $noti2 = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $noti2->execute([$_SESSION['user_id'], $msg_to_new, $link_to_old]);
        }
        // ==========================================

        header("Location: index.php");
        exit;
    } catch (PDOException $e) { 
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage(); 
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างประกาศใหม่ - CampusFinds Dark</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', 'Noto Sans Thai', sans-serif; } </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen"> <div class="max-w-5xl mx-auto px-4 py-12">
        <div class="flex items-center justify-between mb-10">
            <div>
                <a href="index.php" class="flex items-center text-slate-500 hover:text-blue-400 transition font-bold group text-sm uppercase tracking-widest">
                    <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Home
                </a>
                <h1 class="text-4xl font-black text-white mt-3">สร้างประกาศใหม่</h1>
            </div>
            <div class="hidden sm:block text-right">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Logged in as</span>
                <p class="text-sm font-bold text-blue-500"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <form action="create_post.php" method="POST" enctype="multipart/form-data" class="bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-800 overflow-hidden">
                    <div class="p-8 lg:p-10 space-y-8">
                        
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-4">
                                คุณต้องการทำอะไร?
                            </label>
                            <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-950 rounded-2xl border border-slate-800">
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="LOST" class="peer sr-only" checked>
                                    <div class="text-center py-3.5 rounded-xl font-black text-xs transition-all peer-checked:bg-slate-800 peer-checked:text-rose-400 text-slate-600 uppercase tracking-widest">
                                        🚨 ตามหาของหาย
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="FOUND" class="peer sr-only">
                                    <div class="text-center py-3.5 rounded-xl font-black text-xs transition-all peer-checked:bg-slate-800 peer-checked:text-emerald-400 text-slate-600 uppercase tracking-widest">
                                        ✨ เก็บของได้
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">หัวข้อรายการ / ชื่อสิ่งของ</label>
                            <input type="text" name="title" required placeholder="เช่น กุญแจรถ, กระเป๋าเป้..." class="w-full px-5 py-4 bg-slate-800 border border-slate-700 text-white rounded-2xl focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-all font-bold placeholder:text-slate-600">
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">หมวดหมู่สิ่งของ</label>
                            <select name="category" required class="w-full px-5 py-4 bg-slate-800 border border-slate-700 text-white rounded-2xl focus:ring-2 focus:ring-blue-600 outline-none font-bold cursor-pointer appearance-none">
                                <option value="ELECTRONICS">📱 เครื่องใช้ไฟฟ้า / ไอที</option>
                                <option value="STATIONERY">✏️ เครื่องเขียน / อุปกรณ์การเรียน</option>
                                <option value="WALLET">👛 กระเป๋า / กระเป๋าสตางค์</option>
                                <option value="DOCUMENTS">📄 บัตร / เอกสารสำคัญ</option>
                                <option value="ACCESSORIES">💍 เครื่องประดับ / แว่นตา</option>
                                <option value="OTHER" selected>📦 อื่นๆ</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">รูปภาพประกอบ</label>
                            <div class="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-slate-800 border-dashed rounded-[2rem] hover:border-blue-500/50 transition-colors bg-slate-950/50 group">
                                <div class="space-y-2 text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-700 group-hover:text-blue-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-slate-400 justify-center">
                                        <label for="image" class="relative cursor-pointer bg-slate-800 px-3 py-1 rounded-md font-black text-blue-400 hover:text-blue-300">
                                            <span>UPLOAD IMAGE</span>
                                            <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                                        </label>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-600 uppercase tracking-tighter">PNG, JPG up to 5MB</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">สถานที่พบเจอ/ทำหาย</label>
                                <input type="text" name="location" required placeholder="เช่น หน้าตึก 9" class="w-full px-5 py-4 bg-slate-800 border border-slate-700 text-white rounded-2xl focus:ring-2 focus:ring-blue-600 outline-none font-bold placeholder:text-slate-600">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">รายละเอียดเพิ่มเติม</label>
                                <textarea name="description" rows="1" placeholder="ระบุลักษณะ..." class="w-full px-5 py-4 bg-slate-800 border border-slate-700 text-white rounded-2xl focus:ring-2 focus:ring-blue-600 outline-none font-bold placeholder:text-slate-600"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-950/50 px-8 py-8 border-t border-slate-800">
                        <button type="submit" class="w-full bg-blue-600 text-white py-5 rounded-[1.5rem] font-black text-sm uppercase tracking-[0.2em] hover:bg-blue-500 transition-all shadow-xl shadow-blue-900/20 active:scale-[0.98]">
                            CONFIRM POST
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-xl border border-slate-800">
                    <h3 class="text-xs font-black text-white mb-6 uppercase tracking-[0.2em] flex items-center">
                        <span class="w-1.5 h-4 bg-amber-500 rounded-full mr-3"></span>
                        POSTING TIPS
                    </h3>
                    <ul class="space-y-6 text-xs text-slate-400 font-bold leading-relaxed">
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-3">01</span>
                            รูปถ่ายที่ชัดเจนช่วยให้คนค้นหาของเจอได้รวดเร็วขึ้นถึง 80%
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-3">02</span>
                            เลือกหมวดหมู่ให้ตรงกับสิ่งของเพื่อความสะดวกในการกรองข้อมูล
                        </li>
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-3">03</span>
                            ระบุสถานที่พบเจอให้ละเอียดที่สุดเท่าที่จะเป็นไปได้
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-blue-700 to-indigo-900 p-8 rounded-[2.5rem] shadow-2xl text-white relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                    <h3 class="font-black text-sm mb-3 uppercase tracking-widest">CampusFinds Security</h3>
                    <p class="text-blue-100 text-xs leading-relaxed font-bold">ข้อมูลของคุณจะถูกเก็บเป็นความลับ และอนุญาตให้เข้าถึงได้เฉพาะบุคคลภายในวิทยาลัยเท่านั้น</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>