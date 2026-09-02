-- Menambahkan kolom untuk tracking persetujuan Kabid
-- Jalankan file ini di phpMyAdmin atau via command line MySQL

-- Cek apakah kolom sudah ada sebelum menambahkan
--ALTER TABLE pengajuan ADD COLUMN approved_by_kabid INT NULL AFTER approved_by_kanit;
--ALTER TABLE pengajuan ADD COLUMN approved_date_kabid TIMESTAMP NULL AFTER approved_date_kanit;
--ALTER TABLE pengajuan ADD COLUMN catatan_kabid TEXT NULL AFTER catatan_kanit;

-- Command untuk menambahkan kolom jika belum ada:
ALTER TABLE `pengajuan` 
ADD COLUMN IF NOT EXISTS `approved_by_kabid` INT NULL AFTER `approved_by_kanit`,
ADD COLUMN IF NOT EXISTS `approved_date_kabid` TIMESTAMP NULL AFTER `approved_date_kanit`,
ADD COLUMN IF NOT EXISTS `catatan_kabid` TEXT NULL AFTER `catatan_kanit`;

-- Verifikasi kolom sudah ditambahkan
DESCRIBE pengajuan;