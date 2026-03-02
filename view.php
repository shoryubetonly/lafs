<?php require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
  SELECT i.*,
         DATE_FORMAT(i.date_event, '%d/%m/%Y')  AS date_th,
         DATE_FORMAT(i.created_at, '%d/%m/%Y %H:%i') AS created_th
  FROM items i
  WHERE i.id = :id
  LIMIT 1
");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    echo '<div class="empty">ไม่พบรายการ</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

// หา cover (รองรับทั้ง image_url ใหม่ และ image_path เก่า)
$cover = null;
if (!empty($item['image_url']))  { $cover = $item['image_url']; }
elseif (!empty($item['image_path'])) { $cover = $item['image_path']; }
$hasCover = !empty($cover);
?>
<section class="section">
  <article class="detail">
    <div class="detail-left">
      <div class="thumb-lg">
        <?php if ($hasCover): ?>
          <img src="<?= e($cover); ?>" alt="ภาพประกอบ">
        <?php else: ?>
          <div class="thumb-lg-empty" style="min-height:260px;">ไม่มีรูปภาพในโพสต์นี้</div>
        <?php endif; ?>

        <?php if ($item['status'] === 'closed'): ?>
          <span class="badge badge-closed">ส่งคืนแล้ว</span>
        <?php else: ?>
          <span class="badge badge-open"><?= $item['type']==='lost' ? 'ตามหาของหาย' : 'เจอของ'; ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-right">
      <h2><?= e($item['title']); ?></h2>
      <p class="desc"><?= nl2br(e($item['description'])); ?></p>

      <div class="meta-list">
        <div><strong>หมวดหมู่:</strong> <?= e(ucfirst($item['category'])); ?></div>
        <div><strong>สถานที่:</strong> <?= e($item['location']); ?></div>
        <div><strong>วันที่เกิดเหตุ:</strong> <?= e($item['date_th']); ?></div>
        <div><strong>ผู้ติดต่อ:</strong> <?= e($item['contact_name']); ?></div>
        <div><strong>ช่องทางติดต่อ:</strong> <?= e($item['contact_phone']); ?></div>
        <div><strong>โพสต์เมื่อ:</strong> <?= e($item['created_th']); ?></div>
      </div>

      <?php if ($item['status'] === 'open'): ?>
        <form action="claim.php" method="post" class="inline-form"
              onsubmit="return confirm('ยืนยันเปลี่ยนสถานะเป็น ส่งคืนแล้ว/ปิดเคส ?');">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
          <input type="hidden" name="id" value="<?= (int)$item['id']; ?>">
          <button class="btn btn-success" type="submit">ปิดเคส / ส่งคืนแล้ว</button>
        </form>
      <?php else: ?>
        <div class="alert success">รายการนี้ถูกปิดเคสแล้ว</div>
      <?php endif; ?>

      <a class="btn btn-secondary" href="index.php">← กลับหน้าแรก</a>
    </div>
  </article>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
