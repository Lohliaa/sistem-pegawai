<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Set header untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="data_pegawai_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Mulai output
echo '<html>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<table border="1">';
echo '<tr>';
echo '<th>No</th>';
echo '<th>Nama</th>';
echo '<th>Tanggal Lahir</th>';
echo '<th>Alamat</th>';
echo '<th>Jabatan</th>';
echo '<th>Golongan</th>';
echo '<th>Status Kepegawaian</th>';
echo '<th>Masa Kerja</th>';
echo '<th>Unit</th>';
echo '</tr>';

$no = 1;
$result = $conn->query("SELECT * FROM pegawai ORDER BY nama");
while($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $no++ . '</td>';
    echo '<td>' . $row['nama'] . '</td>';
    echo '<td>' . date('d/m/Y', strtotime($row['tanggal_lahir'])) . '</td>';
    echo '<td>' . $row['alamat'] . '</td>';
    echo '<td>' . $row['jabatan'] . '</td>';
    echo '<td>' . $row['golongan'] . '</td>';
    echo '<td>' . $row['status_kepegawaian'] . '</td>';
    echo '<td>' . $row['masa_kerja'] . '</td>';
    echo '<td>' . $row['unit'] . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</html>';
?>