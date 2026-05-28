<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_login();
$u=current_user();$pdo=db();
$id=(int)($_GET['id']??0);
$stmt=$pdo->prepare('SELECT h.*,p.nama_penyakit,p.deskripsi,p.solusi FROM history_deteksi h JOIN penyakit p ON h.id_penyakit=p.id_penyakit WHERE h.id_deteksi=? AND h.id_user=?');
$stmt->execute([$id,$u['id_user']]);$row=$stmt->fetch();
if(!$row){flash('error','Data tidak ditemukan.');redirect('shared/riwayat.php');}
$page_title='Hasil Deteksi';$active_menu='deteksi';
require __DIR__.'/../includes/header.php';
?>
<div class="mb-3 d-flex flex-wrap gap-2">
<a href="<?=e(url('shared/deteksi.php'))?>" class="btn btn-primary"><i class="bi bi-camera"></i> Deteksi Baru</a>
<a href="<?=e(url('shared/cetak_hasil.php?id='.$id))?>" target="_blank" class="btn btn-outline-danger"><i class="bi bi-file-pdf"></i> PDF</a>
<a href="<?=e(url('shared/riwayat.php'))?>" class="btn btn-light"><i class="bi bi-clock-history"></i> Riwayat</a></div>
<div class="row g-3"><div class="col-lg-6"><div class="card h-100"><div class="card-header fw-semibold"><i class="bi bi-image"></i> Citra Hasil</div><div class="card-body text-center"><img src="<?=e(url('uploads/detected/'.rawurlencode($row['nama_file'])))?>" class="img-fluid rounded" style="max-height:400px"></div></div></div>
<div class="col-lg-6"><div class="card h-100"><div class="card-header fw-semibold"><i class="bi bi-clipboard-check"></i> Ringkasan</div><div class="card-body text-center"><p class="text-muted mb-1 small">Penyakit</p><h3><?=penyakit_badge($row['nama_penyakit'])?></h3><p class="text-muted mb-1 small mt-3">Keyakinan</p><h2 class="text-success fw-bold"><?=e(format_akurasi((float)$row['akurasi']))?></h2><hr><dl class="row text-start small mb-0"><dt class="col-5">ID</dt><dd class="col-7">#<?=$row['id_deteksi']?></dd><dt class="col-5">Waktu</dt><dd class="col-7"><?=e(format_tgl($row['tgl_deteksi']))?></dd><dt class="col-5">Operator</dt><dd class="col-7"><?=e($u['nama_lengkap'])?></dd></dl></div></div></div></div>
<div class="row g-3 mt-1"><div class="col-md-6"><div class="card h-100"><div class="card-header bg-light fw-semibold"><i class="bi bi-info-circle text-primary"></i> Deskripsi</div><div class="card-body"><p class="mb-0" style="white-space:pre-line"><?=e($row['deskripsi'])?></p></div></div></div>
<div class="col-md-6"><div class="card h-100 border-success"><div class="card-header bg-success text-white fw-semibold"><i class="bi bi-clipboard2-pulse-fill"></i> Solusi</div><div class="card-body"><p class="mb-0" style="white-space:pre-line"><?=e($row['solusi'])?></p></div></div></div></div>
<?php require __DIR__.'/../includes/footer.php';?>
