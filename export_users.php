<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Set header untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="data_users_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Mulai output
echo '<html>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<table border="1">';
echo '<tr>';
echo '<th>No</th>';
echo '<th>ID User</th>';
echo '<th>Username</th>';
echo '<th>Role</th>';
echo '<th>Nama Pegawai</th>';
echo '<th>Tempat Lahir</th>';
echo '<th>Tanggal Lahir</th>';
echo '<th>Alamat</th>';
echo '<th>Jabatan</th>';
echo '<th>Golongan</th>';
echo '<th>Status Kepegawaian</th>';
echo '<th>Masa Kerja</th>';
echo '<th>Unit</th>';
echo '<th>Status</th>';
echo '</tr>';

$no = 1;
$result = $conn->query("
    SELECT u.id as user_id, u.username, u.role, 
           p.id as pegawai_id, p.nama, p.tempat, p.tanggal_lahir, p.alamat, 
           p.jabatan, p.golongan, p.status_kepegawaian, p.masa_kerja, p.unit
    FROM users u 
    LEFT JOIN pegawai p ON u.id = p.user_id 
    ORDER BY u.id
");

while($row = $result->fetch_assoc()) {
    $tanggal_lahir = !empty($row['tanggal_lahir']) ? date('d/m/Y', strtotime($row['tanggal_lahir'])) : '-';
    $status = $row['pegawai_id'] ? 'Terhubung' : 'Belum Terhubung';
    
    echo '<tr>';
    echo '<td>' . $no++ . '</td>';
    echo '<td>' . $row['user_id'] . '</td>';
    echo '<td>' . htmlspecialchars($row['username']) . '</td>';
    echo '<td>' . strtoupper($row['role']) . '</td>';
    echo '<td>' . htmlspecialchars($row['nama'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['tempat'] ?? '-') . '</td>';
    echo '<td>' . $tanggal_lahir . '</td>';
    echo '<td>' . htmlspecialchars($row['alamat'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['jabatan'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['golongan'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['status_kepegawaian'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['masa_kerja'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($row['unit'] ?? '-') . '</td>';
    echo '<td>' . $status . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</html>';
?>