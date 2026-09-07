<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SESSION['role'] != 'admin') { header('Location: index.php'); exit(); }
use PhpOffice\PhpSpreadsheet\IOFactory;
function nd($v){$v=trim($v);if(!$v)return 'NULL';if(preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/',$v)){$p=explode('/',$v);return $p[2].'-'.str_pad($p[0],2,'0',STR_PAD_LEFT).'-'.str_pad($p[1],2,'0',STR_PAD_LEFT);}return "'".$v."'";}
$msg=''; $msg_type='';
if (isset($_GET['template'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=template_import_sk.xls');
    echo '<html><meta charset=utf-8><table border=1>';
    $h=['No. SK','No. Tambahan','Nama','Gelar','Tempat Lahir','Tanggal Lahir','NIPY','Gol/Ruang','Status Kepegawaian','Unit Kerja','TMT','Tanggal Mulai','Berlaku','Tanggal Akhir','Tanggal Ditetapkan'];
    echo '<tr style=background:#2c3e50;color:#fff;>';
    foreach($h as $x) echo '<th>'.$x.'</th>';
    echo '</tr><tr>';
    $v=['001/SK/2026','A1','Contoh Nama','S.Pd.','Surabaya','1990-01-15','NIPY001','III/A','PT','Yayasan','2026-01-01','2026-01-01','1 Tahun','2027-12-31','2026-01-01'];
    foreach($v as $x) echo '<td>'.$x.'</td>';
    echo '</tr></table></html>'; exit();
}
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_FILES['file_excel'])) {
    $file=$_FILES['file_excel'];
    if($file['error']!==UPLOAD_ERR_OK){$msg='Upload gagal!';$msg_type='danger';}
    else{
        $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,['xls','xlsx','csv'])){$msg='Format harus .xls/.xlsx/.csv';$msg_type='danger';}
        else{
            try{
                require_once __DIR__.'/vendor/autoload.php';
                $handle=fopen($file['tmp_name'],'rb');
                $bytes=fread($handle,8);
                fclose($handle);
                if(substr($bytes,0,2)==='PK'){$reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();}
                elseif(substr($bytes,0,4)==="\xD0\xCF\x11\xE0"){$reader=new \PhpOffice\PhpSpreadsheet\Reader\Xls();}
                else{$reader=new \PhpOffice\PhpSpreadsheet\Reader\Csv();$reader->setInputEncoding('UTF-8');}
                $spreadsheet=$reader->load($file['tmp_name']);
                $sheet=$spreadsheet->getActiveSheet();
                $rows=$sheet->toArray();
                if(count($rows)<2){$msg='File kosong!';$msg_type='warning';}
                else{
                    $ins=0;$sk=0;$errs=[];
                    for($i=1;$i<count($rows);$i++){
                        $r=$rows[$i];
                        $empty=true;for($j=0;$j<3;$j++){if(trim($r[$j]??'')!==''){$empty=false;break;}}
                        if($empty){$sk++;continue;}
                        $d=[
                            'no_sk'=>$conn->real_escape_string($r[0]??''),
                            'no_tambahan'=>$conn->real_escape_string($r[1]??''),
                            'nama'=>$conn->real_escape_string($r[2]??''),
                            'gelar'=>$conn->real_escape_string($r[3]??''),
                            'tempat_lahir'=>$conn->real_escape_string($r[4]??''),
                            'tanggal_lahir'=>$conn->real_escape_string(nd($r[5]??'')),
                            'nipy'=>$conn->real_escape_string($r[6]??''),
                            'gol_ruang'=>$conn->real_escape_string($r[7]??''),
                            'status_kepegawaian'=>$conn->real_escape_string($r[8]??''),
                            'unit_kerja'=>$conn->real_escape_string($r[9]??''),
                            'tmt'=>$conn->real_escape_string(nd($r[10]??'')),
                            'tanggal_mulai'=>$conn->real_escape_string(nd($r[11]??'')),
                            'berlaku'=>$conn->real_escape_string($r[12]??''),
                            'tanggal_akhir'=>$conn->real_escape_string(nd($r[13]??'')),
                            'tanggal_ditetapkan'=>$conn->real_escape_string(nd($r[14]??'')),
                        ];
                        $flds=array_keys($d);$vals=array_values($d);
                        $sql="INSERT INTO data_sk (".implode(',',$flds).") VALUES ('".implode("','",$vals)."')";
                        if($conn->query($sql))$ins++;else $errs[]=$conn->error;
                    }
                    $msg="Import selesai: $ins data masuk, $sk baris kosong dilewati".(count($errs)?' ('.count($errs).' error)':'');
                    $msg_type='success';
                }
            }catch(Exception $e){$msg='Error: '.$e->getMessage();$msg_type='danger';}
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Import Data SK</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}body{background:#f0f2f5;font-family:'Segoe UI',Arial,sans-serif;}
.sidebar{width:220px;background:#1a252f;color:#fff;position:fixed;height:100vh;overflow-y:auto;}
.sidebar h4{padding:15px 20px;margin:0;background:#0d1925;font-size:15px;}
.sidebar a{display:block;padding:9px 20px;color:#b8c7d9;text-decoration:none;font-size:12px;}
.sidebar a:hover,.sidebar a.active{background:#2c3e50;color:#fff;}
.sidebar a i{margin-right:8px;}
.sidebar .section-title{color:#7f8c8d;font-size:10px;font-weight:700;text-transform:uppercase;padding:15px 15px 4px 15px;}
.sidebar .section-divider{border-color:#7f8c8d;margin:5px 15px;opacity:.4;}
.sidebar .brand{padding:15px 20px 10px;border-bottom:1px solid #34495e;}
.main{margin-left:220px;padding:20px;}
.btn-sm{font-size:11px;}
@media(max-width:768px){.sidebar{width:60px;}.main{margin-left:60px;}.sidebar h4,.sidebar a span{display:none;}.sidebar a{text-align:center;padding:12px;}}
</style>
</head>
<body>
<div class="col-md-2 p-0"><div class="sidebar">
<div class="brand"><h4><i class="bi bi-building"></i> SIPS</h4><small>Sistem Informasi Pegawai</small></div>
<a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
<div class="section-title">Pengajuan</div><hr class="section-divider">
<a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
<div class="section-title">Admin</div><hr class="section-divider">
<a href="profile_pegawai.php"><i class="bi bi-person-badge"></i> Profile Pegawai</a>
<a href="setup_users.php"><i class="bi bi-file-earmark-spreadsheet"></i> Manajemen User</a>
<a href="data_mou.php"><i class="bi bi-file-earmark-ruled"></i> Data MOU</a>
<a href="data_sk.php" class="active"><i class="bi bi-file-earmark-text"></i> Data SK</a>
<div class="section-title">Penilaian Kinerja</div><hr class="section-divider">
<a href="kinerja_status.php"><i class="bi bi-person-check"></i> Status</a>
<a href="kinerja_periode.php"><i class="bi bi-calendar3"></i> Periode</a>
<a href="kinerja_pejabat.php"><i class="bi bi-award"></i> Pejabat</a>
<a href="kinerja_bahan.php"><i class="bi bi-file-earmark-text"></i> Bahan Penilaian</a>
<a href="form_penilaian.php"><i class="bi bi-clipboard-check"></i> Form Penilaian</a>
<a href="laporan_penilaian.php"><i class="bi bi-bar-chart"></i> Laporan</a>
<a href="login.php" style="color:#e74c3c;margin-top:30px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div></div>
<div class="main">
<div class="d-flex justify-content-between align-items-center mb-3">
<h3><i class="bi bi-upload"></i> Import Data SK</h3>
<a href="data_sk.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<?php if($msg): ?>
<div class="alert alert-<?=$msg_type?> alert-dismissible fade show"><?=$msg?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<div class="card shadow-sm mb-3">
<div class="card-body">
<h5 class="card-title"><i class="bi bi-info-circle text-primary"></i> Petunjuk Import</h5>
<ol class="mb-3">
<li>Download <a href="?template=1" class="fw-bold text-decoration-none">Template Excel</a> terlebih dahulu</li>
<li>Isi data sesuai format kolom (header di baris 1)</li>
<li>Format tanggal: <code>YYYY-MM-DD</code> atau <code>DD/MM/YYYY</code></li>
<li>Kolom wajib: <strong>No. SK</strong> dan <strong>Nama</strong></li>
<li>Upload file Excel (.xls/.xlsx) atau CSV</li>
</ol>
<form method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
<div class="col-md-8"><label class="form-label">File Excel / CSV</label><input type="file" name="file_excel" accept=".xls,.xlsx,.csv" class="form-control" required></div>
<div class="col-md-4 d-flex gap-2"><button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-upload"></i> Upload &amp; Import</button><a href="?template=1" class="btn btn-success"><i class="bi bi-download"></i> Template</a></div>
</form>
</div>
</div>
<div class="card shadow-sm">
<div class="card-header bg-light"><strong>Urutan Kolom di Excel</strong> (header baris 1)</div>
<div class="card-body"><code style="font-size:12px;">No. SK | No. Tambahan | Nama | Gelar | Tempat Lahir | Tanggal Lahir | NIPY | Gol/Ruang | Status Kepegawaian | Unit Kerja | TMT | Tanggal Mulai | Berlaku | Tanggal Akhir | Tanggal Ditetapkan</code></div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


