<?php
// admin_user_new.php — เพิ่มผู้ดูแลระบบใหม่ (เฉพาะ root) + อัปโหลดรูปโปรไฟล์
require_once __DIR__ . '/init.php';

// อนุญาตเฉพาะ root
if (empty($_SESSION['admin']) || (($_SESSION['admin']['role'] ?? '') !== 'root')) {
  flash_set('error', 'ต้องเป็นผู้ใช้ระดับ root เท่านั้น');
  redirect('/lostfound/admin_login.php');
}

/**
 * อัปโหลดรูปโปรไฟล์ไปไว้ที่ /lostfound/uploads/avatars/
 * คืนค่า URL แบบ absolute path เช่น: /lostfound/uploads/avatars/xxxx.webp
 * ถ้าไม่อัปโหลด/ผิดพลาด ให้คืนค่า null
 */
function upload_avatar_to_public_url(string $field = 'avatar'): ?string {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
    return null; // ไม่ได้เลือกไฟล์
  }
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException('เกิดข้อผิดพลาดระหว่างอัปโหลด (code: '.$f['error'].')');
  }
  // จำกัดขนาด 2MB
  if ($f['size'] > 2 * 1024 * 1024) {
    throw new RuntimeException('ไฟล์ใหญ่เกิน 2MB');
  }
  // ตรวจ MIME
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($f['tmp_name']);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
  ];
  if (!isset($allowed[$mime])) {
    throw new RuntimeException('ชนิดไฟล์ไม่รองรับ (อนุญาต: jpg, png, gif, webp)');
  }
  // เตรียมโฟลเดอร์ปลายทาง
  $dir = __DIR__ . '/uploads/avatars';
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
      throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
    }
  }
  // สร้างชื่อไฟล์แบบสุ่ม
  $ext = $allowed[$mime];
  $basename = bin2hex(random_bytes(8)) . '.' . $ext;
  $target = $dir . DIRECTORY_SEPARATOR . $basename;

  if (!move_uploaded_file($f['tmp_name'], $target)) {
    throw new RuntimeException('ย้ายไฟล์ล้มเหลว');
  }

  // คืนค่าเป็น public URL (absolute path) เพื่อให้ tag <img> ใช้งานได้แน่นอน
  return '/lostfound/uploads/avatars/' . $basename;
}

// เมื่อส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $username = trim($_POST['username'] ?? '');
  $display  = trim($_POST['display_name'] ?? '');
  $pass1    = (string)($_POST['password'] ?? '');
  $pass2    = (string)($_POST['password_confirm'] ?? '');
  $active   = isset($_POST['is_active']) ? 1 : 0;

  // ตรวจข้อมูล
  if ($username === '' || $pass1 === '' || $pass2 === '') {
    flash_set('error', 'กรอกข้อมูลให้ครบ');
    redirect('/lostfound/admin_user_new.php');
  }
  if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
    flash_set('error', 'รูปแบบชื่อผู้ใช้ไม่ถูกต้อง (ใช้ a-z, A-Z, 0-9, _ ความยาว 3-32)');
    redirect('/lostfound/admin_user_new.php');
  }
  if (strlen($pass1) < 8) {
    flash_set('error', 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร');
    redirect('/lostfound/admin_user_new.php');
  }
  if (!hash_equals($pass1, $pass2)) {
    flash_set('error', 'รหัสผ่านยืนยันไม่ตรงกัน');
    redirect('/lostfound/admin_user_new.php');
  }

  // เตรียม hash
  $hash = password_hash($pass1, PASSWORD_DEFAULT);

  // อัปโหลดรูปโปรไฟล์ (ไม่บังคับ)
  $avatar_url = null;
  try {
    $avatar_url = upload_avatar_to_public_url('avatar'); // อาจเป็น null ถ้าไม่เลือกไฟล์
  } catch (Throwable $e) {
    // หากอัปโหลดผิดพลาด ให้เด้งพร้อมข้อความ (หรือจะปล่อย null ก็ได้)
    flash_set('error', 'อัปโหลดรูปโปรไฟล์ไม่สำเร็จ: ' . $e->getMessage());
    redirect('/lostfound/admin_user_new.php');
  }

  try {
    // สร้างผู้ใช้ใหม่ role = admin เสมอ
    $stmt = $pdo->prepare("
      INSERT INTO admin_users (username, password_hash, display_name, profile_image_url, role, is_active)
      VALUES (:u, :h, :d, :img, 'admin', :a)
    ");
    $stmt->execute([
      ':u'   => $username,
      ':h'   => $hash,
      ':d'   => ($display !== '' ? $display : null),
      ':img' => $avatar_url,      // null ได้
      ':a'   => $active ? 1 : 0,
    ]);
  } catch (Throwable $e) {
    // อาจชน UNIQUE (username ซ้ำ)
    flash_set('error', 'ไม่สามารถเพิ่มผู้ใช้: ' . $e->getMessage());
    redirect('/lostfound/admin_user_new.php');
  }

  flash_set('success', 'เพิ่มผู้ดูแลระบบสำเร็จ');
  redirect('/lostfound/admin_users.php'); // หน้า list ผู้ดูแล (สำหรับ root)
}

// ---------- แสดงฟอร์ม ----------
$page_title = 'เพิ่มผู้ดูแลระบบ | Lost & Found';
require_once __DIR__ . '/header.php';
?>
<section class="section">
  <div class="form" style="max-width:560px;margin:0 auto;">
    <h2 style="margin:0 0 12px;">เพิ่มผู้ดูแลระบบ</h2>
    <p class="muted" style="margin-top:-6px;color:var(--muted);">
      เฉพาะผู้ใช้ระดับ <strong>root</strong> เท่านั้นที่เข้าหน้านี้ได้
    </p>

    <?php if ($m = flash_get('error')): ?>
      <div class="alert error" role="alert"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_get('success')): ?>
      <div class="alert success" role="alert"><?= e($m) ?></div>
    <?php endif; ?>

    <form method="post" action="/lostfound/admin_user_new.php" autocomplete="off" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <label for="username">ชื่อผู้ใช้ (a-z, A-Z, 0-9, _ ความยาว 3-32) *</label>
      <input id="username" name="username" type="text" required pattern="[A-Za-z0-9_]{3,32}" autofocus>

      <label for="display_name">ชื่อที่แสดง (ไม่บังคับ)</label>
      <input id="display_name" name="display_name" type="text" maxlength="100" placeholder="เช่น ผู้ดูแลคนที่ 2">

      <div class="grid-form">
        <div>
          <label for="password">รหัสผ่าน *</label>
          <input id="password" name="password" type="password" required minlength="8" placeholder="อย่างน้อย 8 ตัวอักษร">
        </div>
        <div>
          <label for="password_confirm">ยืนยันรหัสผ่าน *</label>
          <input id="password_confirm" name="password_confirm" type="password" required minlength="8">
        </div>
        <div class="full">
          <label for="avatar">รูปโปรไฟล์ (ไม่บังคับ, ≤ 2MB, jpg/png/gif/webp)</label>
          <input id="avatar" name="avatar" type="file" accept="image/*" onchange="previewImage(event)">
          <div class="preview"><img id="preview" alt="" /></div>
        </div>
      </div>

      <label style="display:flex;align-items:center;gap:8px;margin-top:8px;">
        <input type="checkbox" name="is_active" checked>
        เปิดใช้งานบัญชีทันที
      </label>

      <div class="actions" style="margin-top:10px;">
        <button class="btn" type="submit">บันทึกผู้ดูแล</button>
        <a class="btn btn-secondary" href="/lostfound/admin_users.php">ยกเลิก</a>
      </div>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
