<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Petugas');

$u   = current_user();
$pdo = db();

// Handle upload (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $file = $_FILES['citra'] ?? null;

    // --- Validasi file --------------------------------------------------
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        flash('error', 'Gagal mengunggah file. Silakan coba lagi.');
        redirect('petugas/deteksi.php');
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        flash('error', 'Ukuran file melebihi batas 5 MB.');
        redirect('petugas/deteksi.php');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: '';
    if (!in_array($mime, ALLOWED_MIME, true)) {
        flash('error', 'Format file harus JPG atau PNG.');
        redirect('petugas/deteksi.php');
    }

    // --- Simpan file asli -----------------------------------------------
    $new_name = safe_filename($file['name']);
    $dest_ori = UPLOAD_DIR_ORIGINAL . $new_name;
    if (!move_uploaded_file($file['tmp_name'], $dest_ori)) {
        flash('error', 'Gagal menyimpan file.');
        redirect('petugas/deteksi.php');
    }

    // --- Kirim ke Flask API (multipart/form-data) -----------------------
    $ch = curl_init(FLASK_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => FLASK_TIMEOUT,
        CURLOPT_POSTFIELDS     => [
            'image' => new CURLFile($dest_ori, $mime, $new_name),
        ],
    ]);
    $resp     = curl_exec($ch);
    $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err || $http !== 200 || !$resp) {
        @unlink($dest_ori);
        flash('error', 'Gagal menghubungi AI Server (Flask). ' . e($err ?: 'HTTP ' . $http));
        redirect('petugas/deteksi.php');
    }

    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['success'])) {
        @unlink($dest_ori);
        flash('error', 'Respon AI Server tidak valid: ' . e($data['message'] ?? 'unknown'));
        redirect('petugas/deteksi.php');
    }

    // --- Parse hasil ----------------------------------------------------
    $nama_penyakit = trim((string) ($data['label'] ?? ''));
    $akurasi       = (float) ($data['confidence'] ?? 0);
    $image_b64     = (string) ($data['image_base64'] ?? '');  // gambar hasil bounding box

    if ($nama_penyakit === '') {
        @unlink($dest_ori);
        flash('error', 'Model tidak mengembalikan label.');
        redirect('petugas/deteksi.php');
    }

    // --- Cari id_penyakit berdasarkan nama ------------------------------
    $stmt = $pdo->prepare('SELECT id_penyakit FROM penyakit WHERE nama_penyakit = ?');
    $stmt->execute([$nama_penyakit]);
    $id_penyakit = $stmt->fetchColumn();
    if (!$id_penyakit) {
        @unlink($dest_ori);
        flash('error', 'Label "' . e($nama_penyakit) . '" tidak terdaftar di database.');
        redirect('petugas/deteksi.php');
    }

    // --- Simpan gambar bounding box -------------------------------------
    // Jika Flask mengirim image_base64 → simpan; jika tidak, pakai file asli sebagai fallback.
    if ($image_b64 !== '') {
        // Bisa berupa "data:image/jpeg;base64,..." atau raw base64
        if (strpos($image_b64, 'base64,') !== false) {
            $image_b64 = substr($image_b64, strpos($image_b64, 'base64,') + 7);
        }
        $bin = base64_decode($image_b64, true);
        if ($bin !== false) {
            file_put_contents(UPLOAD_DIR_DETECTED . $new_name, $bin);
        } else {
            copy($dest_ori, UPLOAD_DIR_DETECTED . $new_name);
        }
    } else {
        copy($dest_ori, UPLOAD_DIR_DETECTED . $new_name);
    }

    // --- Insert history -------------------------------------------------
    $stmt = $pdo->prepare(
        'INSERT INTO history_deteksi (id_user, id_penyakit, nama_file, akurasi)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$u['id_user'], (int) $id_penyakit, $new_name, $akurasi]);
    $id_deteksi = (int) $pdo->lastInsertId();

    flash('success', 'Deteksi berhasil! Hasilnya ditampilkan di bawah.');
    redirect('petugas/hasil.php?id=' . $id_deteksi);
}

$page_title  = 'Deteksi Penyakit';
$active_menu = 'deteksi';
require __DIR__ . '/../includes/header.php';
?>

<div class="row">
  <div class="col-lg-8 mx-auto">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-camera"></i> Unggah Citra Daun Jeruk
      </div>
      <div class="card-body">

        <form method="post" enctype="multipart/form-data" id="form-deteksi" novalidate>
          <?= csrf_field() ?>

          <!-- Zona upload -->
          <div id="upload-zone" class="upload-zone mb-3">
            <i class="bi bi-cloud-arrow-up-fill"></i>
            <h5 class="mt-3">Klik atau pilih foto daun</h5>
            <p class="hint mb-3">Ambil dari kamera atau pilih dari galeri. Format JPG/PNG, maks 5 MB.</p>

            <div class="d-flex gap-2 justify-content-center flex-wrap">
              <label for="file-citra" class="btn btn-primary btn-lg">
                <i class="bi bi-camera-fill"></i> Kamera
              </label>
              <label for="file-citra-galeri" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-images"></i> Galeri
              </label>
            </div>
          </div>

          <!-- Input file: kamera (capture) -->
          <input type="file" id="file-citra" name="citra"
                 accept="image/jpeg,image/png" capture="environment"
                 class="d-none" required>
          <!-- Input file: galeri (tanpa capture) -->
          <input type="file" id="file-citra-galeri"
                 accept="image/jpeg,image/png"
                 class="d-none">

          <!-- Preview -->
          <div id="preview-box" class="d-none">
            <div class="text-center mb-3">
              <img id="preview-citra" class="preview-image" alt="Preview">
            </div>
            <div class="d-flex gap-2 justify-content-between">
              <button type="button" id="btn-reset-upload" class="btn btn-light">
                <i class="bi bi-arrow-counterclockwise"></i> Pilih Ulang
              </button>
              <button type="submit" id="btn-submit-deteksi" class="btn btn-primary btn-lg">
                <i class="bi bi-magic"></i> Analisis Sekarang
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header"><i class="bi bi-lightbulb"></i> Tips Foto yang Baik</div>
      <div class="card-body">
        <ul class="mb-0">
          <li>Pastikan daun terlihat jelas dan berada di tengah foto.</li>
          <li>Gunakan pencahayaan alami (hindari bayangan gelap).</li>
          <li>Fokuskan pada bagian yang menunjukkan gejala (bercak, kuning, jamur).</li>
          <li>Ambil foto dari jarak 20-30 cm untuk hasil terbaik.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  // Tombol "Galeri" → sinkronkan ke input utama tanpa capture
  document.getElementById('file-citra-galeri')?.addEventListener('change', function() {
    const main = document.getElementById('file-citra');
    if (this.files.length) {
      // Transfer file ke input utama lewat DataTransfer
      const dt = new DataTransfer();
      dt.items.add(this.files[0]);
      main.files = dt.files;
      main.dispatchEvent(new Event('change'));
    }
  });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
