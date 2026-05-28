<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_login();
$u=current_user();$pdo=db();
$fp=(int)($_GET['penyakit']??0);$params=[$u['id_user']];$ex='';
if($fp>0){$ex=' AND h.id_penyakit=?';$params[]=$fp;}
$stmt=$pdo->prepare("SELECT h.*,p.nama_penyakit FROM history_deteksi h JOIN penyakit p ON h.id_penyakit=p.id_penyakit WHERE h.id_user=? $ex ORDER BY h.tgl_deteksi DESC");
$stmt->execute($params);$rows=$stmt->fetchAll();
$plist=$pdo->query('SELECT * FROM penyakit ORDER BY nama_penyakit')->fetchAll();
$page_title='Riwayat Saya';$active_menu='riwayat';
require __DIR__.'/../includes/header.php';
?>
<div class="card mb-3"><div class="card-body"><form method="get" class="row g-2 align-items-end"><div class="col-md-5"><select name="penyakit" class="form-select" onchange="this.form.submit()"><option value="0">Semua</option><?php foreach($plist as $p):?><option value="<?=(int)$p['id_penyakit']?>" <?=$fp===(int)$p['id_penyakit']?'selected':''?>><?=e($p['nama_penyakit'])?></option><?php endforeach;?></select></div><div class="col-md-3"><a href="<?=e(url('shared/deteksi.php'))?>" class="btn btn-primary w-100"><i class="bi bi-camera"></i> Deteksi Baru</a></div></form></div></div>
<?php if(!$rows):?><div class="card"><div class="card-body text-center py-5"><i class="bi bi-inbox display-4 text-muted"></i><h5 class="mt-3">Belum ada riwayat</h5><a href="<?=e(url('shared/deteksi.php'))?>" class="btn btn-primary mt-2">Mulai Deteksi</a></div></div>
<?php else:?><div class="row g-3"><?php foreach($rows as $r):?><div class="col-6 col-lg-4"><div class="card h-100"><a href="<?=e(url('shared/hasil.php?id='.(int)$r['id_deteksi']))?>"><img src="<?=e(url('uploads/detected/'.rawurlencode($r['nama_file'])))?>" class="card-img-top" style="height:150px;object-fit:cover"></a><div class="card-body p-2"><div class="d-flex justify-content-between mb-1"><?=penyakit_badge($r['nama_penyakit'])?><small class="fw-semibold text-success"><?=e(format_akurasi((float)$r['akurasi']))?></small></div><small class="text-muted"><i class="bi bi-clock"></i> <?=e(format_tgl($r['tgl_deteksi']))?></small><div class="d-flex gap-1 mt-2"><a href="<?=e(url('shared/hasil.php?id='.(int)$r['id_deteksi']))?>" class="btn btn-sm btn-primary flex-fill">Detail</a><a href="<?=e(url('shared/cetak_hasil.php?id='.(int)$r['id_deteksi']))?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-pdf"></i></a></div></div></div></div><?php endforeach;?></div><?php endif;?>
<?php require __DIR__.'/../includes/footer.php';?>
