<?php
// admin_user_edit.php — แก้ไขโปรไฟล์ผู้ดูแล
require_once __DIR__ . '/init.php';

/* =========================
   อนุญาต: root หรือ เจ้าของโปรไฟล์ เท่านั้น
   ========================= */
$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0) {
  flash_set('error','รหัสผู้ใช้ไม่ถูกต้อง');
  redirect('/lostfound/admin_posts.php');
}

$isSigned = !empty($_SESSION['admin']) && in_array(($_SESSION['admin']['role'] ?? ''), ['admin','root'], true);
if (!$isSigned) {
  flash_set('error','กรุณาเข้าสู่ระบบผู้ดูแล');
  redirect('/lostfound/admin_login.php');
}
$meId   = (int)($_SESSION['admin']['id'] ?? 0);
$isRoot = (($_SESSION['admin']['role'] ?? '') === 'root');
if (!$isRoot && $meId !== $targetId) {
  flash_set('error','ไม่มีสิทธิ์ทำรายการนี้');
  redirect('/lostfound/admin_posts.php');
}

/* =========================
   โหลดข้อมูลผู้ใช้เป้าหมาย
   ========================= */
$stmt = $pdo->prepare("SELECT id, username, display_name, profile_image_url, role, is_active FROM admin_users WHERE id=:id");
$stmt->execute([':id'=>$targetId]);
$user = $stmt->fetch();
if (!$user) {
  flash_set('error','ไม่พบผู้ใช้ที่ต้องการแก้ไข');
  redirect('/lostfound/admin_posts.php');
}

/* =========================
   ฟังก์ชันอัปโหลด avatar -> /lostfound/uploads/avatars
   คืนค่า public URL (เช่น /lostfound/uploads/avatars/xxxx.webp)
   ========================= */
function upload_avatar_to_public_url(string $field = 'avatar'): ?string {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
    return null; // ไม่ได้เลือกไฟล์
  }
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException('อัปโหลดล้มเหลว (code: '.$f['error'].')');
  }
  if ($f['size'] > 2 * 1024 * 1024) {
    throw new RuntimeException('ไฟล์ใหญ่เกิน 2MB');
  }
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

  $dir = __DIR__ . '/uploads/avatars';
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
      throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้');
    }
  }

  $ext = $allowed[$mime];
  $basename = bin2hex(random_bytes(8)) . '.' . $ext;
  $target = $dir . DIRECTORY_SEPARATOR . $basename;

  if (!move_uploaded_file($f['tmp_name'], $target)) {
    throw new RuntimeException('ย้ายไฟล์ล้มเหลว');
  }

  return '/lostfound/uploads/avatars/' . $basename;
}

/* =========================
   POST: บันทึกการแก้ไข
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $display = trim($_POST['display_name'] ?? '');
  $pass1   = (string)($_POST['password'] ?? '');
  $pass2   = (string)($_POST['password_confirm'] ?? '');
  $removeAvatar = isset($_POST['remove_avatar']); // ติ๊กเพื่อลบรูป
  $newRole   = $_POST['role'] ?? $user['role'];
  $newActive = isset($_POST['is_active']) ? 1 : 0;

  // จำกัดสิทธิ์สำหรับ admin (ไม่ใช่ root)
  if (!$isRoot) {
    $newRole   = $user['role'];
    $newActive = (int)$user['is_active'];
  } else {
    // ป้องกันยุ่งกับ root
    if ($user['username'] === 'root' || $user['role'] === 'root') {
      $newRole   = 'root';
      $newActive = 1;
      // อนุญาตให้เปลี่ยน/ลบ avatar ของ root ได้
    }
  }

  // ตรวจรหัสผ่าน (ถ้าใส่มา)
  $pwSql = '';
  $params = [
    ':id'        => $user['id'],
    ':display'   => ($display !== '' ? $display : null),
    ':role'      => in_array($newRole, ['root','admin'], true) ? $newRole : $user['role'],
    ':is_active' => (int)$newActive,
  ];
  if ($pass1 !== '' || $pass2 !== '') {
    if (!hash_equals($pass1, $pass2)) {
      flash_set('error','รหัสผ่านยืนยันไม่ตรงกัน');
      redirect('/lostfound/admin_user_edit.php?id='.$user['id']);
    }
    if (strlen($pass1) < 8) {
      flash_set('error','รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร');
      redirect('/lostfound/admin_user_edit.php?id='.$user['id']);
    }
    $params[':hash'] = password_hash($pass1, PASSWORD_DEFAULT);
    $pwSql = ", password_hash=:hash";
  }

  // จัดการรูปโปรไฟล์
  $avatarSql = '';
  try {
    $uploadedUrl = upload_avatar_to_public_url('avatar'); // อาจเป็น null
    if ($uploadedUrl) {
      $params[':img'] = $uploadedUrl;
      $avatarSql = ", profile_image_url=:img";
    } elseif ($removeAvatar) {
      $params[':img'] = null;
      $avatarSql = ", profile_image_url=:img";
    }
  } catch (Throwable $e) {
    flash_set('error','อัปโหลดรูปโปรไฟล์ไม่สำเร็จ: '.$e->getMessage());
    redirect('/lostfound/admin_user_edit.php?id='.$user['id']);
  }

  // อัปเดตข้อมูล
  $sql = "UPDATE admin_users
          SET display_name=:display, role=:role, is_active=:is_active {$pwSql} {$avatarSql}
          WHERE id=:id";
  $upd = $pdo->prepare($sql);
  $upd->execute($params);

  // รีเฟรชข้อมูลใน session ถ้าแก้ตัวเอง
  if ($meId === (int)$user['id']) {
    $_SESSION['admin']['name'] = ($display !== '' ? $display : $user['username']);
    if ($isRoot) {
      $_SESSION['admin']['role'] = $params[':role']; // โดยมากยังเป็น root
    }
  }

  flash_set('success','บันทึกข้อมูลผู้ใช้เรียบร้อย');
  if ($isRoot) {
    redirect('/lostfound/admin_users.php');
  } else {
    redirect('/lostfound/admin_posts.php');
  }
}

/* =========================
   แสดงฟอร์ม
   ========================= */
