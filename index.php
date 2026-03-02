<?php require_once __DIR__ . '/header.php';

/**
 * index.php — หน้าแรก/ค้นหา/กรองรายการ
 * - รองรับ q, type, status, category
 * - ถ้าโพสต์ไม่มีรูป: แสดงข้อความ "ไม่มีรูปภาพ" แทน (thumb-empty)
 */

// รับค่าค้นหา/กรอง
$q        = trim($_GET['q'] ?? '');
$type     = $_GET['type']   ?? '';   // lost | found | (ว่าง = ทั้งหมด)
$status   = $_GET['status'] ?? '';   // open | closed | (ว่าง = ทั้งหมด)
$category = $_GET['category'] ?? ''; // หมวดหมู่

$params = [];
$sql = "SELECT i.*, DATE_FORMAT(i.date_event, '%d/%m/%Y') as date_th
        FROM items i WHERE 1=1";

if ($q !== '') {
    $sql .= " AND (i.title LIKE :q OR i.description LIKE :q OR i.location LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($type === 'lost' || $type === 'found') {
    $sql .= " AND i.type = :type";
    $params[':type'] = $type;
}
if ($status === 'open' || $status === 'closed') {
    $sql .= " AND i.status = :status";
    $params[':status'] = $status;
}
if ($category !== '') {
    $sql .= " AND i.category = :category";
    $params[':category'] = $category;
}

$sql .= " ORDER BY i.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// ดึงรายการหมวดหมู่
$cats = $pdo->query("SELECT DISTINCT category FROM items ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

?>
<section class="hero">
  <div class="hero-text">
    <h2>ทำหาย? หรือเจอของใครสักคน?</h2>
    <p>ค้นหา/ประกาศได้ที่นี่ — ระบบใช้งานง่าย, UI สะอาดตา, รองรับมือถือ</p>

    <form method="get" class="search-form">
      <input type="text" name="q" placeholder="ค้นหา: ชื่อของ, ลักษณะ, สถานที่..." value="<?= e($q); ?>">

      <select name="type">
        <option value="">ทุกประเภท</option>
        <option value="lost"  <?= $type==='lost'  ? 'selected' : ''; ?>>ตามหาของหาย</option>
        <option value="found" <?= $type==='found' ? 'selected' : ''; ?>>เจอของ</option>
      </select>

      <select name="status">
        <option value="">ทุกสถานะ</option>
        <option value="open"   <?= $status==='open'   ? 'selected' : ''; ?>>ยังไม่ส่งคืน</option>
        <option value="closed" <?= $status==='closed' ? 'selected' : ''; ?>>ส่งคืนแล้ว</option>
      </select>

      <select name="category">
        <option value="">ทุกหมวดหมู่</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= e($c); ?>" <?= $category===$c ? 'selected' : ''; ?>><?= e(ucfirst($c)); ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="btn">ค้นหา</button>
    </form>
  </div>
</section>

<section>
  <div class="grid">
    <?php if (!$items): ?>
      <div class="empty" style="grid-column:1/-1;">ไม่พบรายการที่ค้นหา</div>
    <?php endif; ?>

    <?php foreach ($items as $it): ?>
      <?php
        // รองรับทั้งสคีมาเก่า (image_path) และใหม่ (image_url)
        $img = $it['image_path'] ?? null;
        if (empty($img) && !empty($it['image_url'])) {
          $img = $it['image_url'];
        }
        $hasImg = !empty($img);
      ?>
      <article class="card">
        <a href="view.php?id=<?= (int)$it['id']; ?>">
          <div class="thumb">
            <?php if ($hasImg): ?>
              <img src="<?= e($img); ?>" alt="ภาพประกอบ">
            <?php else: ?>
              <div class="thumb-empty" style="aspect-ratio:16/9;">ไม่มีรูปภาพ</div>
            <?php endif; ?>

            <?php if ($it['status'] === 'closed'): ?>
              <span class="badge badge-closed">ส่งคืนแล้ว</span>
            <?php else: ?>
              <span class="badge badge-open"><?= $it['type']==='lost' ? 'ตามหาของหาย' : 'เจอของ'; ?></span>
            <?php endif; ?>
          </div>

          <div class="body">
            <h3><?= e($it['title']); ?></h3>
            <p class="desc"><?= e(mb_strimwidth($it['description'], 0, 120, '...', 'UTF-8')); ?></p>
            <div class="meta">
              <span>หมวด: <?= e(ucfirst($it['category'])); ?></span>
              <span>สถานที่: <?= e($it['location']); ?></span>
              <span>วันที่: <?= e($it['date_th']); ?></span>
            </div>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
