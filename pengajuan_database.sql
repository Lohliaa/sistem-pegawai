-- Buat tabel pengajuan jika belum ada
CREATE TABLE IF NOT EXISTS `pengajuan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipe_pengajuan` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `tanggal_tmt` date NOT NULL,
  `unit` varchar(50) NOT NULL,
  `keterangan` text,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  
  -- Data Pengaju
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  
  -- Data Persetujuan Kanit/Kabid
  `approved_by_kanit` int(11),
  `approved_date_kanit` timestamp NULL,
  `catatan_kanit` text,
  `rejected_by_kanit` int(11),
  `rejected_date_kanit` timestamp NULL,
  `rejected_reason` text,
  
  -- Data Pemrosesan Admin
  `processed_by` int(11),
  `processed_date` timestamp NULL,
  `completed_by` int(11),
  `completed_date` timestamp NULL,
  `catatan_admin` text,
  
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tampilkan struktur tabel untuk verifikasi
DESCRIBE pengajuan;
