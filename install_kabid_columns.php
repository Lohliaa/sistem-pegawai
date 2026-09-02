<?php
require_once 'config/database.php';

$columns_to_add = [
    'approved_by_kabid' => 'INT NULL AFTER approved_by_kanit',
    'approved_date_kabid' => 'TIMESTAMP NULL AFTER approved_date_kanit',
    'catatan_kabid' => 'TEXT NULL AFTER catatan_kanit'
];

$success = true;
$messages = [];

foreach ($columns_to_add as $column => $definition) {
    // Cek apakah kolom sudah ada
    $check = $conn->query("SHOW COLUMNS FROM pengajuan LIKE '$column'");
    if ($check && $check->num_rows > 0) {
        $messages[] = "✓ Kolom '$column' sudah ada";
        continue;
    }
    
    // Tambahkan kolom
    $sql = "ALTER TABLE pengajuan ADD COLUMN $column $definition";
    if ($conn->query($sql)) {
        $messages[] = "✓ Berhasil menambahkan kolom '$column'";
    } else {
        $success = false;
        $messages[] = "✗ Gagal menambahkan kolom '$column': " . $conn->error;
    }
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Install Kolom Kabid</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='p-5'>
    <div class='container'>
        <h2>Hasil Instalasi Kolom Kabid</h2>
        <div class='alert alert-" . ($success ? 'success' : 'danger') . "'>
            <ul class='mb-0'>";
foreach ($messages as $msg) {
    echo "<li>$msg</li>";
}
echo "          </ul>
        </div>
        <h4>Struktur Tabel Saat Ini:</h4>
        <pre>";
$desc = $conn->query('DESCRIBE pengajuan');
while ($row = $desc->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | Null: " . $row['Null'] . "\n";
}
echo "</pre>
        <a href='index.php' class='btn btn-primary'>Kembali ke Dashboard</a>
    </div>
</body>
</html>";

$conn->close();
?>