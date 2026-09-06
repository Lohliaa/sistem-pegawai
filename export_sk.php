<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SESSION['role'] != 'admin') { header('Location: index.php'); exit(); }
$filename = 'data_sk_' . date('Y-m-d_His') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');
echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>th{background:#2c3e50;color:#fff;font-weight:bold;padding:6px 8px;text-transform:uppercase;font-size:11px;white-space:nowrap;}td{padding:5px 8px;font-size:11px;white-space:nowrap;}tr:nth-child(even){background:#f8f9fa;}</style>';
echo '<table border="1" cellpadding="0" cellspacing="0">';
echo '<tr><th>No</th><th>No. SK</th><th>No. Tambahan</th><th>Nama</th><th>Gelar</th><th>NIPY</th><th>Gol/Ruang</th><th>Status Kepegawaian</th><th>Unit Kerja</th><th>TMT</th><th>Tanggal Mulai</th><th>Berlaku</th><th>Tanggal Akhir</th><th>Tanggal Ditetapkan</th><th>Tempat Lahir</th><th>Tanggal Lahir</th></tr>';
$no = 1;
$result = $conn->query("SELECT * FROM data_sk ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $no++ . '</td>';
    echo '<td>' . htmlspecialchars($row['no_sk']) . '</td>';
    echo '<td>' . htmlspecialchars($row['no_tambahan']) . '</td>';
    echo '<td>' . htmlspecialchars($row['nama']) . '</td>';
    echo '<td>' . htmlspecialchars($row['gelar']) . '</td>';
    echo '<td>' . htmlspecialchars($row['nipy']) . '</td>';
    echo '<td>' . htmlspecialchars($row['gol_ruang']) . '</td>';
    echo '<td>' . htmlspecialchars($row['status_kepegawaian']) . '</td>';
    echo '<td>' . htmlspecialchars($row['unit_kerja']) . '</td>';
    echo '<td>' . $row['tmt'] . '</td>';
    echo '<td>' . $row['tanggal_mulai'] . '</td>';
    echo '<td>' . htmlspecialchars($row['berlaku']) . '</td>';
    echo '<td>' . $row['tanggal_akhir'] . '</td>';
    echo '<td>' . $row['tanggal_ditetapkan'] . '</td>';
    echo '<td>' . htmlspecialchars($row['tempat_lahir']) . '</td>';
    echo '<td>' . $row['tanggal_lahir'] . '</td>';
    echo '</tr>';
}
echo '</table></html>';
