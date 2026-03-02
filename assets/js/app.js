// assets/js/app.js — ฟังก์ชันด้านหน้าเว็บ (รองรับรูปโปรไฟล์ + พรีวิว)

/**
 * ปรับ path ให้ตรงกับโปรเจ็กต์ของคุณ
 * ใช้เป็นรูปโปรไฟล์สำรอง (Account Circle สีเทา)
 */
const AVATAR_FALLBACK = '/lostfound/assets/img/account_circle.png';

/* ------------------------------
 * ตรวจไฟล์ภาพเบื้องต้นฝั่ง client
 * ------------------------------ */
function validateImageFile(file) {
  if (!file) return { ok: true };
  const max = 2 * 1024 * 1024; // 2MB
  if (file.size > max) return { ok: false, msg: 'ไฟล์ใหญ่เกิน 2MB' };
  const okTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!okTypes.includes(file.type)) return { ok: false, msg: 'ชนิดไฟล์ไม่รองรับ (อนุญาต: jpg, png, gif, webp)' };
  return { ok: true };
}

/* ------------------------------
 * พรีวิวภาพจาก input[type=file]
 * ------------------------------ */
function previewTo(imgEl, file) {
  if (!imgEl || !file) return;
  const reader = new FileReader();
  reader.onload = () => { imgEl.src = reader.result; };
  reader.readAsDataURL(file);
}

/**
 * ใช้งานแบบเดิมได้: onchange="previewImage(event)"
 * จะพยายามพรีวิวลง #preview หรือ selector จาก data-preview
 */
function previewImage(e){
  const input = e.target;
  const file = input.files && input.files[0];
  if (!file) return;

  const v = validateImageFile(file);
  if (!v.ok) { alert(v.msg); input.value = ''; return; }

  const sel = input.getAttribute('data-preview') || '#preview';
  const img = document.querySelector(sel);
  if (img) previewTo(img, file);
}

/* ------------------------------
 * ผูกอัตโนมัติให้ทุก input[data-preview]
 * ------------------------------ */
function bindImagePreviewers(){
  document.querySelectorAll('input[type="file"][data-preview]').forEach(inp => {
    inp.addEventListener('change', previewImage);
  });
}

/* ------------------------------
 * Fallback ให้ avatar ถ้ารูปโหลดไม่ได้
 * ------------------------------ */
function attachAvatarFallback(){
  document.querySelectorAll('img.avatar, img.avatar-lg').forEach(img => {
    img.addEventListener('error', () => {
      if (img.src !== AVATAR_FALLBACK) img.src = AVATAR_FALLBACK;
    }, { once: true });
  });
}

/* ------------------------------
 * กรณี src="" (ค่าว่าง) onerror จะไม่ยิง
 * บังคับใส่ fallback ให้เลย
 * ------------------------------ */
function fixEmptyAvatars(){
  document.querySelectorAll('img.avatar, img.avatar-lg').forEach(img => {
    const src = (img.getAttribute('src') || '').trim();
    if (!src) img.setAttribute('src', AVATAR_FALLBACK);
  });
}

/* ------------------------------
 * Fallback ให้ภาพประกาศ thumb ถ้า src ว่าง
 * (เผื่อบางหน้าอยากใช้ข้อความแทนภาพ)
 * ------------------------------ */
function fixEmptyPostThumbs(){
  document.querySelectorAll('.card .thumb > img, .thumb-lg > img').forEach(img => {
    const src = (img.getAttribute('src') || '').trim();
    if (!src) {
      // ถ้าอยากใช้ placeholder image สามารถกำหนดได้ที่นี่
      // ตอนนี้ปล่อยว่างไว้เพราะใน PHP เราใส่ <div class="thumb-empty"> แทนแล้ว
    }
  });
}

/* ------------------------------
 * Init
 * ------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
  bindImagePreviewers();
  attachAvatarFallback();
  fixEmptyAvatars();
  fixEmptyPostThumbs();
});

// เผื่อไฟล์อื่นเรียกใช้ได้
window.previewImage = previewImage;
