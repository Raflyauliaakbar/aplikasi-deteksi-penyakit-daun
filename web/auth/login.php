<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Sudah login? Redirect ke dashboard sesuai role.
if (is_logged_in()) {
    $u = current_user();
    redirect($u['role'] === 'Admin' ? 'admin/dashboard.php' : 'petugas/dashboard.php');
}

$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash('error', 'Username dan password wajib diisi.');
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            login_user($user);
            flash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
            redirect($user['role'] === 'Admin' ? 'admin/dashboard.php' : 'petugas/dashboard.php');
        } else {
            flash('error', 'Username atau password salah.');
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="login-page">
  <div class="login-card">
    <div class="brand-head">
      <i class="bi bi-tree-fill"></i>
      <h1>Deteksi Penyakit Daun Jeruk</h1>
      <p>Silakan masuk dengan akun Anda</p>
    </div>

    <?= render_flashes() ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <div class="mb-3">
        <label for="username" class="form-label fw-semibold">
          <i class="bi bi-person"></i> Username
        </label>
        <input type="text" id="username" name="username"
               class="form-control form-control-lg"
               value="<?= e($username) ?>"
               autocomplete="username" required autofocus>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label fw-semibold">
          <i class="bi bi-lock"></i> Password
        </label>
        <div class="input-group input-group-lg">
          <input type="password" id="password" name="password"
                 class="form-control"
                 autocomplete="current-password" required>
          <button type="button" class="btn btn-outline-secondary"
                  onclick="var p=document.getElementById('password'); p.type = p.type==='password'?'text':'password';">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
      </button>
    </form>

    <hr class="my-4">
    <div class="small text-muted">
      <strong>Akun demo:</strong><br>
      Admin &rarr; <code>admin</code> / <code>admin123</code><br>
      Petugas &rarr; <code>petugas1</code> / <code>petugas123</code>
    </div>
  </div>
</body>
</html>
