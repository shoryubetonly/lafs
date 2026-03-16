<?php
session_start();
// 1. ตรวจสอบสิทธิ์ Admin (เฉพาะอีเมลของคุณเท่านั้น)
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$host = 'db';
$dbname = 'lost_and_found_db';
$username = 'admin';
$password = 'password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // เช็คว่าเป็น Admin จริงไหม
    $stmt_admin = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt_admin->execute([$_SESSION['user_id']]);
    if ($stmt_admin->fetchColumn() !== '66209010013@bncc.ac.th') {
        die("เฉพาะผู้ดูแลระบบเท่านั้นที่เข้าถึงหน้านี้ได้");
    }

    // 2. Query สรุปสถิติรายเดือน (ตามที่คุณเขียนมา เป๊ะมาก!)
    $stmt_report = $pdo->query("
        SELECT 
            category, 
            COUNT(*) as total, 
            SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as found_back
        FROM items 
        GROUP BY category
    ");
    $reports = $stmt_report->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

// ฟังก์ชันช่วยแสดงชื่อหมวดหมู่
function getCatName($cat) {
    $cats = ['ELECTRONICS' => '📱 ไอที', 'STATIONERY' => '✏️ เครื่องเขียน', 'WALLET' => '👛 กระเป๋า', 'DOCUMENTS' => '📄 เอกสาร', 'ACCESSORIES' => '💍 เครื่องประดับ', 'OTHER' => '📦 อื่นๆ'];
    return $cats[$cat] ?? '📦 อื่นๆ';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปผล - CampusFinds</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+Thai:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans Thai', sans-serif; }
        /* สไตล์สำหรับการปริ้นท์ (Print Style) */
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0; }
            .report-container { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; }
            table { border: 1px solid #ddd !important; }
            th { background-color: #f8fafc !important; color: black !important; }
            td { border-bottom: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-10">

    <div class="max-w-4xl mx-auto report-container bg-slate-900 border border-slate-800 rounded-[2.5rem] shadow-2xl overflow-hidden">
        
        <div class="p-8 border-b border-slate-800 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Monthly Summary Report</h1>
                <p class="text-slate-500 text-sm font-bold mt-1">รายงานสรุปผลการดำเนินงานระบบ CampusFinds</p>
            </div>
            <button onclick="window.print()" class="no-print bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-blue-900/20">
                Print / Save PDF
            </button>
        </div>

        <div class="p-8">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] border-b border-slate-800">
                        <th class="pb-4 pl-4">Category (หมวดหมู่)</th>
                        <th class="pb-4 text-center">Total (ทั้งหมด)</th>
                        <th class="pb-4 text-center">Resolved (ตามหาเจอแล้ว)</th>
                        <th class="pb-4 text-center">Success Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($reports as $row): 
                        $rate = ($row['total'] > 0) ? round(($row['found_back'] / $row['total']) * 100) : 0;
                    ?>
                    <tr class="group hover:bg-slate-800/30 transition-colors">
                        <td class="py-5 pl-4 font-bold text-slate-200"><?= getCatName($row['category']) ?></td>
                        <td class="py-5 text-center font-black text-lg"><?= $row['total'] ?></td>
                        <td class="py-5 text-center font-bold text-emerald-500"><?= $row['found_back'] ?></td>
                        <td class="py-5 text-center">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                <?= $rate ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="mt-10 p-6 bg-slate-950/50 rounded-3xl border border-slate-800">
                <p class="text-xs text-slate-500 leading-relaxed font-medium">
                    * ข้อมูลนี้แสดงผลจากการดำเนินงานจริงภายในวิทยาลัย สถิติการคืนของที่สำเร็จ (Success Rate) เป็นตัวบ่งชี้ถึงประสิทธิภาพของระบบและสังคมแห่งการแบ่งปันภายในแคมปัส
                </p>
            </div>
        </div>

        <div class="p-8 bg-slate-800/20 border-t border-slate-800 flex justify-between items-center text-[10px] font-black text-slate-600 uppercase">
            <span>Report Generated: <?= date('d M Y H:i') ?></span>
            <a href="index.php" class="no-print hover:text-blue-500 transition-colors">Back to Home</a>
        </div>
    </div>

</body>
</html>