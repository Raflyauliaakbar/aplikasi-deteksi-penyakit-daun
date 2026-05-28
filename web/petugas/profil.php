<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_role('Petugas');
$u=current_user();$pdo=db();
$stmt=$pdo->prepare('SELECT * FROM users WHERE id_user=?');$stmt->execute([$u['id_user']]);$me=$stmt->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_verify();$nu=trim($_POST['username']??'');$pl=(string)($_POST['password_lama']??'');$pb=(string)($_POST['password_baru']??'');$pu=(string)($_POST['password_ulang']??'');$err=[];
if(strlen($nu)<3)$err[]='Username min 3.';
if($nu!==$me['username']){$c=$pdo->prepare('SELECT COUNT(*) FROM users WHERE username=? AND id_user<>?');$c->execute([$nu,$me['id_user']]);if($c->fetchColumn()>0)$err[]='Username sudah ada.';}
$cpw=($pb!==''||$pu!=='');if($cpw){if(!password_verify($pl,$me['password']))$err[]='Password lama salah.';if(strlen($pb)<6)$err[]='Password baru min 6.';if($pb!==$pu)$err[]='Konfirmasi tidak cocok.';}
if($err)flash('error',implode(' ',$err));else{if($cpw){$pdo->prepare('UPDATE users SET username=?,password=? WHERE id_user=?')->execute([$nu,password_hash($pb,PASSWORD_DEFAULT),$me['id_user']]);}else{$pdo->prepare('UPDATE users SET username=? WHERE id_user=?')->execute([$nu,$me['id_user']]);} $_SESSION['user']['username']=$nu;flash('success','Profil diperbarui.');}redirect('petugas/profil.php');}
$page_title='Profil';$active_menu='profil';
require __DIR__.'/../includes/header.php';
?>
<div class="row"><div class="col-lg-7 mx-auto"><div class="card"><div class="card-header fw-semibold"><i class="bi bi-person-gear"></i> Ubah Profil</div><div class="card-body"><form method="post"><?=csrf_field()?>
<div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" class="form-control" value="<?=e($me['nama_lengkap'])?>" disabled><div class="form-text">Hanya admin yang dapat mengubah nama.</div></div>
<div class="mb-3"><label class="form-label fw-semibold">Username</label><input type="text" name="username" class="form-control" value="<?=e($me['username'])?>" required></div>
<hr><h6 class="text-muted mb-3">Ubah Password (opsional)</h6>
<div class="mb-3"><label class="form-label">Password Lama</label><input type="password" name="password_lama" class="form-control"></div>
<div class="mb-3"><label class="form-label">Password Baru</label><input type="password" name="password_baru" class="form-control" minlength="6"></div>
<div class="mb-3"><label class="form-label">Ulangi Password Baru</label><input type="password" name="password_ulang" class="form-control"></div>
<button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button></form></div></div></div></div>
<?php require __DIR__.'/../includes/footer.php';?>
