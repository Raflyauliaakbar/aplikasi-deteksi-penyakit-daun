<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_role('Admin');
$pdo=db();
$f_bulan=trim($_GET['bulan']??'');$f_penyakit=(int)($_GET['penyakit']??0);
$where=[];$params=[];
if($f_bulan!==''){$where[]="DATE_FORMAT(h.tgl_deteksi,'%Y-%m')=?";$params[]=$f_bulan;}
if($f_penyakit>0){$where[]='h.id_penyakit=?';$params[]=$f_penyakit;}
$ws=$where?'WHERE '.implode(' AND ',$where):'';
$stmt=$pdo->prepare("SELECT h.*,u.nama_lengkap,p.nama_penyakit FROM history_deteksi h JOIN users u ON h.id_user=u.id_user JOIN penyakit p ON h.id_penyakit=p.id_penyakit $ws ORDER BY h.tgl_deteksi DESC LIMIT 500");
$stmt->execute($params);$rows=$stmt->fetchAll();
$plist=$pdo->query('SELECT * FROM penyakit ORDER BY nama_penyakit')->fetchAll();
$blist=$pdo->query("SELECT DISTINCT DATE_FORMAT(tgl_deteksi,'%Y-%m') AS bln FROM history_deteksi ORDER BY bln DESC")->fetchAll();
$page_title='Monitoring Riwayat';$active_menu='monitoring';
require __DIR__.'/../includes/header.php';
?>
<div class="card mb-3"><div class="card-body"><form method="get" class="row g-2 align-items-end">
<div class="col-md-4"><label class="form-label small mb-1">Bulan</label><select name="bulan" class="form-select form-select-sm"><option value="">Semua</option><?php foreach($blist as $b):?><option value="<?=e($b['bln'])?>" <?=$f_bulan===$b['bln']?'selected':''?>><?=e($b['bln'])?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label small mb-1">Penyakit</label><select name="penyakit" class="form-select form-select-sm"><option value="0">Semua</option><?php foreach($plist as $p):?><option value="<?=(int)$p['id_penyakit']?>" <?=$f_penyakit===(int)$p['id_penyakit']?'selected':''?>><?=e($p['nama_penyakit'])?></option><?php endforeach;?></select></div>
<div class="col-md-4 d-flex gap-2"><button class="btn btn-sm btn-primary flex-fill"><i class="bi bi-search"></i> Filter</button><a href="<?=e(url('admin/monitoring.php'))?>" class="btn btn-sm btn-light"><i class="bi bi-x-lg"></i></a>
<?php if($f_bulan!==''):?><a href="<?=e(url('admin/cetak_pdf.php?bulan='.urlencode($f_bulan).'&penyakit='.$f_penyakit))?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-pdf"></i> PDF</a><?php endif;?></div>
</form></div></div>
<div class="card"><div class="card-header fw-semibold"><i class="bi bi-clipboard-data"></i> Hasil (<?=count($rows)?>)</div><div class="table-responsive"><table class="table table-hover table-sm align-middle mb-0"><thead><tr><th>Waktu</th><th>Petugas</th><th>Hasil</th><th class="text-end">Akurasi</th><th>Citra</th></tr></thead><tbody>
<?php if(!$rows):?><tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td></tr>
<?php else:foreach($rows as $r):?><tr><td class="small"><?=e(format_tgl($r['tgl_deteksi']))?></td><td><?=e($r['nama_lengkap'])?></td><td><?=penyakit_badge($r['nama_penyakit'])?></td><td class="text-end fw-semibold"><?=e(format_akurasi((float)$r['akurasi']))?></td><td><a href="<?=e(url('uploads/detected/'.rawurlencode($r['nama_file'])))?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-image"></i></a></td></tr>
<?php endforeach;endif;?></tbody></table></div></div>
<?php require __DIR__.'/../includes/footer.php';?>
