<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Admin');
$pdo = db();
$total_petugas = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='Petugas'")->fetchColumn();
$total_deteksi = (int)$pdo->query("SELECT COUNT(*) FROM history_deteksi")->fetchColumn();
$deteksi_bulan = (int)$pdo->query("SELECT COUNT(*) FROM history_deteksi WHERE MONTH(tgl_deteksi)=MONTH(CURDATE()) AND YEAR(tgl_deteksi)=YEAR(CURDATE())")->fetchColumn();
$total_penyakit = (int)$pdo->query("SELECT COUNT(*) FROM penyakit")->fetchColumn();
$chart_bulan = $pdo->query("SELECT DATE_FORMAT(tgl_deteksi,'%Y-%m') AS bulan, COUNT(*) AS jml FROM history_deteksi WHERE tgl_deteksi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY bulan ORDER BY bulan")->fetchAll();
$chart_penyakit = $pdo->query("SELECT p.nama_penyakit, COUNT(h.id_deteksi) AS jml FROM penyakit p LEFT JOIN history_deteksi h ON h.id_penyakit=p.id_penyakit GROUP BY p.id_penyakit ORDER BY jml DESC")->fetchAll();
$recent = $pdo->query("SELECT h.*,u.nama_lengkap,p.nama_penyakit FROM history_deteksi h JOIN users u ON h.id_user=u.id_user JOIN penyakit p ON h.id_penyakit=p.id_penyakit ORDER BY h.tgl_deteksi DESC LIMIT 5")->fetchAll();
$page_title='Dashboard Admin'; $active_menu='dashboard';
require __DIR__.'/../includes/header.php';
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card bg-gradient-green"><div class="stat-icon"><i class="bi bi-people-fill"></i></div><div class="stat-value"><?=$total_petugas?></div><div class="stat-label">Petugas</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card bg-gradient-blue"><div class="stat-icon"><i class="bi bi-camera-fill"></i></div><div class="stat-value"><?=$total_deteksi?></div><div class="stat-label">Total Deteksi</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card bg-gradient-orange"><div class="stat-icon"><i class="bi bi-calendar-check"></i></div><div class="stat-value"><?=$deteksi_bulan?></div><div class="stat-label">Bulan Ini</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card bg-gradient-red"><div class="stat-icon"><i class="bi bi-journal-medical"></i></div><div class="stat-value"><?=$total_penyakit?></div><div class="stat-label">Kelas Penyakit</div></div></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-7"><div class="card h-100"><div class="card-header fw-semibold"><i class="bi bi-bar-chart-line"></i> Deteksi per Bulan</div><div class="card-body"><canvas id="chartBulanan" height="200"></canvas></div></div></div>
  <div class="col-lg-5"><div class="card h-100"><div class="card-header fw-semibold"><i class="bi bi-pie-chart"></i> Distribusi Penyakit</div><div class="card-body d-flex align-items-center justify-content-center"><canvas id="chartPenyakit" height="200"></canvas></div></div></div>
</div>
<div class="card"><div class="card-header fw-semibold d-flex justify-content-between"><span><i class="bi bi-clock-history"></i> Terbaru</span><a href="<?=e(url('admin/monitoring.php'))?>" class="btn btn-sm btn-outline-primary">Semua</a></div>
<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Waktu</th><th>Petugas</th><th>Hasil</th><th class="text-end">Akurasi</th></tr></thead><tbody>
<?php if(!$recent):?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada data.</td></tr>
<?php else: foreach($recent as $r):?><tr><td class="small"><?=e(format_tgl($r['tgl_deteksi']))?></td><td><?=e($r['nama_lengkap'])?></td><td><?=penyakit_badge($r['nama_penyakit'])?></td><td class="text-end fw-semibold"><?=e(format_akurasi((float)$r['akurasi']))?></td></tr>
<?php endforeach;endif;?></tbody></table></div></div>
<?php
$labels_bulan=array_map(fn($r)=>$r['bulan'],$chart_bulan); $data_bulan=array_map(fn($r)=>(int)$r['jml'],$chart_bulan);
$labels_peny=array_map(fn($r)=>$r['nama_penyakit'],$chart_penyakit); $data_peny=array_map(fn($r)=>(int)$r['jml'],$chart_penyakit);
$extra_js='<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script><script>
new Chart(document.getElementById("chartBulanan"),{type:"bar",data:{labels:'.json_encode($labels_bulan).',datasets:[{label:"Deteksi",data:'.json_encode($data_bulan).',backgroundColor:"rgba(46,125,50,0.7)",borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
new Chart(document.getElementById("chartPenyakit"),{type:"doughnut",data:{labels:'.json_encode($labels_peny).',datasets:[{data:'.json_encode($data_peny).',backgroundColor:["#e53935","#d81b60","#ffa000","#fb8c00","#424242","#43a047"]}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:"bottom",labels:{boxWidth:12}}}}});
</script>';
require __DIR__.'/../includes/footer.php';?>
