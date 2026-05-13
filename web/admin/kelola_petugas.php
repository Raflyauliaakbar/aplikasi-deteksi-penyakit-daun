<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Admin');

$pdo = db();

// --- Aksi TAMBAH Petugas -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    csrf_verify();

    $username      = trim($_POST['username'] ?? '');
    $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
    $password      = (string) ($_POST['password'] ?? '');
    $password_ulg  = (string) ($_POST['password_ulang'] ?? '');

    $errors = [];
    if (strlen($username) < 3 || strlen($username) > 50) $errors[] = 'Username 3-50 karakter.';
    if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username))    $errors[] = 'Username hanya huruf, angka, titik, underscore.';
    if ($nama_lengkap === '' || strlen($nama_lengkap) > 50) $errors[] = 'Nama lengkap wajib (maks 50 karakter).';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $password_ulg) $errors[] = 'Konfirmasi password tidak cocok.';

    // Cek username unik
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username sudah digunakan.';
        }
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, nama_lengkap, role)
             VALUES (?, ?, ?, "Petugas")'
        );
        $stmt->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $nama_lengkap,
        ]);
        flash('success', 'Petugas "' . $nama_lengkap . '" berhasil ditambahkan.');
    }
    redirect('admin/kelola_petugas.php');
}

// --- Aksi HAPUS Petugas --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus') {
    csrf_verify();
    $id = (int) ($_POST['id_user'] ?? 0);

    if ($id > 0) {
        // Pastikan yang dihapus adalah Petugas (bukan Admin, bukan dirinya sendiri)
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id_user = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();
        if (!$target) {
            flash('error', 'Petugas tidak ditemukan.');
        } elseif ($target['role'] !== 'Petugas') {
            flash('error', 'Hanya akun petugas yang dapat dihapus.');
        } else {
            $del = $pdo->prepare('DELETE FROM users WHERE id_user = ?');
            $del->execute([$id]);
            flash('success', 'Akun petugas berhasil dihapus.');
        }
    }
    redirect('admin/kelola_petugas.php');
}

// --- Query list ----------------------------------------------------------
$list = $pdo->query("
    SELECT u.*, COUNT(h.id_deteksi) AS jml_deteksi
    FROM users u
    LEFT JOIN history_deteksi h ON h.id_user = u.id_user
    WHERE u.role = 'Petugas'
    GROUP BY u.id_user
    ORDER BY u.nama_lengkap ASC
")->fetchAll();

$page_title  = 'Kelola Petugas';
$active_menu = 'petugas';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0">Tambah atau hapus akun petugas. Edit akun tidak tersedia sesuai kebijakan.</p>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
    <i class="bi bi-person-plus"></i> Tambah Petugas
  </button>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>Nama Lengkap</th>
          <th class="text-center">Jumlah Deteksi</th>
          <th>Dibuat</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$list): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Belum ada petugas. Klik "Tambah Petugas" untuk menambahkan.</td></tr>
        <?php else: foreach ($list as $i => $r): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><code><?= e($r['username']) ?></code></td>
            <td><?= e($r['nama_lengkap']) ?></td>
            <td class="text-center"><span class="badge bg-secondary"><?= (int) $r['jml_deteksi'] ?></span></td>
            <td><?= e(format_tgl($r['created_at'])) ?></td>
            <td class="text-end">
              <form method="post" class="d-inline"
                    onsubmit="return confirm('Yakin hapus akun <?= e(addslashes($r['username'])) ?>?\nRiwayat deteksinya juga akan terhapus.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action"  value="hapus">
                <input type="hidden" name="id_user" value="<?= (int) $r['id_user'] ?>">
                <button class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i> Hapus
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Petugas -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="tambah">

      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus"></i> Tambah Petugas Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Username</label>
          <input type="text" name="username" class="form-control"
                 pattern="[a-zA-Z0-9_.]+" minlength="3" maxlength="50"
                 required autocomplete="off">
          <div class="form-text">3-50 karakter. Hanya huruf, angka, titik, underscore.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" class="form-control" maxlength="50" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Password</label>
          <input type="password" name="password" class="form-control" minlength="6" required>
          <div class="form-text">Minimal 6 karakter.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Ulangi Password</label>
          <input type="password" name="password_ulang" class="form-control" minlength="6" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
