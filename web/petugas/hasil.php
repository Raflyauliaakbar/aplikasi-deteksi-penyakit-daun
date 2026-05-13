<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Petugas');

$u   = current_user();
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('petugas/riwayat.php');
}

// Hanya pemilik yang bisa akses
$stmt = $pdo->prepare('
    SELECT h.*, p.nama_penyakit, p.deskripsi, p.solusi
    FROM history_deteksi h
    JOIN penyakit p ON h.id_penyakit = p.id_penyakit
    WHERE h.id_deteksi = ? AND h.id_user = ?
    LIMIT 1
');
$stmt->execute([$id, $u['id_user']]);
$row = $stmt->fetch();
if (!$row) {
    flash('error', 'Data hasil tidak ditemukan.');
    redirect('petugas/riwayat.php');
}

$page_title  = 'Hasil Deteksi';
$active_menu = 'deteksi';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-3 no-print">
  <a href="<?= e(url('petugas/deteksi.php')) ?>" class="btn btn-primary">
    <i class="bi bi-camera"></i> Deteksi Baru
  </a>
  <a href="<?= e(url('petugas/unduh_pdf.php?id=' . $id)) ?>" class="btn btn-outline-primary" target="_blank">
    <i class="bi bi-file-earmark-pdf"></i> Unduh PDF
  </a>
  <a href="<?= e(url('petugas/riwayat.php')) ?>" class="btn btn-light">
    <i class="bi bi-clock-history"></i> Riwayat
  </a>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-image"></i> Citra Hasil Deteksi</div>
      <div class="card-body text-center">
        <img src="<?= e(UPLOAD_URL_DETECTED . rawurlencode($row['nama_file'])) ?>"
             alt="Hasil Deteksi" class="preview-image">
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-clipboard-check"></i> Ringkasan Hasil</div>
      <div class="card-body">
        <div class="text-center mb-3">
          <div class="mb-1 text-muted">Penyakit Terdeteksi</div>
          <h2 class="mb-2"><?= penyakit_badge($row['nama_penyakit']) ?></h2>
          <div class="text-muted">Tingkat Keyakinan Model</div>
          <div class="display-5 fw-bold text-success"><?= e(format_akurasi((float) $row['akurasi'])) ?></div>
        </div>

        <hr>

        <dl class="row small mb-0">
          <dt class="col-5">ID Deteksi</dt>
          <dd class="col-7">#<?= (int) $row['id_deteksi'] ?></dd>

          <dt class="col-5">Waktu</dt>
          <dd class="col-7"><?= e(format_tgl($row['tgl_deteksi'])) ?></dd>

          <dt class="col-5">Petugas</dt>
          <dd class="col-7"><?= e($u['nama_lengkap']) ?></dd>

          <dt class="col-5">File</dt>
          <dd class="col-7"><small><code><?= e($row['nama_file']) ?></code></small></dd>
        </dl>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header bg-light">
        <i class="bi bi-info-circle text-primary"></i>
        <strong>Deskripsi Gejala</strong>
      </div>
      <div class="card-body">
        <p style="white-space: pre-line;" class="mb-0"><?= e($row['deskripsi']) ?></p>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card h-100 border-success">
      <div class="card-header bg-success text-white">
        <i class="bi bi-clipboard2-pulse-fill"></i>
        <strong>Solusi &amp; Penanganan</strong>
      </div>
      <div class="card-body">
        <p style="white-space: pre-line;" class="mb-0"><?= e($row['solusi']) ?></p>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
