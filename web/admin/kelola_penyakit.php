<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Admin');

$pdo = db();

// --- Aksi UPDATE deskripsi/solusi ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    csrf_verify();
    $id        = (int) ($_POST['id_penyakit'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $solusi    = trim($_POST['solusi'] ?? '');

    if ($id <= 0 || $deskripsi === '' || $solusi === '') {
        flash('error', 'Deskripsi dan solusi wajib diisi.');
    } else {
        $stmt = $pdo->prepare('UPDATE penyakit SET deskripsi = ?, solusi = ? WHERE id_penyakit = ?');
        $stmt->execute([$deskripsi, $solusi, $id]);
        flash('success', 'Informasi penyakit berhasil diperbarui.');
    }
    redirect('admin/kelola_penyakit.php');
}

$list = $pdo->query('SELECT * FROM penyakit ORDER BY id_penyakit')->fetchAll();

$page_title  = 'Kelola Informasi Penyakit';
$active_menu = 'penyakit';
require __DIR__ . '/../includes/header.php';
?>

<div class="alert alert-info d-flex align-items-center" role="alert">
  <i class="bi bi-info-circle me-2 fs-5"></i>
  <div>
    Nama 6 kelas penyakit bersifat tetap. Anda dapat memperbarui <strong>deskripsi</strong> dan <strong>solusi</strong>
    untuk meningkatkan informasi yang ditampilkan kepada petugas.
  </div>
</div>

<div class="accordion" id="accPenyakit">
  <?php foreach ($list as $i => $p): ?>
    <div class="accordion-item">
      <h2 class="accordion-header">
        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#p<?= (int) $p['id_penyakit'] ?>">
          <?= penyakit_badge($p['nama_penyakit']) ?>
          <span class="ms-2 fw-semibold"><?= e($p['nama_penyakit']) ?></span>
        </button>
      </h2>
      <div id="p<?= (int) $p['id_penyakit'] ?>"
           class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
           data-bs-parent="#accPenyakit">
        <div class="accordion-body">
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id_penyakit" value="<?= (int) $p['id_penyakit'] ?>">

            <div class="mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-file-text"></i> Deskripsi Gejala
              </label>
              <textarea name="deskripsi" class="form-control" rows="4" required><?= e($p['deskripsi']) ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-clipboard2-pulse"></i> Solusi / Penanganan
              </label>
              <textarea name="solusi" class="form-control" rows="6" required><?= e($p['solusi']) ?></textarea>
              <div class="form-text">Gunakan baris baru untuk tiap langkah agar mudah dibaca petugas.</div>
            </div>

            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Simpan Perubahan
            </button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