$page_title = 'แก้ไขผู้ดูแลระบบ';
require_once __DIR__ . '/header.php';

// เตรียม avatar แสดงผล (fallback เป็นไอคอนสีเทา)
$avatar_fallback = function_exists('avatar_fallback') ? avatar_fallback() : '/lostfound/assets/img/account_circle.png';
$avatar_url = function_exists('avatar_url')
  ? avatar_url($user['profile_image_url'])
  : (trim((string)$user['profile_image_url']) !== '' ? $user['profile_image_url'] : $avatar_fallback);
?>
<section class="section">
  <div class="form" style="max-width:560px;margin:0 auto;">
    <h2 style="margin:0 0 12px;">แก้ไขผู้ดูแลระบบ</h2>

    <?php if ($m = flash_get('error')): ?>
      <div class="alert error"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_get('success')): ?>
      <div class="alert success"><?= e($m) ?></div>
    <?php endif; ?>

    <div style="display:flex; align-items:center; gap:14px; margin-bottom:10px;">
      <img src="<?= e($avatar_url) ?>" alt="avatar" class="avatar-lg">
      <div>
        <div style="font-weight:700;"><?= e($user['display_name'] ?: $user['username']) ?></div>
        <div style="font-size:12px;color:var(--muted);">@<?= e($user['username']) ?> · <?= e($user['role']) ?><?= !$user['is_active'] ? ' · inactive' : '' ?></div>
      </div>
    </div>

    <form method="post" action="/lostfound/admin_user_edit.php?id=<?= (int)$user['id'] ?>" autocomplete="off" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <label>ชื่อที่แสดง</label>
      <input type="text" name="display_name" maxlength="100" value="<?= e($user['display_name']) ?>">

      <div class="grid-form">
        <div>
          <label>รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)</label>
          <input type="password" name="password" minlength="8" placeholder="อย่างน้อย 8 ตัวอักษร">
        </div>
        <div>
          <label>ยืนยันรหัสผ่านใหม่</label>
          <input type="password" name="password_confirm" minlength="8">
        </div>

        <div class="full">
          <label>รูปโปรไฟล์ (ไม่บังคับ, ≤ 2MB, jpg/png/gif/webp)</label>
          <!-- ใช้ data-preview ให้ JS พรีวิวไปยัง #previewAvatar -->
          <input type="file" name="avatar" accept="image/*" data-preview="#previewAvatar">
          <div class="preview"><img id="previewAvatar" class="avatar-lg" alt=""></div>

          <label style="display:flex;align-items:center;gap:8px;margin-top:8px;">
            <input type="checkbox" name="remove_avatar">
            ลบรูปโปรไฟล์ (ใช้ไอคอนสีเทาแทน)
          </label>
        </div>
      </div>

      <?php if ($isRoot): ?>
        <label>สิทธิ์</label>
        <select name="role" <?= ($user['username']==='root'||$user['role']==='root') ? 'disabled' : '' ?>>
          <option value="admin" <?= $user['role']==='admin'?'selected':''; ?>>admin</option>
          <option value="root"  <?= $user['role']==='root'?'selected':''; ?>>root</option>
        </select>

        <label style="display:flex;align-items:center;gap:8px;margin-top:8px;">
          <input type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?> <?= ($user['username']==='root'||$user['role']==='root') ? 'disabled' : '' ?>>
          เปิดใช้งานบัญชี
        </label>
      <?php else: ?>
        <p class="muted" style="margin-top:8px;">* คุณสามารถแก้ไขเฉพาะชื่อที่แสดง, รหัสผ่าน และรูปโปรไฟล์ของคุณเอง</p>
      <?php endif; ?>

      <div class="actions" style="margin-top:10px;">
        <button class="btn" type="submit">บันทึก</button>
        <?php if ($isRoot): ?>
          <a class="btn btn-secondary" href="/lostfound/admin_users.php">ยกเลิก</a>
        <?php else: ?>
          <a class="btn btn-secondary" href="/lostfound/admin_posts.php">ยกเลิก</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
