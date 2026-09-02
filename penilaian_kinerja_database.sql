-- =============================================
-- Tabel untuk Penilaian Kinerja
-- =============================================

-- Tabel Status Kepegawaian
CREATE TABLE IF NOT EXISTS `status_kepegawaian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_status` VARCHAR(50) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert data default
INSERT IGNORE INTO `status_kepegawaian` (`nama_status`) VALUES 
    ('Mitra'), ('Honorer'), ('Magang'), ('CPT'), ('CGT'), ('GT'), ('PT');

-- Tabel Periode Penilaian
CREATE TABLE IF NOT EXISTS `periode_penilaian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kuartal` VARCHAR(50) NOT NULL,
    `periode_bulan` VARCHAR(20) NOT NULL,
    `tahun` YEAR NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Pejabat Penilai (dari role kanit/kabid)
CREATE TABLE IF NOT EXISTS `pejabat_penilai` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pegawai_id` INT,
    `nama` VARCHAR(100) NOT NULL,
    `jabatan` VARCHAR(50) NOT NULL,
    `status_aktif` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    `unit` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai`(`id`) ON DELETE SET NULL
);

-- Tabel Bahan Penilaian
CREATE TABLE IF NOT EXISTS `bahan_penilaian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_bahan` VARCHAR(100) NOT NULL,
    `link` TEXT NOT NULL,
    `keterangan` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Verifikasi tabel
-- =============================================
SHOW TABLES;