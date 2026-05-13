<?php
/**
 * Laporan PDF via print-to-PDF browser.
 * Halaman ini otomatis memicu dialog cetak → pengguna pilih "Simpan sebagai PDF".
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Petugas');

$u   = current_user();
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
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
    http_response_code(404);
    die('Data tidak ditemukan.');
}

$judul  = 'Laporan Deteksi #' . $id;
$img_url = UPLOAD_URL_DETECTED . rawurlencode($row['nama_file']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= e($judul) ?></title>
  <style>
    @page { size: A4; margin: 14mm; }
    * { box-sizing: border-box; }
    body {
      font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
      color: #222;
      font-size: 12pt;
      margin: 0;
      background: #fff;
    }
    .header {
      border-bottom: 3px solid #2e7d32;
      padding-bottom: 10px;
      margin-bottom: 18px;
    }
    .header h1 {
      color: #2e7d32;
      margin: 0;
      font-size: 18pt;
    }
    .header .sub { color: #666; font-size: 10pt; margin-top: 4px; }
    table.meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 16px;
      font-size: 11pt;
    }
    table.meta td {
      padding: 5px 8px;
      border: 1px solid #ddd;
      vertical-align: top;
    }
    table.meta td.label {
      background: #f1f8e9;
      font-weight: bold;
      width: 35%;
    }
    .foto {
      text-align: center;
      margin: 16px 0;
      page-break-inside: avoid;
    }
    .foto img {
      max-width: 100%;
      max-height: 320px;
      border: 1px solid #ccc;
    }
    h2.section {
      color: #2e7d32;
      font-size: 13pt;
      border-bottom: 1px solid #2e7d32;
      padding-bottom: 4px;
      margin-top: 18px;
    }
    .content {
      white-space: pre-line;
      text-align: justify;
    }
    .footer {
      margin-top: 26px;
      padding-top: 10px;
      border-top: 1px solid #ccc;
      font-size: 9pt;
      color: #777;
      text-align: center;
    }
    .no-print {
      background: #fffde7;
      padding: 14px;
      border: 1px solid #ffeb3b;
      margin-bottom: 20px;
      border-radius: 8px;
      text-align: center;
    }
    .no-print button {
      padding: 10px 22px;
      background: #2e7d32;
      color: #fff;
      border: 0;
      font-size: 14px;
      border-radius: 6px;
      cursor: pointer;
    }
    @media print {
      .no-print { display: none; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <strong>Cetak / Simpan sebagai PDF</strong><br>
  Dialog cetak akan terbuka otomatis. Pilih destinasi <em>"Simpan sebagai PDF"</em>.
  <br><br>
  <button onclick="window.print()">Cetak Sekarang</button>
</div>

<div class="header">
  <h1>Laporan Hasil Deteksi Penyakit Daun Jeruk</h1>
  <div class="sub"><?= e(APP_NAME) ?> &middot; Dicetak <?= date('d/m/Y H:i') ?></div>
</div>

<table class="meta">
  <tr>
    <td class="label">ID Deteksi</td>
    <td>#<?= (int) $row['id_deteksi'] ?></td>
  </tr>
  <tr>
    <td class="label">Tanggal Deteksi</td>
    <td><?= e(format_tgl($row['tgl_deteksi'])) ?></td>
  </tr>
  <tr>
    <td class="label">Petugas</td>
    <td><?= e($u['nama_lengkap']) ?> (@<?= e($u['username']) ?>)</td>
  </tr>
  <tr>
    <td class="label">Hasil Deteksi</td>
    <td><strong style="color:#2e7d32;"><?= e($row['nama_penyakit']) ?></strong></td>
  </tr>
  <tr>
    <td class="label">Tingkat Keyakinan Model</td>
    <td><strong><?= e(format_akurasi((float) $row['akurasi'])) ?></strong></td>
  </tr>
  <tr>
    <td class="label">File Citra</td>
    <td><?= e($row['nama_file']) ?></td>
  </tr>
</table>

<div class="foto">
  <img src="<?= e($img_url) ?>" alt="Citra hasil deteksi">
  <div style="font-size:10pt;color:#666;margin-top:6px;">Citra dengan bounding box hasil deteksi</div>
</div>

<h2 class="section">Deskripsi Gejala</h2>
<div class="content"><?= e($row['deskripsi']) ?></div>

<h2 class="section">Solusi &amp; Penanganan</h2>
<div class="content"><?= e($row['solusi']) ?></div>

<div class="footer">
  Laporan ini dihasilkan secara otomatis oleh sistem <?= e(APP_NAME) ?>.
</div>

<script>
  // Trigger print otomatis setelah gambar selesai dimuat
  window.addEventListener('load', function () {
    const img = document.querySelector('.foto img');
    if (img && !img.complete) {
      img.addEventListener('load',  () => setTimeout(() => window.print(), 300));
      img.addEventListener('error', () => setTimeout(() => window.print(), 300));
    } else {
      setTimeout(() => window.print(), 400);
    }
  });
</script>
</body>
</html>
