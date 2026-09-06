<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SESSION['role'] != 'admin') { header('Location: index.php'); exit(); }

$filename = 'data_mou_' . date('Y-m-d_His') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

echo '<html>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>';
echo 'th{background:#2c3e50;color:#fff;font-weight:bold;padding:6px 8px;text-transform:uppercase;font-size:11px;white-space:nowrap;}';
echo 'td{padding:5px 8px;font-size:11px;white-space:nowrap;}';
echo 'tr:nth-child(even){background:#f8f9fa;}';
echo '</style>';
echo '<table border="1" cellpadding="0" cellspacing="0">';
echo '<tr>
<th>No</th>
<th>No. SK</th>
<th>No. Tambahan</th>
<th>Status Kepegawaian</th>
<th>Status Detail</th>
<th>Nama</th>
<th>Gelar</th>
<th>Hari Kerja</th>
<th>Jam Kerja</th>
<th>Alamat</th>
<th>Hari MOU</th>
<th>Tanggal MOU</th>
<th>Tempat Lahir</th>
<th>Tanggal Lahir</th>
<th>Unit Kerja</th>
<th>Gaji Pokok</th>
<th>Tunjangan Jabatan</th>
<th>Tunjangan Transport</th>
<th>Tunjangan Kinerja</th>
<th>Tunjangan Fungsional</th>
<th>THP</th>
<th>Terbilang</th>
<th>Tanggal Mulai</th>
<th>Berlaku</th>
<th>Tanggal Akhir</th>
<th>Saksi 1</th>
<th>Saksi 2</th>
</tr>';

$no = 1;
$result = $conn->query("SELECT * FROM data_mou ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $gaji = number_format($row['gaji_pokok'] ?? 0, 0, ',', '.');
    $tj = number_format($row['tunjangan_jabatan'] ?? 0, 0, ',', '.');
    $tt = number_format($row['tunjangan_transport'] ?? 0, 0, ',', '.');
    $tk = number_format($row['tunjangan_kinerja'] ?? 0, 0, ',', '.');
    $tf = number_format($row['tunjangan_fungsional'] ?? 0, 0, ',', '.');
    $thp = number_format($row['thp'] ?? 0, 0, ',', '.');
    echo '<tr>';
    echo '<td>' . $no++ . '</td>';
    echo '<td>' . htmlspecialchars($row['no_sk']) . '</td>';
    echo '<td>' . htmlspecialchars($row['no_tambahan']) . '</td>';
    echo '<td>' . htmlspecialchars($row['status_kepegawaian']) . '</td>';
    echo '<td>' . htmlspecialchars($row['status_detail']) . '</td>';
    echo '<td>' . htmlspecialchars($row['nama']) . '</td>';
    echo '<td>' . htmlspecialchars($row['gelar']) . '</td>';
    echo '<td>' . htmlspecialchars($row['hari_kerja']) . '</td>';
    echo '<td>' . htmlspecialchars($row['jam_kerja']) . '</td>';
    echo '<td>' . htmlspecialchars($row['alamat']) . '</td>';
    echo '<td>' . htmlspecialchars($row['hari']) . '</td>';
    echo '<td>' . $row['tgl_mou'] . '</td>';
    echo '<td>' . htmlspecialchars($row['tempat_lahir']) . '</td>';
    echo '<td>' . $row['tanggal_lahir'] . '</td>';
    echo '<td>' . htmlspecialchars($row['unit_kerja']) . '</td>';
    echo '<td style="text-align:right;">' . $gaji . '</td>';
    echo '<td style="text-align:right;">' . $tj . '</td>';
    echo '<td style="text-align:right;">' . $tt . '</td>';
    echo '<td style="text-align:right;">' . $tk . '</td>';
    echo '<td style="text-align:right;">' . $tf . '</td>';
    echo '<td style="text-align:right;">' . $thp . '</td>';
    echo '<td>' . htmlspecialchars($row['terbilang']) . '</td>';
    echo '<td>' . $row['tgl_mulai'] . '</td>';
    echo '<td>' . htmlspecialchars($row['berlaku']) . '</td>';
    echo '<td>' . $row['tanggal_akhir'] . '</td>';
    echo '<td>' . htmlspecialchars($row['saksi1']) . '</td>';
    echo '<td>' . htmlspecialchars($row['saksi2']) . '</td>';
    echo '</tr>';
}

echo '</table></html>';
?>