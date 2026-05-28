<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (is_logged_in()) { $u=current_user(); redirect($u['role']==='Admin'?'admin/dashboard.php':'petugas/dashboard.php'); }
$username='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_verify();
    $username=trim($_POST['username']??'');
    $password=(string)($_POST['password']??'');
    if($username===''||$password==='') flash('error','Username dan password wajib diisi.');
    else {
        $stmt=db()->prepare('SELECT * FROM users WHERE username=? LIMIT 1');
        $stmt->execute([$username]); $user=$stmt->fetch();
        if($user && password_verify($password,$user['password'])){
            login_user($user); flash('success','Selamat datang, '.$user['nama_lengkap'].'!');
            redirect($user['role']==='Admin'?'admin/dashboard.php':'petugas/dashboard.php');
        } else flash('error','Username atau password salah.');
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="login-page">
  <div class="login-card">
    <div class="login-brand"><i class="bi bi-leaf"></i><h1><?= e(APP_NAME) ?></h1><p>Sistem berbasis YOLOv8</p></div>
    <?= render_flashes() ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3"><label for="username" class="form-label fw-semibold"><i class="bi bi-person"></i> Username</label><input type="text" id="username" name="username" class="form-control form-control-lg" value="<?= e($username) ?>" required autofocus></div>
      <div class="mb-3"><label for="password" class="form-label fw-semibold"><i class="bi bi-lock"></i> Password</label><div class="input-group"><input type="password" id="password" name="password" class="form-control form-control-lg" required><button type="button" class="btn btn-outline-secondary" onclick="let p=document.getElementById('password');p.type=p.type==='password'?'text':'password'"><i class="bi bi-eye"></i></button></div></div>
      <button type="submit" class="btn btn-primary btn-lg w-100 mt-2"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</button>
    </form>
    <div class="login-demo mt-4"><small class="text-muted d-block mb-2"><strong>Akun Demo:</strong></small><div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-success flex-fill" onclick="document.getElementById('username').value='admin';document.getElementById('password').value='admin123'">Admin</button><button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="document.getElementById('username').value='petugas1';document.getElementById('password').value='petugas123'">Petugas</button></div></div>
  </div>
</body>
</html>
