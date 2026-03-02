<?php
// save_item.php
require_once __DIR__ . '/init.php';
csrf_verify();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('create.php');
}

// ------- รับและตรวจค่าฟอร์ม -------
$type          = $_POST['type']          ?? 'lost';
$category      = trim($_POST['category'] ?? '');
$title         = trim($_POST['title']    ?? '');
$description   = trim($_POST['description'] ?? '');
$location      = trim($_POST['location'] ?? '');
$date_event    = $_POST['date_event']    ?? '';
$contact_name  = trim($_POST['contact_name']  ?? '');
$contact_phone = trim($_POST['contact_phone'] ?? '');

if (!in_array($type, ['lost','found'], true)) $type = 'lost';
$allowed_categories = ['electronics','stationery','idcard','apparel','other'];
if (!in_array($category, $allowed_categories, true)) $category = 'other';

// Validate ขั้นพื้นฐาน
if ($title === '' || $description === '' || $location === '' || $date_event === '' || $contact_name === '' || $contact_phone === '') {
    http_response_code(400);
    die('กรุณากรอกข้อมูลให้ครบถ้วน');
}

// ------- บันทึก item (ยังไม่ใส่รูปปก) -------
$stmt = $pdo->prepare("
    INSERT INTO items(
        type, category, title, description, location, date_event,
        contact_name, contact_phone, status, created_at
    ) VALUES (
        :type, :category, :title, :description, :location, :date_event,
        :contact_name, :contact_phone, 'open', NOW()
    )
");
$stmt->execute([
    ':type'          => $type,
    ':category'      => $category,
    ':title'         => $title,
    ':description'   => $description,
    ':location'      => $location,
    ':date_event'    => $date_event,
    ':contact_name'  => $contact_name,
    ':contact_phone' => $contact_phone,
]);
$itemId = (int)$pdo->lastInsertId();

// ------- อัปโหลดรูปภาพ -------
$savedUrls = [];

try {
    // หลายไฟล์ (แนะนำ)
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $savedUrls = save_uploaded_images('images', 10 * 1024 * 1024, '/lostfound/uploads', __DIR__ . '/uploads');
    } 
    // เดี่ยว (รองรับแบบฟอร์มเก่า)
    elseif (!empty($_FILES['image'])) {
        $one = upload_image('image', 10 * 1024 * 1024, '/lostfound/uploads', __DIR__ . '/uploads');
        if ($one) $savedUrls[] = $one;
    }
} catch (Throwable $e) {
    $savedUrls = [];
} catch (Throwable $e) {
    // ถ้าอัปโหลดล้มเหลว ให้ไปต่อได้โดยไม่มีรูป
    // คุณอาจ flash_set('error', $e->getMessage()); เพื่อแสดงข้อความได้ตามต้องการ
    $savedUrls = [];
}

// บันทึกรูปลงตาราง item_images
if ($savedUrls) {
    $insImg = $pdo->prepare("INSERT INTO item_images (item_id, image_url, mime_type) VALUES (:item_id, :url, NULL)");
    foreach ($savedUrls as $url) {
        $insImg->execute([':item_id' => $itemId, ':url' => $url]);
    }

    // ตั้งรูปแรกเป็นปก (items.image_url)
    $u = $pdo->prepare("UPDATE items SET image_url = :url WHERE id = :id");
    $u->execute([':url' => $savedUrls[0], ':id' => $itemId]);
}

// เสร็จแล้วพาไปดูหน้ารายละเอียด
redirect('view.php?id=' . $itemId);
