<?php
// admin_posts.php — แสดง/จัดการ "ทุกโพสต์" สำหรับผู้ดูแล (admin หรือ root)
require_once __DIR__ . '/init.php';

// ต้องเป็นผู้ดูแล (admin หรือ root)
if (empty($_SESSION['admin']) || !in_array(($_SESSION['admin']['role'] ?? ''), ['admin','root'], true)) {
  flash_set('error', 'กรุณาเข้าสู่ระบบผู้ดูแล');
  redirect('/lafs/admin_login.php');
}

/* =========================
   จัดการคำสั่งแบบ POST
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $action = $_POST['action'] ?? '';
  $id     = (int)($_POST['id'] ?? 0);

  if ($id <= 0) {
    flash_set('error', 'รหัสรายการไม่ถูกต้อง');
    redirect('/lafs/admin_posts.php');
  }

  if ($action === 'delete') {
    // ลบ item (item_images ถูกลบตามด้วย FK ON DELETE CASCADE)
    $del = $pdo->prepare("DELETE FROM items WHERE id = :id");
    $del->execute([':id' => $id]);
    flash_set('success', "ลบโพสต์ #{$id} เรียบร้อย");
    redirect('/lafs/admin_posts.php');
  }

  if ($action === 'close') {
    $u = $pdo->prepare("UPDATE items SET status='closed' WHERE id=:id");
    $u->execute([':id'=>$id]);
    flash_set('success', "ปิดเคสโพสต์ #{$id} แล้ว");
    redirect('/lafs/admin_posts.php');
  }

  if ($action === 'reopen') {
    $u = $pdo->prepare("UPDATE items SET status='open' WHERE id=:id");
    $u->execute([':id'=>$id]);
    flash_set('success', "เปิดเคสโพสต์ #{$id} แล้ว");
    redirect('/lafs/admin_posts.php');
  }

  flash_set('error', 'คำสั่งไม่ถูกต้อง');
  redirect('/lafs/admin_posts.php');
}

/* =========================
   ดึง "ทุกโพสต์" จากฐานข้อมูล
   ========================= */
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

// นับทั้งหมด
$total = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// ดึงรายการทั้งหมด (ไม่มีตัวกรอง) — เรียงใหม่ไปเก่า
$listStmt = $pdo->prepare("
  SELECT i.*,
         DATE_FORMAT(i.created_at, '%Y-%m-%d %H:%i') AS created_th
  FROM items i
  ORDER BY i.created_at DESC
  LIMIT :lim OFFSET :off
");
$listStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':off', $offset, PDO::PARAM_INT);
$listStmt->execute();
$items = $listStmt->fetchAll();

/* helper: หา cover (รองรับทั้ง image_url ใหม่ และ image_path เก่า) */
function admin_cover(PDO $pdo, array $row): ?string {
  if (!empty($row['image_url']))  return $row['image_url'];
  if (!empty($row['image_path'])) return $row['image_path']; // เผื่อสคีมาเก่า
  $q = $pdo->prepare("SELECT image_url FROM item_images WHERE item_id = :id ORDER BY created_at ASC LIMIT 1");
  $q->execute([':id' => (int)$row['id']]);
  $u = $q->fetchColumn();
  return $u ?: null;
}

