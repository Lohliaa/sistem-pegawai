-- Tambahkan kolom tempat lahir ke tabel pegawai
ALTER TABLE pegawai ADD COLUMN tempat VARCHAR(100) NULL AFTER nama;
