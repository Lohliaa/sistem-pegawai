<?php
require_once 'config/database.php';

echo "<h2>Install Kolom Jenis Kelamin</h2>";

// Cek apakah kolom sudah ada
$check = $conn->query("SHOW COLUMNS FROM pegawai LIKE 'jenis_kelamin'");
if ($check && $check->num_rows > 0) {
    echo "<p style='color: orange;'>⚠ Kolom 'jenis_kelamin' sudah ada di tabel pegawai.</p>";
} else {
    $sql = "ALTER TABLE `pegawai` ADD COLUMN `jenis_kelamin` VARCHAR(10) NULL AFTER `golongan`";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Kolom 'jenis_kelamin' berhasil ditambahkan ke tabel pegawai.</p>";
    } else {
        echo "<p style='color: red;'>✗ Gagal menambah kolom: " . $conn->error . "</p>";
    }
}

// Verifikasi struktur tabel
echo "<h3>Struktur Tabel Pegawai:</h3>";
$result = $conn->query("DESCRIBE pegawai");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Tampilkan data pegawai
echo "<h3>Data Pegawai:</h3>";
$result = $conn->query("SELECT id, nama, jenis_kelamin FROM pegawai LIMIT 10");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nama</th><th>Jenis Kelamin</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_kelamin'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>