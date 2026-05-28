<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_role('Admin');
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_verify();$id=(int)($_POST['id_penyakit']??0);$d=trim($_POST['deskripsi']??'');$s=trim($_POST['solusi']??'');if($id>0&&$d!==''&&$s!==''){$pdo->prepare('UPDATE penyakit SET deskripsi=?,solusi=? WHERE id_penyakit=?')->execute([$d,$s,$id]);flash('success','Info penyakit diperbarui.');}else flash('error','Data tidak lengkap.');redirect('admin/penyakit.php');}
$list=$pdo->query('SELECT * FROM penyakit ORDER BY id_penyakit')->fetchAll();
$page_title='Info Penyakit';$active_menu='penyakit';
require __DIR__.'/../includes/header.php';
?>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> 6 kelas penyakit bersifat tetap. Anda hanya dapat mengedit <strong>deskripsi</strong> dan <strong>solusi</strong>.</div>
<div class="accordion" id="acc">
<?php foreach($list as $i=>$p):?>
<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button <?=$i>0?'collapsed':''?>" type="button" data-bs-toggle="collapse" data-bs-target="#p<?=$p['id_penyakit']?>"><?=penyakit_badge($p['nama_penyakit'])?> <span class="ms-2"><?=e($p['nama_penyakit'])?></span></button></h2>
<div id="p<?=$p['id_penyakit']?>" class="accordion-collapse collapse <?=$i===0?'show':''?>" data-bs-parent="#acc"><div class="accordion-body">
<form method="post"><?=csrf_field()?><input type="hidden" name="id_penyakit" value="<?=(int)$p['id_penyakit']?>">
<div class="mb-3"><label class="form-label fw-semibold">Deskripsi Gejala</label><textarea name="deskripsi" class="form-control" rows="4" required><?=e($p['deskripsi'])?></textarea></div>
<div class="mb-3"><label class="form-label fw-semibold">Solusi Penanganan</label><textarea name="solusi" class="form-control" rows="6" required><?=e($p['solusi'])?></textarea></div>
<button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button></form></div></div></div>
<?php endforeach;?>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
