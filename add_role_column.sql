-- Tambahkan kolom role ke tabel pegawai jika belum ada
ALTER TABLE pegawai ADD COLUMN role VARCHAR(20) DEFAULT 'staf' NOT NULL;
