<?php
// admin_seed.php — สร้างผู้ดูแลระบบคนแรก (ครั้งเดียว)
require_once __DIR__ . '/init.php';

// เช็คว่ามีผู้ใช้แอดมินอยู่แล้วหรือยัง
$has = (int)$pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
if ($has > 0) {
  flash_set('info', 'มีผู้ดูแลระบบอยู่แล้ว กรุณาเข้าสู่ระบบ');
  redirect('/lostfound/admin_login.php');
}

// ส่งฟอร์มมาหรือยัง
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $username = trim($_POST['username'] ?? '');
  $display  = trim($_POST['display_name'] ?? '');
  $pass1    = (string)($_POST['password'] ?? '');
  $pass2    = (string)($_POST['password_confirm'] ?? '');

  // ตรวจความถูกต้อง
  if ($username === '' || $pass1 === '' || $pass2 === '') {
    flash_set('error', 'กรอกข้อมูลให้ครบ');
    redirect('/lostfound/admin_seed.php');
  }
  // อนุญาต a-zA-Z0-9_ 3-32 ตัวอักษร
  if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
    flash_set('error', 'รูปแบบชื่อผู้ใช้ไม่ถูกต้อง (ใช้ a-z, A-Z, 0-9, _ ความยาว 3-32)');
    redirect('/lostfound/admin_seed.php');
  }
  if (strlen($pass1) < 8) {
    flash_set('error', 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร');
    redirect('/lostfound/admin_seed.php');
  }
  if (!hash_equals($pass1, $pass2)) {
    flash_set('error', 'รหัสผ่านยืนยันไม่ตรงกัน');
    redirect('/lostfound/admin_seed.php');
  }

  // สร้างแฮช
  $hash = password_hash($pass1, PASSWORD_DEFAULT);

  // บันทึก
  try {
    $stmt = $pdo->prepare("
      INSERT INTO admin_users (username, password_hash, display_name, role, is_active)
      VALUES (:u, :h, :d, 'admin', 1)
    ");
    $stmt->execute([
      ':u' => $username,
      ':h' => $hash,
      ':d' => ($display !== '' ? $display : null),
    ]);
  } catch (Throwable $e) {
    // เผื่อ username ชน unique
    flash_set('error', 'ไม่สามารถสร้างผู้ใช้ได้: ' . $e->getMessage());
    redirect('/lostfound/admin_seed.php');
  }

  flash_set('success', 'สร้างผู้ดูแลเรียบร้อยแล้ว! กรุณาเข้าสู่ระบบ');
  redirect('/lostfound/admin_login.php');
}

// ---------- แสดงฟอร์ม ----------
$page_title = 'สร้างผู้ดูแลระบบคนแรก | Lost & Found';
require_once __DIR__ . '/header.php';
?>
<section class="section">
  <div class="form" style="max-width:520px;margin:0 auto;">
    <h2 style="margin:0 0 12px;">สร้างผู้ดูแลระบบคนแรก</h2>
    <p class="muted" style="margin-top:-6px;color:var(--muted);">
      ไฟล์นี้ใช้เพื่อกำหนดผู้ดูแลระบบครั้งแรกเท่านั้น (หากสร้างแล้วจะเข้าหน้านี้ไม่ได้)
    </p>

    <?php if ($m = flash_get('error')): ?>
      <div class="alert error" role="alert"><?=esc($m)?></div>
    <?php endif; ?>
    <?php if ($m = flash_get('success')): ?>
      <div class="alert success" role="alert"><?=esc($m)?></div>
    <?php endif; ?>
    <?php if ($m = flash_get('info')): ?>
      <div class="alert" role="status"><?=esc($m)?></div>
    <?php endif; ?>

    <form method="post" action="/lostfound/admin_seed.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">

      <label for="username">ชื่อผู้ใช้ (a-z, A-Z, 0-9, _ ความยาว 3-32) *</label>
      <input id="username" name="username" type="text" required pattern="[A-Za-z0-9_]{3,32}" autofocus>

      <label for="display_name">ชื่อที่แสดง (ไม่บังคับ)</label>
      <input id="display_name" name="display_name" type="text" maxlength="100" placeholder="เช่น ผู้ดูแลระบบ">

      <label for="password">รหัสผ่าน *</label>
      <input id="password" name="password" type="password" required minlength="8" placeholder="อย่างน้อย 8 ตัวอักษร">

      <label for="password_confirm">ยืนยันรหัสผ่าน *</label>
      <input id="password_confirm" name="password_confirm" type="password" required minlength="8">

      <div class="actions">
        <button class="btn" type="submit">สร้างผู้ดูแล</button>
        <a class="btn btn-secondary" href="/lostfound/">ยกเลิก</a>
      </div>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
