<?php
// functions.php — ฟังก์ชันช่วยเหลือ/ความปลอดภัย/อัปโหลดรูป
// ใช้ร่วมกับ init.php ที่เรียก session_start() แล้ว

// ---------- HTML escaping ----------
function esc(string $s = ''): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
// alias ให้โค้ดเดิมของคุณ
function e($str) { return esc((string)($str ?? '')); }

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

// ตรวจ POST: รองรับทั้งชื่อฟิลด์ 'csrf' (ใหม่) และ 'csrf_token' (เดิม)
function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf'] ?? ($_POST['csrf_token'] ?? '');
        $sess = $_SESSION['csrf'] ?? '';
        if (!$sent || !$sess || !hash_equals($sess, $sent)) {
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }
}
// alias ให้โค้ดเดิมของคุณ
function verify_csrf() { csrf_verify(); }

// ---------- Redirect + Flash ----------
function redirect(string $url): void {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }
    echo '<meta http-equiv="refresh" content="0;url='.esc($url).'">'; // fallback
    exit;
}

function flash_set(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}
function flash_get(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}

// ---------- Upload helpers ----------
function ensure_upload_dir(string $path): void {
    if (!is_dir($path) && !mkdir($path, 0775, true)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้: ' . $path);
    }
}

function allowed_image_mimes(): array {
    return [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
}

/**
 * อัปโหลด “ไฟล์เดียว” (field name เช่น 'image')
 * คืนค่า: public URL (เช่น /lostfound/uploads/2025/10/xxxx.jpg) หรือ null ถ้าไม่เลือกไฟล์
 */
function upload_image(
    string $field_name,
    int $maxBytes = 2097152, // 2MB
    string $uploadsBasePublic = '/lostfound/uploads',
    ?string $uploadsBaseReal = null
): ?string {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field_name];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('ไฟล์ใหญ่เกินกำหนด (' . (int)($maxBytes/1024/1024) . 'MB)');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = allowed_image_mimes();
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('ชนิดไฟล์ไม่รองรับ');
    }

    if ($uploadsBaseReal === null) {
        $uploadsBaseReal = __DIR__ . '/uploads';
    }
    $subDir = date('Y') . '/' . date('m');
    $targetDir = rtrim($uploadsBaseReal, '/\\') . '/' . $subDir;
    ensure_upload_dir($targetDir);

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('ย้ายไฟล์ล้มเหลว');
    }

    $publicUrl = rtrim($uploadsBasePublic, '/') . '/' . $subDir . '/' . $filename;
    return $publicUrl;
}

/**
 * อัปโหลด “หลายไฟล์” (field name เช่น 'images[]')
 * คืนค่า: array ของ public URL ที่บันทึกสำเร็จ
 */
function save_uploaded_images(
    string $field_name,
    int $maxBytes = 2097152, // 2MB
    string $uploadsBasePublic = '/lostfound/uploads',
    ?string $uploadsBaseReal = null
): array {
    $saved = [];
    if (empty($_FILES[$field_name]) || !is_array($_FILES[$field_name]['name'])) {
        return $saved;
    }
    $names = $_FILES[$field_name]['name'];
    $types = $_FILES[$field_name]['type'];
    $tmpns = $_FILES[$field_name]['tmp_name'];
    $errs  = $_FILES[$field_name]['error'];
    $sizes = $_FILES[$field_name]['size'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowed = allowed_image_mimes();

    if ($uploadsBaseReal === null) {
        $uploadsBaseReal = __DIR__ . '/uploads';
    }
    $subDir = date('Y') . '/' . date('m');
    $targetDir = rtrim($uploadsBaseReal, '/\\') . '/' . $subDir;
    ensure_upload_dir($targetDir);

    $count = count($names);
    for ($i = 0; $i < $count; $i++) {
        if ($errs[$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($errs[$i] !== UPLOAD_ERR_OK) continue;
        if ($sizes[$i] > $maxBytes) continue;

        $mime = $finfo->file($tmpns[$i]);
        if (!isset($allowed[$mime])) continue;

        $ext = $allowed[$mime];
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpns[$i], $targetPath)) continue;

        $publicUrl = rtrim($uploadsBasePublic, '/') . '/' . $subDir . '/' . $filename;
        $saved[] = $publicUrl;
    }
    return $saved;
}

// ---------- Query helper (สำหรับ index.php เพจจิ้ง/ฟิลเตอร์) ----------
function fetch_items(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 12): array {
    $where = [];
    $args  = [];

    if (!empty($filters['q'])) {
        $where[] = '(title LIKE :q OR description LIKE :q OR location LIKE :q)';
        $args[':q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['type']) && in_array($filters['type'], ['lost','found'], true)) {
        $where[] = 'type = :type';
        $args[':type'] = $filters['type'];
    }
    if (!empty($filters['status']) && in_array($filters['status'], ['open','closed'], true)) {
        $where[] = 'status = :status';
        $args[':status'] = $filters['status'];
    }
    if (!empty($filters['category'])) {
        $where[] = 'category = :cat';
        $args[':cat'] = $filters['category'];
    }

    $sql = 'SELECT * FROM items';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY created_at DESC';

    $offset = max(0, ($page - 1) * $perPage);
    $sql .= ' LIMIT :lim OFFSET :off';

    $stmt = $pdo->prepare($sql);
    foreach ($args as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $countSql = 'SELECT COUNT(*) FROM items' . ($where ? (' WHERE ' . implode(' AND ', $where)) : '');
    $cstmt = $pdo->prepare($countSql);
    foreach ($args as $k => $v) $cstmt->bindValue($k, $v);
    $cstmt->execute();
    $total = (int)$cstmt->fetchColumn();

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
}
// ---- Admin helpers ----
function current_admin(): ?array {
  return $_SESSION['admin'] ?? null;
}
function is_admin(): bool {
  return !empty($_SESSION['admin']) && in_array($_SESSION['admin']['role'] ?? '', ['admin','root'], true);
}
function is_root(): bool {
  return !empty($_SESSION['admin']) && (($_SESSION['admin']['role'] ?? '') === 'root');
}
function require_root(): void {
  if (!is_root()) {
    flash_set('error','ต้องเป็นผู้ใช้ระดับ root เท่านั้น');
    redirect('/lostfound/admin_login.php');
  }
}
function require_admin_or_self(int $targetUserId): void {
  // root แก้ใครก็ได้ / admin แก้ได้เฉพาะตัวเอง
  if (is_root()) return;
  if (!is_admin() || (int)($_SESSION['admin']['id'] ?? 0) !== $targetUserId) {
    flash_set('error','ไม่มีสิทธิ์ทำรายการนี้');
    redirect('/lostfound/admin_login.php');
  }
}
// ===== Avatar helpers =====
function avatar_fallback(): string {
    return '/lostfound/assets/img/account_circle.png';
}
function avatar_url(?string $maybe): string {
    $maybe = trim((string)$maybe);
    return $maybe !== '' ? $maybe : avatar_fallback();
}
