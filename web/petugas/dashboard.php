<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_role('Petugas');
$u=current_user();$pdo=db();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM history_deteksi WHERE id_user=?');$stmt->execute([$u['id_user']]);$total=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT COUNT(*) FROM history_deteksi WHERE id_user=? AND MONTH(tgl_deteksi)=MONTH(CURDATE()) AND YEAR(tgl_deteksi)=YEAR(CURDATE())");$stmt->execute([$u['id_user']]);$bulan_ini=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT h.*,p.nama_penyakit FROM history_deteksi h JOIN penyakit p ON h.id_penyakit=p.id_penyakit WHERE h.id_user=? ORDER BY h.tgl_deteksi DESC LIMIT 5");$stmt->execute([$u['id_user']]);$recent=$stmt->fetchAll();
$page_title='Dashboard';$active_menu='dashboard';
require __DIR__.'/../includes/header.php';
?>
<div class="card mb-4 border-start border-4 border-success"><div class="card-body"><h5 class="mb-1">Halo, <?=e($u['nama_lengkap'])?>!</h5><p class="text-muted mb-0">Gunakan fitur deteksi untuk mengidentifikasi penyakit daun jeruk.</p></div></div>
<div class="row g-3 mb-4"><div class="col-6"><div class="stat-card bg-gradient-green"><div class="stat-icon"><i class="bi bi-camera-fill"></i></div><div class="stat-value"><?=$total?></div><div class="stat-label">Total Deteksi</div></div></div><div class="col-6"><div class="stat-card bg-gradient-orange"><div class="stat-icon"><i class="bi bi-calendar-check"></i></div><div class="stat-value"><?=$bulan_ini?></div><div class="stat-label">Bulan Ini</div></div></div></div>
<div class="row g-3 mb-4"><div class="col-6"><a href="<?=e(url('shared/deteksi.php'))?>" class="btn btn-primary btn-lg w-100 py-3"><i class="bi bi-camera fs-3 d-block mb-1"></i> Deteksi</a></div><div class="col-6"><a href="<?=e(url('shared/riwayat.php'))?>" class="btn btn-outline-primary btn-lg w-100 py-3"><i class="bi bi-clock-history fs-3 d-block mb-1"></i> Riwayat</a></div></div>
<div class="card"><div class="card-header fw-semibold"><i class="bi bi-clock-history"></i> 5 Terakhir</div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Waktu</th><th>Hasil</th><th class="text-end">Akurasi</th></tr></thead><tbody>
<?php if(!$recent):?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada riwayat.</td></tr>
<?php else:foreach($recent as $r):?><tr><td class="small"><?=e(format_tgl($r['tgl_deteksi']))?></td><td><?=penyakit_badge($r['nama_penyakit'])?></td><td class="text-end fw-semibold"><?=e(format_akurasi((float)$r['akurasi']))?></td></tr><?php endforeach;endif;?></tbody></table></div></div>
<?php require __DIR__.'/../includes/footer.php';?>
