<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_login();
$u=current_user();$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_verify();$file=$_FILES['citra']??null;
if(!$file||($file['error']??4)!==0){flash('error','Gagal upload.');redirect('shared/deteksi.php');}
if($file['size']>MAX_UPLOAD_SIZE){flash('error','Maks 5MB.');redirect('shared/deteksi.php');}
$fi=new finfo(FILEINFO_MIME_TYPE);$mime=$fi->file($file['tmp_name'])?:'';
if(!in_array($mime,ALLOWED_MIME,true)){flash('error','Format JPG/PNG.');redirect('shared/deteksi.php');}
$nn=safe_filename($file['name']);$dest=UPLOAD_DIR_ORIGINAL.$nn;
if(!move_uploaded_file($file['tmp_name'],$dest)){flash('error','Gagal simpan.');redirect('shared/deteksi.php');}
$ch=curl_init(FLASK_API_URL);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>FLASK_TIMEOUT,CURLOPT_POSTFIELDS=>['image'=>new CURLFile($dest,$mime,$nn)]]);
$resp=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
if($err||$http!==200||!$resp){@unlink($dest);flash('error','AI Server error. '.$err);redirect('shared/deteksi.php');}
$data=json_decode($resp,true);if(!is_array($data)||empty($data['success'])){@unlink($dest);flash('error','Response invalid.');redirect('shared/deteksi.php');}
$label=trim($data['nama_penyakit']??'');$akurasi=(float)($data['confidence']??0);$rf=trim($data['result_image']??'');
if($label===''){@unlink($dest);flash('error','No label.');redirect('shared/deteksi.php');}
$stmt=$pdo->prepare('SELECT id_penyakit FROM penyakit WHERE nama_penyakit=?');$stmt->execute([$label]);$idp=$stmt->fetchColumn();
if(!$idp){@unlink($dest);flash('error','Label "'.$label.'" tidak terdaftar.');redirect('shared/deteksi.php');}
$flask_path=__DIR__.'/../../ai_server/static/results/'.$rf;
if($rf&&file_exists($flask_path)){copy($flask_path,UPLOAD_DIR_DETECTED.$rf);}else{copy($dest,UPLOAD_DIR_DETECTED.$nn);$rf=$nn;}
$stmt=$pdo->prepare('INSERT INTO history_deteksi(id_user,id_penyakit,nama_file,akurasi)VALUES(?,?,?,?)');$stmt->execute([$u['id_user'],(int)$idp,$rf,$akurasi]);
$idd=(int)$pdo->lastInsertId();flash('success','Deteksi berhasil!');redirect('shared/hasil.php?id='.$idd);}
$page_title='Deteksi Penyakit';$active_menu='deteksi';
require __DIR__.'/../includes/header.php';
?>
<div class="row"><div class="col-lg-8 mx-auto"><div class="card"><div class="card-header fw-semibold"><i class="bi bi-camera"></i> Unggah Citra Daun</div><div class="card-body">
<form method="post" enctype="multipart/form-data" id="form-deteksi" novalidate><?=csrf_field()?>
<div id="upload-zone" class="upload-zone mb-3"><i class="bi bi-cloud-arrow-up-fill"></i><h5 class="mt-2">Pilih foto daun jeruk</h5><p class="text-muted small mb-3">JPG/PNG, maks 5 MB</p><div class="d-flex gap-2 justify-content-center flex-wrap"><label for="file-citra" class="btn btn-primary"><i class="bi bi-camera-fill"></i> Kamera</label><label for="file-galeri" class="btn btn-outline-primary"><i class="bi bi-images"></i> Galeri</label></div></div>
<input type="file" id="file-citra" name="citra" accept="image/jpeg,image/png" capture="environment" class="d-none" required>
<input type="file" id="file-galeri" accept="image/jpeg,image/png" class="d-none">
<div id="preview-box" class="d-none text-center"><img id="preview-img" class="img-fluid rounded mb-3" style="max-height:350px"><div class="d-flex gap-2 justify-content-center"><button type="button" id="btn-reset" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i> Ulang</button><button type="submit" id="btn-submit" class="btn btn-primary btn-lg" disabled><i class="bi bi-magic"></i> Analisis</button></div></div>
</form></div></div>
<div class="card mt-3"><div class="card-header"><i class="bi bi-lightbulb"></i> Tips</div><div class="card-body"><ul class="mb-0 small"><li>Daun terlihat jelas di tengah.</li><li>Pencahayaan alami.</li><li>Fokus pada bagian bergejala.</li><li>Jarak 20-30 cm.</li></ul></div></div></div></div>
<?php require __DIR__.'/../includes/footer.php';?>
