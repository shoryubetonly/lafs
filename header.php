<?php require_once __DIR__ . '/init.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php
    // ตั้งชื่อหน้าแบบยืดหยุ่น
    $page_title = isset($page_title) && $page_title !== '' ? $page_title : 'Lost & Found | แผนก IT';
  ?>
  <title><?= e($page_title) ?></title>

  <!-- ใช้พาธ absolute ป้องกัน asset ไม่เจอเมื่ออยู่ในโฟลเดอร์ย่อย -->
  <link rel="stylesheet" href="/lostfound/assets/css/style.css">

  <!-- Thai-friendly font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">

  <!-- รองรับ light/dark -->
  <meta name="color-scheme" content="light dark">

  <!-- สไตล์เสริมเล็กน้อยสำหรับเมนูโปรไฟล์ (อยู่ที่นี่เพื่อไฟล์นี้ไฟล์เดียวพอ) -->
  <style>
    .account-menu{ position:relative; display:inline-block; margin-left:8px; }
    .account-menu summary{ list-style:none; cursor:pointer; display:flex; align-items:center; }
    .account-menu summary::-webkit-details-marker{ display:none; }
    .dropdown{
      position:absolute; right:0; margin-top:8px; min-width:240px;
      background:var(--card); border:1px solid var(--border); border-radius:12px;
      box-shadow: var(--shadow); padding:10px; z-index:50;
    }
    .dropdown .who{ display:flex; align-items:center; gap:10px; margin-bottom:8px; }
    .dropdown .who .name{ font-weight:600; }
    .dropdown .who .role{ font-size:12px; color:var(--muted); }
    .dropdown .menu a, .dropdown .menu form button{
      display:block; width:100%; text-align:left; padding:8px 10px; border-radius:8px;
      border:none; background:transparent; cursor:pointer; color:inherit; font-weight:600;
    }
    .dropdown .menu a:hover, .dropdown .menu form button:hover{ background:rgba(43,127,255,.08); }
  </style>
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="/lostfound/">
      <div class="logo">🔎</div>
      <div>
        <h1>Lost & Found</h1>
        <p>ระบบประกาศตามหาของหาย (แผนก IT)</p>
      </div>
    </a>

    <nav class="nav">
      <a href="/lostfound/index.php">หน้าแรก</a>
      <a href="/lostfound/create.php" class="btn">โพสต์ประกาศ</a>

      <?php
      // เตรียมข้อมูลโปรไฟล์สำหรับหัวเว็บ
      $isSigned = !empty($_SESSION['admin']) && in_array(($_SESSION['admin']['role'] ?? ''), ['admin','root'], true);

      // defaults
      $display_name = 'ผู้ดูแลระบบ';
      $role_label   = '';
      $avatar_url   = function_exists('avatar_fallback') ? avatar_fallback() : '/lostfound/assets/img/account_circle.png';

      if ($isSigned) {
        $display_name = $_SESSION['admin']['name'] ?? ($_SESSION['admin']['username'] ?? 'ผู้ดูแลระบบ');
        $role_label   = ($_SESSION['admin']['role'] ?? '');

        // ดึงรูปโปรไฟล์ล่าสุดจาก DB เพื่อให้ตรงตามของจริงเสมอ
        try {
          $aid = (int)($_SESSION['admin']['id'] ?? 0);
          if ($aid > 0) {
            $st = $pdo->prepare("SELECT profile_image_url FROM admin_users WHERE id=:id LIMIT 1");
            $st->execute([':id' => $aid]);
            $avatar_db = $st->fetchColumn();
            if (function_exists('avatar_url')) {
              $avatar_url = avatar_url($avatar_db);
            } else {
              $avatar_url = trim((string)$avatar_db) !== '' ? $avatar_db : '/lostfound/assets/img/account_circle.png';
            }
          }
        } catch (Throwable $e) {
          // ถ้า query มีปัญหา ให้ใช้ fallback
          $avatar_url = function_exists('avatar_fallback') ? avatar_fallback() : '/lostfound/assets/img/account_circle.png';
        }
      }
      ?>

      <?php if ($isSigned): ?>
        <!-- ปุ่มเข้าหน้าจัดการโพสต์ -->
        <a href="/lostfound/admin_posts.php" class="btn-secondary" style="margin-left:8px;">จัดการโพสต์</a>

        <!-- เมนูโปรไฟล์แบบ dropdown -->
        <details class="account-menu">
          <summary title="โปรไฟล์ผู้ดูแล">
            <img class="avatar" src="<?= e($avatar_url) ?>" alt="โปรไฟล์">
          </summary>
          <div class="dropdown">
            <div class="who">
              <img class="avatar" src="<?= e($avatar_url) ?>" alt="โปรไฟล์">
              <div>
                <div class="name"><?= e($display_name) ?></div>
                <div class="role"><?= e($role_label) ?></div>
              </div>
            </div>

            <div class="menu">
              <?php if (($_SESSION['admin']['role'] ?? '') === 'root'): ?>
                <a href="/lostfound/admin_users.php">ผู้ดูแลระบบทั้งหมด</a>
                <a href="/lostfound/admin_user_new.php">+ เพิ่มแอดมิน</a>
              <?php else: ?>
                <a href="/lostfound/admin_user_edit.php?id=<?= (int)($_SESSION['admin']['id'] ?? 0) ?>">โปรไฟล์ของฉัน</a>
              <?php endif; ?>

              <form action="/lostfound/admin_logout.php" method="post" style="margin-top:6px;">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit">ออกจากระบบ</button>
              </form>
            </div>
          </div>
        </details>
      <?php else: ?>
        <!-- ปุ่มเข้าสู่ระบบผู้ดูแล -->
        <a href="/lostfound/admin_login.php" class="btn-secondary" style="margin-left:8px;">เข้าสู่ระบบผู้ดูแล</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
