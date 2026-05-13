<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// Admin & Petugas sama-sama memiliki halaman riwayat pribadi.
require_login();

$u   = current_user();
$pdo = db();

// Filter penyakit
$f_penyakit = (int) ($_GET['penyakit'] ?? 0);
$params     = [$u['id_user']];
$extra      = '';
if ($f_penyakit > 0) {
    $extra    = ' AND h.id_penyakit = ?';
    $params[] = $f_penyakit;
}

$stmt = $pdo->prepare("
    SELECT h.*, p.nama_penyakit
    FROM history_deteksi h
    JOIN penyakit p ON h.id_penyakit = p.id_penyakit
    WHERE h.id_user = ? $extra
    ORDER BY h.tgl_deteksi DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$penyakit_list = $pdo->query('SELECT id_penyakit, nama_penyakit FROM penyakit ORDER BY nama_penyakit')->fetchAll();

$page_title  = 'Riwayat Deteksi Saya';
$active_menu = 'riwayat';
require __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label small mb-1">Filter Penyakit</label>
        <select name="penyakit" class="form-select" onchange="this.form.submit()">
          <option value="0">Semua Penyakit</option>
          <?php foreach ($penyakit_list as $p): ?>
            <option value="<?= (int) $p['id_penyakit'] ?>"
              <?= $f_penyakit === (int) $p['id_penyakit'] ? 'selected' : '' ?>>
              <?= e($p['nama_penyakit']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <a href="<?= e(url('petugas/deteksi.php')) ?>" class="btn btn-primary w-100">
          <i class="bi bi-camera"></i> Deteksi Baru
        </a>
      </div>
    </form>
  </div>
</div>

<?php if (!$rows): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="bi bi-inbox display-4 text-muted"></i>
      <h5 class="mt-3">Belum ada riwayat deteksi</h5>
      <p class="text-muted">Mulai deteksi pertama Anda dengan mengunggah foto daun jeruk.</p>
      <a href="<?= e(url('petugas/deteksi.php')) ?>" class="btn btn-primary">
        <i class="bi bi-camera"></i> Mulai Deteksi
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($rows as $r): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100">
          <a href="<?= e(url('petugas/hasil.php?id=' . (int) $r['id_deteksi'])) ?>">
            <img src="<?= e(UPLOAD_URL_DETECTED . rawurlencode($r['nama_file'])) ?>"
                 alt="Citra" class="card-img-top"
                 style="height: 180px; object-fit: cover;">
          </a>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <?= penyakit_badge($r['nama_penyakit']) ?>
              <span class="fw-semibold text-success small"><?= e(format_akurasi((float) $r['akurasi'])) ?></span>
            </div>
            <div class="small text-muted mb-2">
              <i class="bi bi-clock"></i> <?= e(format_tgl($r['tgl_deteksi'])) ?>
            </div>
            <div class="d-flex gap-2">
              <a href="<?= e(url('petugas/hasil.php?id=' . (int) $r['id_deteksi'])) ?>"
                 class="btn btn-sm btn-primary flex-fill">
                <i class="bi bi-eye"></i> Detail
              </a>
              <a href="<?= e(url('petugas/unduh_pdf.php?id=' . (int) $r['id_deteksi'])) ?>"
                 target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-file-earmark-pdf"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
