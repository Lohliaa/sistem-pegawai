-- Menambahkan kolom untuk jenis kelamin ke tabel pegawai
ALTER TABLE pegawai ADD COLUMN IF NOT EXISTS `jenis_kelamin` VARCHAR(10) NULL AFTER `golongan`;

-- Verifikasi kolom sudah ditambahkan
DESCRIBE pegawai;