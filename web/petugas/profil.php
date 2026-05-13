<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Petugas');

$u   = current_user();
$pdo = db();

// Ambil data terbaru
$stmt = $pdo->prepare('SELECT * FROM users WHERE id_user = ? LIMIT 1');
$stmt->execute([$u['id_user']]);
$me = $stmt->fetch();
if (!$me) { logout_user(); redirect('auth/login.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $new_username = trim($_POST['username'] ?? '');
    $current_pw   = (string) ($_POST['password_lama'] ?? '');
    $new_pw       = (string) ($_POST['password_baru'] ?? '');
    $new_pw_ulg   = (string) ($_POST['password_ulang'] ?? '');

    $errors = [];

    // --- Validasi username --------------------------------------------
    if (strlen($new_username) < 3 || strlen($new_username) > 50) {
        $errors[] = 'Username 3-50 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.]+$/', $new_username)) {
        $errors[] = 'Username hanya huruf, angka, titik, underscore.';
    } elseif ($new_username !== $me['username']) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id_user <> ?');
        $chk->execute([$new_username, $me['id_user']]);
        if ($chk->fetchColumn() > 0) $errors[] = 'Username sudah digunakan.';
    }

    // --- Validasi password (opsional) ---------------------------------
    $change_pw = ($new_pw !== '' || $new_pw_ulg !== '');
    if ($change_pw) {
        if (!password_verify($current_pw, $me['password'])) {
            $errors[] = 'Password lama salah.';
        }
        if (strlen($new_pw) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        }
        if ($new_pw !== $new_pw_ulg) {
            $errors[] = 'Konfirmasi password baru tidak cocok.';
        }
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('petugas/profil.php');
    }

    // --- Simpan -------------------------------------------------------
    if ($change_pw) {
        $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ? WHERE id_user = ?');
        $stmt->execute([$new_username, password_hash($new_pw, PASSWORD_DEFAULT), $me['id_user']]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET username = ? WHERE id_user = ?');
        $stmt->execute([$new_username, $me['id_user']]);
    }

    // Refresh session
    $_SESSION['user']['username'] = $new_username;
    flash('success', 'Profil berhasil diperbarui.');
    redirect('petugas/profil.php');
}

$page_title  = 'Profil Saya';
$active_menu = 'profil';
require __DIR__ . '/../includes/header.php';
?>

<div class="row">
  <div class="col-lg-7 mx-auto">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-gear"></i> Ubah Profil</div>
      <div class="card-body">
        <form method="post" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap</label>
            <input type="text" class="form-control" value="<?= e($me['nama_lengkap']) ?>" readonly disabled>
            <div class="form-text">Nama lengkap hanya dapat diubah oleh admin.</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" name="username" class="form-control"
                   pattern="[a-zA-Z0-9_.]+" minlength="3" maxlength="50"
                   value="<?= e($me['username']) ?>" required>
          </div>

          <hr class="my-4">
          <h6 class="mb-3 text-muted">Ubah Password (opsional)</h6>

          <div class="mb-3">
            <label class="form-label fw-semibold">Password Lama</label>
            <input type="password" name="password_lama" class="form-control" autocomplete="current-password">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Password Baru</label>
            <input type="password" name="password_baru" class="form-control" minlength="6" autocomplete="new-password">
            <div class="form-text">Minimal 6 karakter. Kosongkan jika tidak ingin mengubah.</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Ulangi Password Baru</label>
            <input type="password" name="password_ulang" class="form-control" minlength="6" autocomplete="new-password">
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Simpan Perubahan
            </button>
            <a href="<?= e(url('petugas/dashboard.php')) ?>" class="btn btn-light">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
