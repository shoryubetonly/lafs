<?php
// admin_users.php — รายชื่อผู้ดูแล (root เท่านั้น) แสดงเป็นการ์ดโปรไฟล์
require_once __DIR__ . '/init.php';

// อนุญาตเฉพาะ root
if (empty($_SESSION['admin']) || (($_SESSION['admin']['role'] ?? '') !== 'root')) {
  flash_set('error','ต้องเป็นผู้ใช้ระดับ root เท่านั้น');
  redirect('/lostfound/admin_login.php');
}

/* =============== จัดการคำสั่ง (POST) =============== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $action = $_POST['action'] ?? '';
  $uid    = (int)($_POST['id'] ?? 0);

  if ($uid <= 0) {
    flash_set('error','รหัสผู้ใช้ไม่ถูกต้อง');
    redirect('/lostfound/admin_users.php');
  }

  // ดึงข้อมูลผู้ใช้ก่อนทำรายการ
  $u = $pdo->prepare("SELECT id, username, role, is_active FROM admin_users WHERE id=:id");
  $u->execute([':id'=>$uid]);
  $row = $u->fetch();

  if (!$row) {
    flash_set('error','ไม่พบผู้ใช้');
    redirect('/lostfound/admin_users.php');
  }

  // ป้องกันยุ่งกับ root
  $isTargetRoot = ($row['username'] === 'root' || $row['role'] === 'root');
  if ($isTargetRoot && in_array($action, ['delete','deactivate'], true)) {
    flash_set('error','ไม่สามารถลบหรือปิดใช้งานผู้ใช้ root');
    redirect('/lostfound/admin_users.php');
  }

  if ($action === 'delete') {
    $del = $pdo->prepare("DELETE FROM admin_users WHERE id=:id");
    $del->execute([':id'=>$uid]);
    flash_set('success','ลบผู้ใช้เรียบร้อย');
    redirect('/lostfound/admin_users.php');
  }

  if ($action === 'deactivate') {
    $up = $pdo->prepare("UPDATE admin_users SET is_active=0 WHERE id=:id");
    $up->execute([':id'=>$uid]);
    flash_set('success','ปิดใช้งานผู้ใช้เรียบร้อย');
    redirect('/lostfound/admin_users.php');
  }

  if ($action === 'activate') {
    $up = $pdo->prepare("UPDATE admin_users SET is_active=1 WHERE id=:id");
    $up->execute([':id'=>$uid]);
    flash_set('success','เปิดใช้งานผู้ใช้เรียบร้อย');
    redirect('/lostfound/admin_users.php');
  }

  if ($action === 'resetpwd') {
    $new  = bin2hex(random_bytes(4)); // 8 ตัว
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $up = $pdo->prepare("UPDATE admin_users SET password_hash=:h WHERE id=:id");
    $up->execute([':h'=>$hash, ':id'=>$uid]);
    flash_set('success','รีเซ็ตรหัสผ่านสำเร็จ — รหัสใหม่: '.$new.' (โปรดเปลี่ยนทันที)');
    redirect('/lostfound/admin_users.php');
  }

  flash_set('error','คำสั่งไม่ถูกต้อง');
  redirect('/lostfound/admin_users.php');
}

/* =============== ดึงรายชื่อทั้งหมด =============== */
$list = $pdo->query("
  SELECT id, username, display_name, profile_image_url, role, is_active,
         DATE_FORMAT(created_at,'%Y-%m-%d %H:%i') AS created_th
  FROM admin_users
  ORDER BY role='root' DESC, created_at DESC
")->fetchAll();

/* =============== UI =============== */
$page_title = 'ผู้ดูแลระบบ | Root';
require_once __DIR__ . '/header.php';

// ไอคอนสีเทาสำหรับ fallback (เผื่อไม่มี helper)
$avatar_fallback = function_exists('avatar_fallback')
  ? avatar_fallback()
  : '/lostfound/assets/img/account_circle.png';
?>
<section class="section">
  <div class="form" style="display:flex;align-items:center;justify-content:space-between;">
    <h2 style="margin:0;">ผู้ดูแลระบบทั้งหมด</h2>
    <div class="actions">
      <a class="btn btn-secondary" href="/lostfound/admin_posts.php">← กลับจัดการโพสต์</a>
      <a class="btn" href="/lostfound/admin_user_new.php">+ เพิ่มแอดมิน</a>
    </div>
  </div>

  <?php if ($m = flash_get('success')): ?>
    <div class="alert success" style="margin-top:12px;"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash_get('error')): ?>
    <div class="alert error" style="margin-top:12px;"><?= e($m) ?></div>
  <?php endif; ?>

  <div class="grid" style="margin-top:16px;">
    <?php foreach ($list as $u): 
      $avatar = function_exists('avatar_url')
        ? avatar_url($u['profile_image_url'])
        : (trim((string)$u['profile_image_url']) !== '' ? $u['profile_image_url'] : $avatar_fallback);
      $isRootUser = ($u['username']==='root' || $u['role']==='root');
    ?>
      <article class="card" style="overflow:visible;">
        <div class="body">
          <!-- ส่วนหัวการ์ด: avatar + ชื่อ + ป้ายสถานะ -->
          <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div style="display:flex; align-items:center; gap:12px;">
              <img src="<?= e($avatar) ?>" alt="avatar" class="avatar-lg">
              <div>
                <div style="font-weight:700;margin-bottom:2px;">
                  <?= e($u['display_name'] ?: $u['username']) ?>
                  <small class="muted" style="font-weight:400;">@<?= e($u['username']) ?></small>
                </div>
                <div class="meta" style="gap:8px;">
                  <span>สิทธิ์: <?= e($u['role']) ?></span>
                  <span><?= $u['is_active'] ? 'active' : 'inactive' ?></span>
                  <span>สร้างเมื่อ: <?= e($u['created_th']) ?></span>
                </div>
              </div>
            </div>

            <!-- เมนู “ด้านใน” ด้วย details/summary (สามจุด) -->
            <details class="account-menu" style="position:relative;">
              <summary title="เมนูเพิ่มเติม" style="list-style:none;cursor:pointer;font-size:22px;padding:6px 10px;border-radius:8px;">
                ⋯
              </summary>
              <div class="dropdown" style="position:absolute;right:0;top:36px;min-width:220px;background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);padding:8px;z-index:30;">
                <div class="who" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                  <img src="<?= e($avatar) ?>" class="avatar" alt="avatar">
                  <div>
                    <div class="name" style="font-weight:600;"><?= e($u['display_name'] ?: $u['username']) ?></div>
                    <div class="role" style="font-size:12px;color:var(--muted);">@<?= e($u['username']) ?> · <?= e($u['role']) ?></div>
                  </div>
                </div>

                <div class="menu">
                  <?php if (!$isRootUser): ?>
                    <?php if ($u['is_active']): ?>
                      <form method="post" action="/lostfound/admin_users.php" class="inline-form" onsubmit="return confirm('ปิดใช้งานผู้ใช้นี้?');">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="deactivate">
                        <button type="submit">ปิดใช้งาน</button>
                      </form>
                    <?php else: ?>
                      <form method="post" action="/lostfound/admin_users.php" class="inline-form">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="activate">
                        <button type="submit">เปิดใช้งาน</button>
                      </form>
                    <?php endif; ?>

                    <form method="post" action="/lostfound/admin_users.php" class="inline-form" onsubmit="return confirm('รีเซ็ตรหัสผ่านและแสดงรหัสใหม่?');">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="action" value="resetpwd">
                      <button type="submit">รีเซ็ตรหัสผ่าน</button>
                    </form>

                    <form method="post" action="/lostfound/admin_users.php" class="inline-form" onsubmit="return confirm('ลบผู้ใช้นี้ถาวร?');">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" style="color:#ef4444;">ลบ</button>
                    </form>
                  <?php else: ?>
                    <div style="padding:8px 10px;font-size:12px;color:var(--muted);">* ไม่สามารถลบ/ปิดใช้งานบัญชี root</div>
                  <?php endif; ?>
                </div>
              </div>
            </details>
          </div>

          <!-- ปุ่ม “แก้ไข” ด้านนอกตามโจทย์ -->
          <div class="actions" style="margin-top:12px;">
            <a class="btn btn-secondary" href="/lostfound/admin_user_edit.php?id=<?= (int)$u['id'] ?>">แก้ไข</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
