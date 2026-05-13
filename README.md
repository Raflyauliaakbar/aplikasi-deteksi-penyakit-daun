# Aplikasi Deteksi Penyakit Daun Jeruk

Sistem web berbasis **PHP Native + Flask API + MySQL** untuk mendeteksi
6 kelas penyakit daun jeruk menggunakan model **YOLOv8n (Nano)**.

Arsitektur terpisah (_decoupled_) antara antarmuka pengguna dan pemrosesan AI,
sehingga keduanya dapat di-scale dan di-maintain secara independen.

---

## Fitur

### Admin
- **Dashboard**: ringkasan statistik & distribusi deteksi per penyakit.
- **Kelola Petugas**: tambah & hapus akun petugas (tidak bisa edit).
- **Kelola Informasi Penyakit**: perbarui deskripsi & solusi 6 kelas penyakit tetap.
- **Monitor Riwayat**: pantau seluruh riwayat deteksi dengan filter.

### Petugas
- **Deteksi Penyakit**: unggah citra dari kamera atau galeri.
- **Hasil Deteksi**: tampilan bounding box + deskripsi + solusi.
- **Riwayat Pribadi**: galeri card semua deteksi miliknya dengan filter.
- **Unduh PDF**: laporan hasil deteksi siap cetak.
- **Profil**: ubah username & password (nama lengkap _read-only_).

### 6 Kelas Penyakit
`Canker`, `HLB`, `Greasy Spot`, `Melanose`, `Sooty Mold`, `Healthy`

---

## Struktur Proyek

```
aplikasi-deteksi-penyakit-daun/
├── web/                      # PHP Native (antarmuka pengguna)
│   ├── admin/                # Halaman Admin
│   ├── petugas/              # Halaman Petugas
│   ├── auth/                 # Login & logout
│   ├── config/               # config.php, database.php
│   ├── includes/             # bootstrap, header, footer, auth, helpers
│   ├── assets/               # CSS & JS
│   ├── uploads/              # Citra: original/ & detected/
│   └── index.php             # Entry point
│
├── ai_server/                # Flask API (pemrosesan AI)
│   ├── app.py                # MOCK (aktif saat ini)
│   ├── app_yolo.py           # Versi YOLOv8 asli (aktifkan nanti)
│   ├── requirements.txt
│   └── model/                # Letakkan best.pt di sini
│
├── database/
│   └── schema.sql            # DDL + seed data
│
└── README.md
```

---

## Kebutuhan Sistem

| Komponen     | Versi       |
|--------------|-------------|
| PHP          | 8.0 atau lebih baru (dengan ekstensi `pdo_mysql`, `curl`, `fileinfo`, `gd`) |
| MySQL/MariaDB| 5.7 / 10.4+ |
| Python       | 3.9+        |
| Web Server   | Apache/Nginx (atau `php -S` untuk development) |

---

## Instalasi

### 1. Clone & siapkan database

```bash
git clone https://github.com/Raflyauliaakbar/aplikasi-deteksi-penyakit-daun.git
cd aplikasi-deteksi-penyakit-daun

# Import schema
mysql -u root -p < database/schema.sql
```

### 2. Jalankan Web Server (PHP)

Jika pakai XAMPP/Laragon → letakkan folder `web/` di `htdocs/` dan akses
`http://localhost/aplikasi-deteksi-penyakit-daun/web/`.

**Atau** jalankan via built-in PHP server:

```bash
cd web
php -S localhost:8000
```

Buka `http://localhost:8000` di browser.

> **Konfigurasi database** ada di `web/config/database.php`
> (default: `root` tanpa password, database `aplikasi_deteksi_daun`).

### 3. Jalankan AI Server (Flask) - Mode MOCK

Mode mock ini mengembalikan hasil dummy, sehingga aplikasi end-to-end bisa
diuji **tanpa perlu file `best.pt`**.

```bash
cd ai_server
python -m venv venv
source venv/bin/activate          # Linux/Mac
# venv\Scripts\activate            # Windows

pip install -r requirements.txt
python app.py
```

Server akan berjalan di `http://127.0.0.1:5000`.
Cek `http://127.0.0.1:5000/health` untuk memastikan hidup.

### 4. Login

| Role     | Username   | Password     |
|----------|------------|--------------|
| Admin    | `admin`    | `admin123`   |
| Petugas  | `petugas1` | `petugas123` |

> **Wajib ganti password** akun `admin` setelah login pertama.

---

## Mengaktifkan Model YOLOv8 Asli

Saat file `best.pt` sudah tersedia:

1. **Taruh model** di `ai_server/model/best.pt`.

2. **Install dependency tambahan**:
   - Edit `ai_server/requirements.txt`, uncomment baris `ultralytics`, `opencv-python`, `numpy`, `torch`.
   - Jalankan:
     ```bash
     pip install -r requirements.txt
     ```

3. **Swap file Flask**:
   ```bash
   cd ai_server
   mv app.py app_mock.py
   mv app_yolo.py app.py
   python app.py
   ```

4. **Pastikan nama kelas** pada `app.py` (variabel `CLASSES`) dan tabel `penyakit`
   di MySQL **sama persis**. Jika urutan output model berbeda, sesuaikan daftar `CLASSES`.

---

## Alur Kerja Deteksi

```
Petugas (Browser)
   │  upload citra daun (kamera / galeri)
   ▼
web/petugas/deteksi.php
   │  validasi MIME + ukuran (<= 5 MB)
   │  simpan → web/uploads/original/
   │  POST multipart/form-data
   ▼
ai_server/app.py (POST /predict)
   │  preprocess 640x640 → YOLOv8n inference
   │  gambar bounding box di atas citra
   │  return JSON { label, confidence, bbox, image_base64 }
   ▼
web/petugas/deteksi.php
   │  decode base64 → simpan ke web/uploads/detected/
   │  JOIN tabel penyakit → ambil deskripsi + solusi
   │  INSERT INTO history_deteksi
   ▼
web/petugas/hasil.php  (tampil ke Petugas)
```

---

## Keamanan

- Password disimpan menggunakan `password_hash()` (bcrypt).
- Semua query menggunakan **PDO prepared statements**.
- **CSRF token** untuk semua form POST.
- **Role guard** di setiap halaman (`require_role('Admin')` / `require_role('Petugas')`).
- Validasi upload: MIME type, ekstensi, ukuran.
- Nama file upload di-_sanitize_ + random token.

---

## Troubleshooting

**"Gagal menghubungi AI Server"**
- Pastikan Flask berjalan: `curl http://127.0.0.1:5000/health`
- Cek `FLASK_API_URL` di `web/config/config.php`
- Cek ekstensi PHP `curl` aktif: `php -m | grep curl`

**Gambar hasil tidak tampil**
- Cek permission folder: `chmod -R 775 web/uploads/`
- Cek `BASE_URL` di `web/config/config.php` (terutama jika pakai subfolder)

**Login gagal terus**
- Pastikan `schema.sql` sudah di-import dengan benar.
- Reset hash password manual:
  ```bash
  php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
  ```
  Lalu update baris di tabel `users`.

---

## Lisensi

Proyek ini dibuat untuk keperluan akademik/penelitian.
