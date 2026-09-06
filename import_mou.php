<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SESSION['role'] != 'admin') { header('Location: index.php'); exit(); }
use PhpOffice\PhpSpreadsheet\IOFactory;
$msg = ''; $msg_type = '';
if (isset($_GET['template'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=template_import_mou.xls');
    echo '<html><meta charset=utf-8><table border=1>';
    $h = ['No. SK','No. Tambahan','Status Kepegawaian','Status Detail','Nama','Gelar','Hari Kerja','Jam Kerja','Alamat','Hari','Tanggal MOU','Tempat Lahir','Tanggal Lahir','Unit Kerja','Gaji Pokok','Tunjangan Jabatan','Tunjangan Transport','Tunjangan Kinerja','Tunjangan Fungsional','THP','Terbilang','Tanggal Mulai','Berlaku','Tanggal Akhir','Saksi 1','Saksi 2'];
    echo '<tr style=background:#2c3e50;color:#fff;>';
    foreach($h as $x) echo '<th>'.$x.'</th>';
    echo '</tr><tr>';
    $v = ['001/ABC','A1','PT','PKWTT','Contoh','S.Pd.','Senin-Jumat','08.00-16.00','Jl. Contoh','Senin','2026-09-01','Surabaya','1990-01-15','Yayasan','3000000','500000','200000','300000','400000','4400000','Empat Juta','2026-09-01','1 Tahun','2027-08-31','Hendra','Wati'];
    foreach($v as $x) echo '<td>'.$x.'</td>';
    echo '</tr></table></html>'; exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel'];
    if ($file['error'] !== UPLOAD_ERR_OK) { $msg = 'Upload gagal!'; $msg_type = 'danger'; }
    else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xls','xlsx','csv'])) { $msg = 'Format harus .xls/.xlsx/.csv'; $msg_type = 'danger'; }
        else {
            try {
                require_once __DIR__ . '/vendor/autoload.php';
                $reader = IOFactory::createReaderForFile($file['tmp_name']);
                $spreadsheet = $reader->load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
                if (count($rows) < 2) { $msg = 'File kosong!'; $msg_type = 'warning'; }
                else {
                    $ins = 0; $sk = 0; $errs = [];
                    for ($i = 1; $i < count($rows); $i++) {
                        $r = $rows[$i];
                        $empty = true;
                        for ($j = 0; $j < 5; $j++) { if (trim($r[$j] ?? '') !== '') { $empty = false; break; } }
                        if ($empty) { $sk++; continue; }
                        $d = [
                            'no_sk'=>$conn->real_escape_string($r[0] ?? ''),
                            'no_tambahan'=>$conn->real_escape_string($r[1] ?? ''),
                            'status_kepegawaian'=>$conn->real_escape_string($r[2] ?? ''),
                            'status_detail'=>$conn->real_escape_string($r[3] ?? ''),
                            'nama'=>$conn->real_escape_string($r[4] ?? ''),
                            'gelar'=>$conn->real_escape_string($r[5] ?? ''),
                            'hari_kerja'=>$conn->real_escape_string($r[6] ?? ''),
                            'jam_kerja'=>$conn->real_escape_string($r[7] ?? ''),
                            'alamat'=>$conn->real_escape_string($r[8] ?? ''),
                            'hari'=>$conn->real_escape_string($r[9] ?? ''),
                            'tgl_mou'=>$conn->real_escape_string(nd($r[10] ?? '')),
                            'tempat_lahir'=>$conn->real_escape_string($r[11] ?? ''),
                            'tanggal_lahir'=>$conn->real_escape_string(nd($r[12] ?? '')),
                            'unit_kerja'=>$conn->real_escape_string($r[13] ?? ''),
                            'gaji_pokok'=>(float)($r[14] ?? 0),
                            'tunjangan_jabatan'=>(float)($r[15] ?? 0),
                            'tunjangan_transport'=>(float)($r[16] ?? 0),
                            'tunjangan_kinerja'=>(float)($r[17] ?? 0),
                            'tunjangan_fungsional'=>(float)($r[18] ?? 0),
                            'thp'=>(float)($r[19] ?? 0),
                            'terbilang'=>$conn->real_escape_string($r[20] ?? ''),
                            'tgl_mulai'=>$conn->real_escape_string(nd($r[21] ?? '')),
                            'berlaku'=>$conn->real_escape_string($r[22] ?? ''),
                            'tanggal_akhir'=>$conn->real_escape_string(nd($r[23] ?? '')),
                            'saksi1'=>$conn->real_escape_string($r[24] ?? ''),
                            'saksi2'=>$conn->real_escape_string($r[25] ?? ''),
                        ];
                        if (trim($d['nama']) === '') { $errs[] = 'Baris ' . ($i+1) . ': Nama kosong'; $sk++; continue; }
                        $p = [];
                        foreach($d as $k => $v) { $p[] = chr(96) . $k . chr(96) . '=' . chr(39) . $v . chr(39); }
                        $sql = 'INSERT INTO data_mou SET ' . implode(',', $p);
                        if ($conn->query($sql)) { $ins++; } else { $errs[] = 'Baris ' . ($i+1) . ': Gagal'; $sk++; }
                    }
                    $msg = 'Import selesai! <strong>' . $ins . '</strong> data ditambahkan';
                    if ($sk > 0) $msg .= ', <strong>' . $sk . '</strong> dilewati';
                    if ($errs) $msg .= '<br><small>' . implode('<br>', array_slice($errs,0,5)) . '</small>';
                    $msg_type = $ins > 0 ? 'success' : 'warning';
                }
            } catch (Exception $e) { $msg = 'Error: ' . $e->getMessage(); $msg_type = 'danger'; }
        }
    }
}
function nd($v) {
    $v = trim($v);
    if ($v === '' || $v === '-' || $v === '0000-00-00') return '';
    if (is_numeric($v) && $v > 25569) return gmdate('Y-m-d', ($v - 25569) * 86400);
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})\$#', $v, $m)) return $m[3].'-'.str_pad($m[2],2,'0',STR_PAD_LEFT).'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);
    if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{4})\$#', $v, $m)) return $m[3].'-'.str_pad($m[2],2,'0',STR_PAD_LEFT).'-'.str_pad($m[1],2,'0',STR_PAD_LEFT);
    return $v;
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Import Data MOU</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>body{background:#f4f6f9;}.sidebar{background:linear-gradient(180deg,#2c3e50 0%,#1a252f 100%);min-height:100vh;padding:20px;color:#fff;}.sidebar a{color:#fff;text-decoration:none;display:block;padding:10px 15px;margin:5px 0;border-radius:5px;}.sidebar a:hover{background:#34495e;}.sidebar a.active{background:#3498db;}.sidebar .section-title{color:#7f8c8d;font-size:.75rem;text-transform:uppercase;padding:15px 15px 4px 15px;}.sidebar .section-divider{border-color:#7f8c8d;margin:5px 15px;opacity:.4;}.sidebar .brand{margin-bottom:20px;padding-bottom:10px;border-bottom:1px solid #34495e;}</style>
</head><body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><div class="sidebar">
<div class="brand"><h4><i class="bi bi-building"></i> SIPS</h4><small>Sistem Informasi Pegawai</small></div>
<a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
<div class="section-title">Pengajuan</div><hr class="section-divider">
<a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
<div class="section-title">Admin</div><hr class="section-divider">
<a href="profile_pegawai.php"><i class="bi bi-person-badge"></i> Profile Pegawai</a>
<a href="setup_users.php"><i class="bi bi-file-earmark-spreadsheet"></i> Manajemen User</a>
<a href="data_mou.php" class="active"><i class="bi bi-file-earmark-ruled"></i> Data MOU</a>
<a href="data_sk.php"><i class="bi bi-file-earmark-text"></i> Data SK</a>
<div class="section-title">Penilaian Kinerja</div><hr class="section-divider">
<a href="kinerja_status.php"><i class="bi bi-person-check"></i> Status</a>
<a href="kinerja_periode.php"><i class="bi bi-calendar3"></i> Periode</a>
<a href="kinerja_pejabat.php"><i class="bi bi-award"></i> Pejabat</a>
<a href="kinerja_bahan.php"><i class="bi bi-file-earmark-text"></i> Bahan Penilaian</a>
<a href="form_penilaian.php"><i class="bi bi-clipboard-check"></i> Form Penilaian</a>
<a href="laporan_penilaian.php"><i class="bi bi-bar-chart"></i> Laporan</a>
<a href="login.php" style="color:#e74c3c;margin-top:30px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div></div>
<div class="col-md-10 p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h3><i class="bi bi-upload"></i> Import Data MOU</h3><a href="data_mou.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a></div>
<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<div class="card shadow-sm mb-3"><div class="card-body"><h5 class="card-title"><i class="bi bi-info-circle text-primary"></i> Petunjuk Import</h5><ol class="mb-3"><li>Download <a href="?template=1" class="fw-bold text-decoration-none">Template Excel</a> terlebih dahulu</li><li>Isi data sesuai format kolom (header di baris 1)</li><li>Format tanggal: <code>YYYY-MM-DD</code> atau <code>DD/MM/YYYY</code></li><li>Format angka: tanpa titik/koma (contoh: <code>3000000</code> untuk 3 juta)</li><li>Kolom wajib: <strong>No. SK</strong> dan <strong>Nama</strong></li><li>Upload file Excel (.xls / .xlsx) atau CSV</li></ol>
<form method="POST" enctype="multipart/form-data" class="row g-2 align-items-end"><div class="col-md-8"><label class="form-label">File Excel / CSV</label><input type="file" name="file_excel" accept=".xls,.xlsx,.csv" class="form-control" required></div><div class="col-md-4 d-flex gap-2"><button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-upload"></i> Upload &amp; Import</button><a href="?template=1" class="btn btn-success"><i class="bi bi-download"></i> Template</a></div></form></div></div>
<div class="card shadow-sm"><div class="card-header bg-light"><strong>Urutan Kolom di Excel</strong> (header baris 1)</div><div class="card-body"><code style="font-size:12px;">No. SK | No. Tambahan | Status Kepegawaian | Status Detail | Nama | Gelar | Hari Kerja | Jam Kerja | Alamat | Hari | Tanggal MOU | Tempat Lahir | Tanggal Lahir | Unit Kerja | Gaji Pokok | Tunjangan Jabatan | Tunjangan Transport | Tunjangan Kinerja | Tunjangan Fungsional | THP | Terbilang | Tanggal Mulai | Berlaku | Tanggal Akhir | Saksi 1 | Saksi 2</code></div></div>
</div></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
