<?php
// admin_login.php — ฟอร์มเข้าสู่ระบบผู้ดูแล + ตรวจสอบ
require_once __DIR__ . '/init.php';

// ถ้าเข้าแล้ว ไม่ต้องล็อกอินซ้ำ
if (!empty($_SESSION['admin']) && in_array($_SESSION['admin']['role'] ?? '', ['admin','root'], true)) {
  redirect('/lostfound/admin_posts.php');
}

// เมื่อส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    flash_set('error', 'กรอกชื่อผู้ใช้และรหัสผ่านให้ครบ');
    redirect('/lostfound/admin_login.php');
  }

  // พยายามดึงผู้ใช้จากตาราง admin_users
  try {
    $stmt = $pdo->prepare("SELECT id, username, password_hash, display_name, role, is_active 
                           FROM admin_users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $u = $stmt->fetch();
  } catch (PDOException $e) {
    // ถ้ายังไม่ได้สร้างตาราง จะพาไปหน้า seed (สร้างผู้ดูแลคนแรก)
    if ($e->getCode() === '42S02') { // Base table not found
      flash_set('info', 'ยังไม่ได้ตั้งค่าระบบผู้ดูแล โปรดสร้างผู้ดูแลคนแรกก่อน');
      redirect('/lostfound/admin_seed.php');
    }
    throw $e;
  }

  $ok = 0;
  if ($u && (int)$u['is_active'] === 1 && password_verify($password, $u['password_hash'])) {
    $ok = 1;

    // ผูกเซสชันแอดมิน
    session_regenerate_id(true);
    $_SESSION['admin'] = [
      'id'       => (int)$u['id'],
      'username' => $u['username'],
      'name'     => $u['display_name'] ?: $u['username'],
      'role'     => $u['role'],    // 'admin' หรือ 'root'
      'logged_at'=> date('Y-m-d H:i:s'),
    ];
  }

  // บันทึก log (สำเร็จ/ไม่สำเร็จ)
  try {
    $log = $pdo->prepare("INSERT INTO admin_login_log (username, ip, user_agent, ok) 
                          VALUES (:u, :ip, :ua, :ok)");
    $log->execute([
      ':u'  => $username !== '' ? $username : '(empty)',
      ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
      ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
      ':ok' => $ok ? 1 : 0,
    ]);
  } catch (Throwable $e) {
    // ถ้าตาราง log ยังไม่มี ให้ข้ามได้ ไม่ต้องขว้าง error ใส่ผู้ใช้
  }

  if ($ok) {
    redirect('/lostfound/admin_posts.php');
  } else {
    // ถ่วงเวลาเล็กน้อยกัน brute force
    usleep(400000);
    flash_set('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง หรือบัญชีถูกปิดใช้งาน');
    redirect('/lostfound/admin_login.php');
  }
}

// ------------ หน้าแบบฟอร์ม ------------
$page_title = 'เข้าสู่ระบบผู้ดูแล | Lost & Found';
require_once __DIR__ . '/header.php';
?>
<section class="section">
  <div class="form" style="max-width:480px;margin:0 auto;">
    <h2 style="margin:0 0 12px;">เข้าสู่ระบบผู้ดูแล</h2>

    <?php if ($m = flash_get('info')): ?>
      <div class="alert" role="status"><?=esc($m)?></div>
    <?php endif; ?>
    <?php if ($m = flash_get('error')): ?>
      <div class="alert error" role="alert"><?=esc($m)?></div>
    <?php endif; ?>
    <?php if ($m = flash_get('success')): ?>
      <div class="alert success" role="alert"><?=esc($m)?></div>
    <?php endif; ?>

    <form method="post" action="/lostfound/admin_login.php" autocomplete="off">
      <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">

      <label for="username">ชื่อผู้ใช้</label>
      <input id="username" name="username" type="text" required autofocus placeholder="yourname">

      <label for="password">รหัสผ่าน</label>
      <input id="password" name="password" type="password" required placeholder="••••••••">

      <div class="actions">
        <button class="btn" type="submit">เข้าสู่ระบบ</button>
        <a class="btn btn-secondary" href="/lostfound/">ยกเลิก</a>
      </div>

      <p class="muted" style="margin-top:8px;color:var(--muted);font-size:13px;">
        * เฉพาะผู้ดูแลระบบที่ได้รับสิทธิ์เท่านั้น
      </p>
      <p class="muted" style="margin-top:4px;color:var(--muted);font-size:13px;">
        ถ้ายังไม่เคยตั้งค่าผู้ดูแล <a href="/lostfound/admin_seed.php">สร้างผู้ดูแลคนแรก</a>
      </p>
    </form>
  </div>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
