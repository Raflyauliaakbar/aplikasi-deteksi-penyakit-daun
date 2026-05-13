<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Admin');

$pdo = db();

// --- Filter ---------------------------------------------------------------
$f_petugas  = trim($_GET['petugas']  ?? '');
$f_penyakit = (int) ($_GET['penyakit'] ?? 0);
$f_dari     = trim($_GET['dari']     ?? '');
$f_sampai   = trim($_GET['sampai']   ?? '');

$where  = [];
$params = [];

if ($f_petugas !== '') {
    $where[]    = '(u.username LIKE ? OR u.nama_lengkap LIKE ?)';
    $params[]   = '%' . $f_petugas . '%';
    $params[]   = '%' . $f_petugas . '%';
}
if ($f_penyakit > 0) {
    $where[]    = 'h.id_penyakit = ?';
    $params[]   = $f_penyakit;
}
if ($f_dari !== '') {
    $where[]    = 'DATE(h.tgl_deteksi) >= ?';
    $params[]   = $f_dari;
}
if ($f_sampai !== '') {
    $where[]    = 'DATE(h.tgl_deteksi) <= ?';
    $params[]   = $f_sampai;
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT h.*, u.username, u.nama_lengkap, p.nama_penyakit
    FROM history_deteksi h
    JOIN users u    ON h.id_user = u.id_user
    JOIN penyakit p ON h.id_penyakit = p.id_penyakit
    $where_sql
    ORDER BY h.tgl_deteksi DESC
    LIMIT 500
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$penyakit_list = $pdo->query('SELECT id_penyakit, nama_penyakit FROM penyakit ORDER BY nama_penyakit')->fetchAll();

$page_title  = 'Monitor Riwayat Deteksi';
$active_menu = 'monitor';
require __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-funnel"></i> Filter</div>
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <label class="form-label small mb-1">Cari Petugas</label>
        <input type="text" name="petugas" class="form-control form-control-sm"
               placeholder="Username / nama" value="<?= e($f_petugas) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Penyakit</label>
        <select name="penyakit" class="form-select form-select-sm">
          <option value="0">Semua</option>
          <?php foreach ($penyakit_list as $p): ?>
            <option value="<?= (int) $p['id_penyakit'] ?>" <?= $f_penyakit === (int) $p['id_penyakit'] ? 'selected' : '' ?>>
              <?= e($p['nama_penyakit']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Dari</label>
        <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($f_dari) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Sampai</label>
        <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($f_sampai) ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end gap-2">
        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
        <a href="<?= e(url('admin/monitor_riwayat.php')) ?>" class="btn btn-sm btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-clipboard-data"></i> Hasil (<?= count($rows) ?> baris)</span>
    <small class="text-muted">Menampilkan maks 500 baris terbaru</small>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Petugas</th>
          <th>Hasil</th>
          <th class="text-end">Akurasi</th>
          <th>Citra</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data sesuai filter.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?= e(format_tgl($r['tgl_deteksi'])) ?></td>
            <td>
              <div><?= e($r['nama_lengkap']) ?></div>
              <small class="text-muted">@<?= e($r['username']) ?></small>
            </td>
            <td><?= penyakit_badge($r['nama_penyakit']) ?></td>
            <td class="text-end fw-semibold"><?= e(format_akurasi((float) $r['akurasi'])) ?></td>
            <td>
              <a href="<?= e(UPLOAD_URL_DETECTED . rawurlencode($r['nama_file'])) ?>"
                 target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-image"></i> Lihat
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
