-- ============================================================
-- Database: aplikasi_deteksi_daun
-- Aplikasi Deteksi Penyakit Daun Jeruk
-- ============================================================

CREATE DATABASE IF NOT EXISTS `aplikasi_deteksi_daun`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `aplikasi_deteksi_daun`;

-- ------------------------------------------------------------
-- Tabel: users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `history_deteksi`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `penyakit`;

CREATE TABLE `users` (
  `id_user`       INT(11)      NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `password`      VARCHAR(255) NOT NULL,
  `nama_lengkap`  VARCHAR(50)  NOT NULL,
  `role`          ENUM('Admin','Petugas') NOT NULL DEFAULT 'Petugas',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: penyakit (6 kelas tetap)
-- ------------------------------------------------------------
CREATE TABLE `penyakit` (
  `id_penyakit`   INT(11)      NOT NULL AUTO_INCREMENT,
  `nama_penyakit` VARCHAR(50)  NOT NULL UNIQUE,
  `deskripsi`     TEXT         NOT NULL,
  `solusi`        TEXT         NOT NULL,
  PRIMARY KEY (`id_penyakit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: history_deteksi
-- ------------------------------------------------------------
CREATE TABLE `history_deteksi` (
  `id_deteksi`    INT(11)      NOT NULL AUTO_INCREMENT,
  `id_user`       INT(11)      NOT NULL,
  `id_penyakit`   INT(11)      NOT NULL,
  `nama_file`     VARCHAR(100) NOT NULL,
  `akurasi`       FLOAT        NOT NULL,
  `tgl_deteksi`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_deteksi`),
  KEY `fk_hist_user` (`id_user`),
  KEY `fk_hist_penyakit` (`id_penyakit`),
  CONSTRAINT `fk_hist_user`
    FOREIGN KEY (`id_user`) REFERENCES `users`(`id_user`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hist_penyakit`
    FOREIGN KEY (`id_penyakit`) REFERENCES `penyakit`(`id_penyakit`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Akun default:
--   Admin   -> username: admin     password: admin123
--   Petugas -> username: petugas1  password: petugas123
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('admin',    '$2y$12$5nvuIGhmSal5/x1oVz/h7.hN6nf7O2XSn3YXA1O77DiLGKy22Itbm', 'Administrator Sistem', 'Admin'),
('petugas1', '$2y$12$6dEGxdAiGIS6ri3mvrOYy.3aKmjVAkt5L6J3KgBXTyCUROvWxfvBe', 'Budi Santoso',         'Petugas');

-- 6 kelas penyakit tetap (konten bisa di-update via menu Admin)
INSERT INTO `penyakit` (`nama_penyakit`, `deskripsi`, `solusi`) VALUES
('Canker',
 'Penyakit citrus canker disebabkan oleh bakteri Xanthomonas citri. Gejala berupa bercak kuning yang berkembang menjadi lesi coklat bergabus (corky) dengan halo kuning, muncul pada daun, buah, dan ranting.',
 '1. Pangkas dan bakar bagian tanaman yang terinfeksi.\n2. Semprotkan bakterisida berbahan tembaga (copper-based) setiap 2-3 minggu.\n3. Hindari kerja lapangan saat daun basah untuk mencegah penyebaran.\n4. Gunakan bibit yang bebas penyakit dan sanitasi alat potong.'),

('HLB',
 'Huanglongbing (HLB) atau penyakit greening disebabkan bakteri Candidatus Liberibacter. Gejala: daun menguning tidak simetris (blotchy mottle), urat daun hijau sementara lamina menguning, buah kecil dan pahit.',
 '1. Cabut dan musnahkan tanaman yang sudah terinfeksi (tidak ada kuratif).\n2. Kendalikan vektor kutu loncat (Diaphorina citri) dengan insektisida.\n3. Gunakan bibit bersertifikat bebas HLB.\n4. Lakukan monitoring rutin setiap 2 minggu.'),

('Greasy Spot',
 'Greasy spot disebabkan oleh jamur Mycosphaerella citri. Gejala tampak sebagai bercak kuning di permukaan atas daun dan bercak berminyak coklat-hitam di permukaan bawah daun, menyebabkan daun rontok prematur.',
 '1. Kumpulkan dan musnahkan daun-daun yang berguguran.\n2. Aplikasikan fungisida berbahan tembaga atau minyak petroleum pada awal musim hujan.\n3. Jaga drainase kebun agar tidak lembab berlebihan.\n4. Pangkas cabang untuk meningkatkan sirkulasi udara.'),

('Melanose',
 'Melanose disebabkan oleh jamur Diaporthe citri. Gejala berupa bintik-bintik kecil coklat-kehitaman yang kasar seperti amplas pada daun muda, ranting, dan buah.',
 '1. Pangkas ranting mati (sumber inokulum utama) dan bakar.\n2. Aplikasikan fungisida tembaga 2-3 minggu setelah kelopak bunga gugur.\n3. Jaga kesehatan tanaman dengan pemupukan seimbang.\n4. Hindari luka pada tanaman saat pemangkasan.'),

('Sooty Mold',
 'Embun jelaga (sooty mold) adalah lapisan jamur hitam yang tumbuh pada embun madu (honeydew) yang dikeluarkan serangga penghisap seperti kutu daun, kutu putih, dan kutu sisik.',
 '1. Kendalikan serangga penghasil embun madu dengan insektisida atau sabun insektisida.\n2. Cuci daun dengan air bersih atau larutan sabun lembut untuk menghilangkan jelaga.\n3. Tingkatkan populasi musuh alami (predator) di kebun.\n4. Pangkas tajuk agar sirkulasi udara lebih baik.'),

('Healthy',
 'Daun dalam kondisi sehat. Tidak ditemukan gejala penyakit pada citra yang dianalisis. Warna daun hijau merata, permukaan halus, tidak ada bercak, lesi, atau jamur.',
 '1. Lanjutkan praktik budidaya yang baik: pemupukan teratur dan pengairan cukup.\n2. Lakukan pemantauan rutin minimal 2 minggu sekali.\n3. Jaga kebersihan kebun dari gulma dan sisa tanaman sakit.\n4. Aplikasikan pupuk NPK sesuai dosis anjuran untuk menjaga daya tahan tanaman.');
