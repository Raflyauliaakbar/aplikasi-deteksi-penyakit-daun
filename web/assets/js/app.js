/* App interactions: sidebar toggle, upload preview, camera capture */

(function () {
  // --- Sidebar toggle (mobile) ---
  const toggleBtn = document.querySelector('.btn-toggle-sidebar');
  const sidebar   = document.querySelector('.sidebar');
  let backdrop    = document.querySelector('.sidebar-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
  }
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });
    backdrop.addEventListener('click', () => {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // --- Upload preview ---
  const fileInput = document.getElementById('file-citra');
  const previewEl = document.getElementById('preview-citra');
  const uploadZone = document.getElementById('upload-zone');
  const previewBox = document.getElementById('preview-box');
  const submitBtn = document.getElementById('btn-submit-deteksi');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      const f = this.files && this.files[0];
      if (!f) return;
      if (f.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5 MB.');
        this.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = (e) => {
        if (previewEl) previewEl.src = e.target.result;
        if (previewBox) previewBox.classList.remove('d-none');
        if (uploadZone) uploadZone.classList.add('d-none');
        if (submitBtn) submitBtn.disabled = false;
      };
      reader.readAsDataURL(f);
    });
  }

  const btnReset = document.getElementById('btn-reset-upload');
  if (btnReset) {
    btnReset.addEventListener('click', () => {
      if (fileInput) fileInput.value = '';
      if (previewBox) previewBox.classList.add('d-none');
      if (uploadZone) uploadZone.classList.remove('d-none');
      if (submitBtn) submitBtn.disabled = true;
    });
  }

  // Klik zona upload membuka dialog file
  if (uploadZone && fileInput) {
    uploadZone.addEventListener('click', (e) => {
      if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
        fileInput.click();
      }
    });
  }

  // --- Loading state saat submit deteksi ---
  const form = document.getElementById('form-deteksi');
  if (form) {
    form.addEventListener('submit', () => {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Menganalisis citra...';
      }
    });
  }
})();