/* ตั้งชื่อหน้า */
$page_title = 'จัดการโพสต์ทั้งหมด | ผู้ดูแลระบบ';
require_once __DIR__ . '/header.php';
?>
<section class="section">
  <div class="form" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
    <div>
      <h2 style="margin:0 0 4px;">ผู้ดูแลระบบ — โพสต์ทั้งหมด (<?= (int)$total ?> รายการ)</h2>
      <p class="muted" style="margin:0;color:var(--muted);">
        แสดงทุกโพสต์จากฐานข้อมูล เรียงจากใหม่ไปเก่า · หน้าละ <?= (int)$perPage ?> รายการ
      </p>
    </div>
    <div class="actions">
      <a class="btn btn-secondary" href="/lafs/">↩ กลับหน้าเว็บ</a>
      <?php if (($_SESSION['admin']['role'] ?? '') === 'root'): ?>
        <a class="btn" href="/lafs/admin_users.php">ผู้ดูแลระบบ</a>
        <a class="btn" href="/lafs/admin_user_new.php">+ เพิ่มแอดมิน</a>
      <?php else: ?>
        <a class="btn" href="/lafs/admin_user_edit.php?id=<?= (int)($_SESSION['admin']['id'] ?? 0) ?>">โปรไฟล์ของฉัน</a>
      <?php endif; ?>
      <!-- logout -->
      <form action="/lafs/admin_logout.php" method="post" style="display:inline; margin-left:6px;">
        <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
        <button class="btn" type="submit">ออกจากระบบ</button>
      </form>
    </div>
  </div>

  <?php if ($m = flash_get('success')): ?>
    <div class="alert success" style="margin-top:12px;"><?=esc($m)?></div>
  <?php endif; ?>
  <?php if ($m = flash_get('error')): ?>
    <div class="alert error" style="margin-top:12px;"><?=esc($m)?></div>
  <?php endif; ?>

  <div class="grid" style="margin-top:16px;">
    <?php if (!$items): ?>
      <div class="empty" style="grid-column:1/-1;">ยังไม่มีโพสต์ในฐานข้อมูล</div>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <?php
          $cover = admin_cover($pdo, $it);
          $hasCover = !empty($cover);
        ?>
        <article class="card">
          <div class="thumb">
            <?php if ($hasCover): ?>
              <img src="<?=esc($cover)?>" alt="thumb">
            <?php else: ?>
              <div class="thumb-empty" style="aspect-ratio:16/9;">ไม่มีรูปภาพ</div>
            <?php endif; ?>
            <span class="badge <?=$it['status']==='closed'?'badge-closed':'badge-open'?>">
              <?=$it['status']==='closed'?'ส่งคืนแล้ว':($it['type']==='lost'?'ของหาย':'เจอของ')?>
            </span>
          </div>
          <div class="body">
            <h3 style="display:flex;align-items:center;gap:8px;">
              <?=esc($it['title'])?>
              <small class="muted" style="font-size:12px;font-weight:400;">#<?= (int)$it['id']?></small>
            </h3>
            <p class="desc"><?=esc(mb_strimwidth($it['description'], 0, 140, '...', 'UTF-8'))?></p>
            <div class="meta">
              <span>หมวด: <?=esc(ucfirst($it['category']))?></span>
              <span>สถานที่: <?=esc($it['location'])?></span>
              <span>สถานะ: <?=esc($it['status'])?></span>
              <span>โพสต์เมื่อ: <?=esc($it['created_th'])?></span>
            </div>

            <div class="actions" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
              <a class="btn btn-secondary" href="/lafs/view.php?id=<?=(int)$it['id']?>">ดูหน้าโพสต์</a>

              <?php if ($it['status'] === 'open'): ?>
                <form method="post" action="/lafs/admin_posts.php" onsubmit="return confirm('ยืนยันปิดเคสโพสต์ #<?= (int)$it['id']?> ?');" class="inline-form">
                  <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                  <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                  <input type="hidden" name="action" value="close">
                  <button type="submit" class="btn btn-success">ปิดเคส</button>
                </form>
              <?php else: ?>
                <form method="post" action="/lafs/admin_posts.php" onsubmit="return confirm('ยืนยันเปิดเคสโพสต์ #<?= (int)$it['id']?> ใหม่ ?');" class="inline-form">
                  <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                  <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                  <input type="hidden" name="action" value="reopen">
                  <button type="submit" class="btn">เปิดเคส</button>
                </form>
              <?php endif; ?>

              <form method="post" action="/lafs/admin_posts.php" onsubmit="return confirm('ลบโพสต์นี้ถาวร? การลบไม่สามารถย้อนกลับได้');" class="inline-form">
                <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn" style="background:#ef4444;color:#fff;">ลบถาวร</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- เพจจิ้ง -->
  <?php if ($pages > 1): ?>
    <nav class="pagination">
      <?php for ($i=1; $i<=$pages; $i++): ?>
        <a class="<?= $i===$page ? 'active' : '' ?>"
           href="?<?=http_build_query(['page'=>$i])?>">
           <?=$i?>
        </a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
