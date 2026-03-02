<?php require_once __DIR__ . '/header.php'; ?>
<section class="section">
  <h2>โพสต์ประกาศใหม่</h2>

  <form class="form" action="save_item.php" method="post" enctype="multipart/form-data" id="create-form">
    <!-- ใช้ csrf_token() และ esc() ตาม functions.php -->
    <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">

    <div class="grid-form">
      <div>
        <label>ประเภท *</label>
        <select name="type" required>
          <option value="lost">ตามหาของหาย</option>
          <option value="found">เจอของ</option>
        </select>
      </div>

      <div>
        <label>หมวดหมู่ *</label>
        <select name="category" required>
          <option value="electronics">อุปกรณ์อิเล็กทรอนิกส์</option>
          <option value="stationery">เครื่องเขียน</option>
          <option value="idcard">บัตร/เอกสาร</option>
          <option value="apparel">เสื้อผ้า/เครื่องแต่งกาย</option>
          <option value="other">อื่น ๆ</option>
        </select>
      </div>

      <div class="full">
        <label>หัวข้อประกาศ *</label>
        <input type="text" name="title" maxlength="120" placeholder="เช่น ทำกระเป๋าสตางค์หาย สีดำ ยี่ห้อ..." required>
      </div>

      <div class="full">
        <label>รายละเอียด *</label>
        <textarea name="description" rows="5" placeholder="รายละเอียดลักษณะ/จุดสังเกต สิ่งของ และเหตุการณ์" required></textarea>
      </div>

      <div>
        <label>สถานที่ *</label>
        <input type="text" name="location" maxlength="120" placeholder="อาคาร/ชั้น/ห้อง/บริเวณ" required>
      </div>

      <div>
        <label>วันที่เกิดเหตุ *</label>
        <input type="date" name="date_event" required>
      </div>

      <div>
        <label>ผู้ติดต่อ *</label>
        <input type="text" name="contact_name" maxlength="80" placeholder="ชื่อผู้ติดต่อ" required>
      </div>

      <div>
        <label>เบอร์โทร/ช่องทางติดต่อ *</label>
        <input type="text" name="contact_phone" maxlength="50" placeholder="เบอร์โทร/Line/FB" required>
      </div>

      <div class="full">
        <label>อัปโหลดรูป (ไม่บังคับ, ≤ 10MB/ไฟล์, jpg/png/gif/webp) — เลือกได้หลายไฟล์</label>
        <!-- ใช้ชื่อ images[] ให้เข้ากับ save_uploaded_images() -->
        <input type="file" name="images[]" id="images" accept="image/*" multiple>
        <div class="preview" id="preview" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;"></div>
      </div>
    </div>

    <div class="actions" style="margin-top:12px;">
      <button class="btn" type="submit">บันทึกประกาศ</button>
      <a class="btn btn-secondary" href="index.php">ยกเลิก</a>
    </div>
  </form>
</section>

<script>
(function(){
  const MAX_BYTES = 10 * 1024 * 1024; // 10MB/ไฟล์
  const ALLOWED = ['image/jpeg','image/png','image/gif','image/webp'];

  const input = document.getElementById('images');
  const preview = document.getElementById('preview');
  const form = document.getElementById('create-form');

  input.addEventListener('change', () => {
    preview.innerHTML = '';
    const files = Array.from(input.files || []);
    files.forEach(f => {
      if (!ALLOWED.includes(f.type) || f.size > MAX_BYTES) return;
      const img = document.createElement('img');
      img.style.width = '140px';
      img.style.height = '110px';
      img.style.objectFit = 'cover';
      img.style.border = '1px solid #1f2937';
      img.style.borderRadius = '8px';
      img.alt = f.name;
      const reader = new FileReader();
      reader.onload = e => { img.src = e.target.result; };
      reader.readAsDataURL(f);
      preview.appendChild(img);
    });
  });

  form.addEventListener('submit', (e) => {
    const files = Array.from(input.files || []);
    for (const f of files) {
      if (!ALLOWED.includes(f.type)) {
        alert('มีไฟล์ที่ชนิดไม่รองรับ (รองรับ: JPG, PNG, GIF, WEBP)');
        e.preventDefault();
        return;
      }
      if (f.size > MAX_BYTES) {
        alert('มีไฟล์ที่ใหญ่เกิน 10MB');
        e.preventDefault();
        return;
      }
    }
  });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
