<?php
require_once 'config/database.php';

echo "<h2>Install Tabel Penilaian Kinerja</h2>";

// Tabel Status Kepegawaian
$tables = [
    'status_kepegawaian' => "CREATE TABLE IF NOT EXISTS `status_kepegawaian` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_status` VARCHAR(50) NOT NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    'periode_penilaian' => "CREATE TABLE IF NOT EXISTS `periode_penilaian` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_kuartal` VARCHAR(50) NOT NULL,
        `periode_bulan` VARCHAR(20) NOT NULL,
        `tahun` YEAR NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    'pejabat_penilai' => "CREATE TABLE IF NOT EXISTS `pejabat_penilai` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `pegawai_id` INT,
        `nama` VARCHAR(100) NOT NULL,
        `jabatan` VARCHAR(50) NOT NULL,
        `status_aktif` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        `unit` VARCHAR(20) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    'bahan_penilaian' => "CREATE TABLE IF NOT EXISTS `bahan_penilaian` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_bahan` VARCHAR(100) NOT NULL,
        `link` TEXT NOT NULL,
        `keterangan` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Tabel <b>$name</b> berhasil dibuat.</p>";
    } else {
        echo "<p style='color: red;'>✗ Gagal membuat tabel <b>$name</b>: " . $conn->error . "</p>";
    }
}

// Insert default status kepegawaian
$default_status = ['Mitra', 'Honorer', 'Magang', 'CPT', 'CGT', 'GT', 'PT'];
foreach ($default_status as $status) {
    $status = $conn->real_escape_string($status);
    $check = $conn->query("SELECT id FROM status_kepegawaian WHERE nama_status='$status'");
    if ($check && $check->num_rows == 0) {
        $conn->query("INSERT INTO status_kepegawaian (nama_status) VALUES ('$status')");
    }
}
echo "<p style='color: green;'>✓ Default status kepegawaian berhasil diinput.</p>";

// Verifikasi
echo "<h3>Daftar Tabel Penilaian Kinerja:</h3>";
$tables_check = ['status_kepegawaian', 'periode_penilaian', 'pejabat_penilai', 'bahan_penilaian'];
echo "<ul>";
foreach ($tables_check as $t) {
    $check = $conn->query("SHOW TABLES LIKE '$t'");
    $exists = ($check && $check->num_rows > 0) ? '✓' : '✗';
    $color = ($check && $check->num_rows > 0) ? 'green' : 'red';
    echo "<li style='color: $color;'>$exists $t</li>";
}
echo "</ul>";

echo "<h3>Data Status Kepegawaian:</h3>";
$result = $conn->query("SELECT * FROM status_kepegawaian ORDER BY id");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nama Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['nama_status']) . "</td></tr>";
    }
    echo "</table>";
}

$conn->close();
?>