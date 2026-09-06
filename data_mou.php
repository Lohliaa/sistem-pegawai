<?php
// =============================================================
//  AJAX endpoint - HARUS paling atas, sebelum output HTML
// =============================================================
session_start();
require_once 'config/database.php';

if (isset($_GET['get']) && is_numeric($_GET['get'])) {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'unauthorized']); exit(); }
    $gid = (int)$_GET['get'];
    $gr = $conn->query("SELECT * FROM data_mou WHERE id=$gid");
    if ($gr && $gr->num_rows > 0) { echo json_encode($gr->fetch_assoc()); } else { echo json_encode(['error' => 'notfound']); }
    exit();
}

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SESSION['role'] != 'admin') { header('Location: index.php'); exit(); }

$msg = '';
$edit = null;

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = ['no_sk','no_tambahan','status_kepegawaian','status_detail','nama','gelar','hari_kerja','jam_kerja','alamat','hari','tgl_mou','tempat_lahir','tanggal_lahir','unit_kerja','gaji_pokok','tunjangan_jabatan','tunjangan_transport','tunjangan_kinerja','tunjangan_fungsional','thp','terbilang','tgl_mulai','berlaku','tanggal_akhir','saksi1','saksi2'];
    $set = [];
    foreach ($fields as $f) {
        $v = mysqli_real_escape_string($conn, $_POST[$f] ?? '');
        $set[] = "`$f`='$v'";
    }
    $set_str = implode(',', $set);
    if (isset($_POST['simpan'])) {
        $conn->query("INSERT INTO data_mou SET $set_str");
        $msg = 'Data MOU berhasil ditambahkan!';
    } elseif (isset($_POST['update'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE data_mou SET $set_str WHERE id=$id");
        $msg = 'Data MOU berhasil diupdate!';
    }
    header('Location: data_mou.php?msg=' . urlencode($msg));
    exit();
}

// DELETE
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $conn->query('DELETE FROM data_mou WHERE id=' . (int)$_GET['hapus']);
    header('Location: data_mou.php?msg=' . urlencode('Data MOU berhasil dihapus!'));
    exit();
}

$res = $conn->query('SELECT * FROM data_mou ORDER BY id ASC');
$list = [];
while ($r = $res->fetch_assoc()) $list[] = $r;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $er = $conn->query('SELECT * FROM data_mou WHERE id=' . (int)$_GET['edit']);
    if ($er && $er->num_rows > 0) $edit = $er->fetch_assoc();
}

if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);
// Fetch data status kepegawaian untuk dropdown
$statusList = [];
$rs = $conn->query("SELECT * FROM status_kepegawaian ORDER BY nama_status");
while ($sr = $rs->fetch_assoc()) $statusList[] = $sr;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data MOU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .sidebar{background:linear-gradient(180deg,#2c3e50 0%,#1a252f 100%);min-height:100vh;padding:20px;color:#fff;}
        .sidebar a{color:#fff;text-decoration:none;display:block;padding:10px 15px;margin:5px 0;border-radius:5px;}
        .sidebar a:hover{background:#34495e;}.sidebar a.active{background:#3498db;}
        .sidebar .section-title{color:#7f8c8d;font-size:0.75rem;text-transform:uppercase;padding:15px 15px 4px 15px;}
        .sidebar .section-divider{border-color:#7f8c8d;margin:5px 15px;opacity:0.4;}
        .sidebar .brand{margin-bottom:20px;padding-bottom:10px;border-bottom:1px solid #34495e;}
        .sidebar .brand h4{color:#fff;margin-bottom:4px;}.sidebar .brand small{color:#bdc3c7;}
        .table-container{background:#fff;border-radius:8px;padding:15px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
        .modal .form-control,.modal .form-select{font-size:13px;}
        .modal .form-label{font-size:12px;font-weight:600;margin-bottom:2px;color:#34495e;}
        .modal .required:after{content:" *";color:red;}
        /* Modal scrollable - body scroll, footer sticky */
        #modalForm .modal-body{max-height:65vh;overflow-y:auto;padding:15px 20px;box-sizing:border-box;}
        #modalForm .modal-footer{position:sticky;bottom:0;background:#f8f9fa;border-top:1px solid #dee2e6;padding:12px 20px;z-index:5;flex-shrink:0;}
        #modalForm .modal-content{max-height:90vh;overflow:hidden;}
        #modalForm .modal-dialog-scrollable .modal-body::-webkit-scrollbar{width:6px;}
        #modalForm .modal-dialog-scrollable .modal-body::-webkit-scrollbar-thumb{background:#ccc;border-radius:3px;}
        .btn-sm{font-size:11px;padding:2px 5px;}
        /* Compact table - sejajarkan thead & tbody border */
        #tblMou{font-size:11px;table-layout:fixed;width:100%;border-collapse:collapse;border-spacing:0;}
        #tblMou thead th{font-size:10px;font-weight:600;text-transform:uppercase;white-space:nowrap;padding:7px 6px;text-align:center;vertical-align:middle;border:1px solid #2c3e50;background:#2c3e50;color:#fff;box-sizing:border-box;}
        #tblMou tbody td{padding:5px 6px;vertical-align:middle;line-height:1.3;border:1px solid #dee2e6;font-size:11px;box-sizing:border-box;}
        #tblMou tbody tr:nth-child(odd)>td{background:#fafbfc;}
        #tblMou tbody tr:nth-child(even)>td{background:#ffffff;}
        #tblMou tbody tr:hover>td{background:#fff8e1 !important;}
        #tblMou .aksi-btns a{margin:0 1px;display:inline-block;}
        /* Pastikan garis header & isi lurus */
        #tblMou th,#tblMou td{box-sizing:border-box;}
        .dataTables_wrapper{font-size:11px;}
        .dataTables_wrapper .dataTables_filter input,.dataTables_wrapper .dataTables_length select{font-size:11px;padding:2px 6px;}
        .dataTables_wrapper .dataTables_filter,.dataTables_wrapper .dataTables_length{margin-bottom:10px;}
    </style>
</head>
<body>
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
<div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
<h2><i class="bi bi-file-earmark-ruled"></i> Manajemen Data MOU</h2>
<p class="text-muted">Kelola data Memorandum of Understanding (MOU) pegawai.</p>
<hr>
<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
<i class="bi bi-check-circle"></i> <?= $msg ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<div class="card shadow-sm">
<div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
<h5 class="mb-0"><i class="bi bi-list"></i> Daftar Data MOU</h5>
<div class="d-flex gap-2">
<a href="export_mou.php" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Export Excel</a>
<a href="import_mou.php" class="btn btn-info btn-sm"><i class="bi bi-upload"></i> Import Excel</a>
<button type="button" class="btn btn-primary btn-sm" id="btnTambah"><i class="bi bi-plus-circle"></i> Tambah</button>
</div>
</div>
<div class="card-body">
<div class="table-responsive">
<table id="tblMou" class="table table-hover table-sm" style="width:100%;table-layout:fixed;border-collapse:collapse;">
<thead>
<tr>
<th style="width:35px;text-align:center;">No</th>
<th style="width:85px;">No. SK</th>
<th style="width:85px;">No. Tambahan</th>
<th style="width:70px;text-align:center;">Status</th>
<th style="width:90px;">Status Detail</th>
<th style="width:130px;">Nama</th>
<th style="width:70px;">Gelar</th>
<th style="width:75px;">Hari Kerja</th>
<th style="width:75px;">Jam Kerja</th>
<th style="width:160px;">Alamat</th>
<th style="width:60px;">Hari</th>
<th style="width:85px;">Tgl MOU</th>
<th style="width:90px;">Tempat Lahir</th>
<th style="width:85px;">Tgl Lahir</th>
<th style="width:100px;">Unit Kerja</th>
<th style="width:95px;text-align:right;">Gaji Pokok</th>
<th style="width:95px;text-align:right;">Tunj. Jabatan</th>
<th style="width:95px;text-align:right;">Tunj. Transport</th>
<th style="width:95px;text-align:right;">Tunj. Kinerja</th>
<th style="width:95px;text-align:right;">Tunj. Fungsional</th>
<th style="width:95px;text-align:right;">THP</th>
<th style="width:150px;">Terbilang</th>
<th style="width:85px;">Tgl Mulai</th>
<th style="width:75px;">Berlaku</th>
<th style="width:85px;">Tgl Akhir</th>
<th style="width:90px;">Saksi 1</th>
<th style="width:90px;">Saksi 2</th>
<th style="width:65px;text-align:center;">Aksi</th>
</tr></thead>
<tbody>
<?php foreach ($list as $i => $row): ?>
<tr>
<td style="width:35px;text-align:center;"><?= $i + 1 ?></td>
<td style="width:85px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['no_sk']) ?></td>
<td style="width:85px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['no_tambahan']) ?></td>
<td style="width:70px;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['status_kepegawaian']) ?></td>
<td style="width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['status_detail']) ?></td>
<td style="width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['nama']) ?></td>
<td style="width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['gelar']) ?></td>
<td style="width:75px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['hari_kerja']) ?></td>
<td style="width:75px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['jam_kerja']) ?></td>
<td style="width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['alamat']) ?></td>
<td style="width:60px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['hari']) ?></td>
<td style="width:85px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['tgl_mou']) ?></td>
<td style="width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['tempat_lahir']) ?></td>
<td style="width:85px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['tanggal_lahir']) ?></td>
<td style="width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['unit_kerja']) ?></td>
<td style="width:95px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= number_format($row['gaji_pokok'] ?? 0, 0, ',', '.') ?></td>
<td style="width:95px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= number_format($row['tunjangan_jabatan'] ?? 0, 0, ',', '.') ?></td>
<td style="width:95px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= number_format($row['tunjangan_transport'] ?? 0, 0, ',', '.') ?></td>
<td style="width:95px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= number_format($row['tunjangan_kinerja'] ?? 0, 0, ',', '.') ?></td>
<td style="width:95px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= number_format($row['tunjangan_fungsional'] ?? 0, 0, ',', '.') ?></td>
<td style="width:95px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= number_format($row['thp'] ?? 0, 0, ',', '.') ?></td>
<td style="width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['terbilang']) ?></td>
<td style="width:85px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['tgl_mulai']) ?></td>
<td style="width:75px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['berlaku']) ?></td>
<td style="width:85px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['tanggal_akhir']) ?></td>
<td style="width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['saksi1']) ?></td>
<td style="width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['saksi2']) ?></td>
<td style="width:65px;text-align:center;white-space:nowrap;" class="aksi-btns">
<a href="javascript:void(0)" class="btn btn-sm btn-warning" onclick="openEdit(<?= $row['id'] ?>)" title="Edit"><i class="bi bi-pencil"></i></a>
<a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')" title="Hapus"><i class="bi bi-trash"></i></a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div></div></div></div></div>
<!-- Modal Form Tambah / Edit -->
<div class="modal fade" id="modalForm" tabindex="-1" data-bs-backdrop="static">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header bg-primary text-white">
<h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-circle"></i> Tambah Data MOU</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<form method="POST" id="frmMou" autocomplete="off">
<div class="modal-body">
<input type="hidden" name="id" id="f_id" value="">

<!-- Section 1: Identitas Pegawai -->
<h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-person-badge"></i> Identitas Pegawai</h6>
<div class="row g-2 mb-3">
<div class="col-md-3"><label class="form-label required">No. SK</label><input type="text" name="no_sk" id="f_no_sk" class="form-control form-control-sm" required></div>
<div class="col-md-3"><label class="form-label">No. Tambahan</label><input type="text" name="no_tambahan" id="f_no_tambahan" class="form-control form-control-sm"></div>
<div class="col-md-3"><label class="form-label">Status Kepegawaian</label><select name="status_kepegawaian" id="f_status_kepegawaian" class="form-select form-select-sm"><option value="">-- Pilih --</option><?php foreach($statusList as $s): ?><option value="<?= htmlspecialchars($s['nama_status']) ?>"><?= htmlspecialchars($s['nama_status']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Status Detail</label><input type="text" name="status_detail" id="f_status_detail" class="form-control form-control-sm" placeholder="PKWTT, PKHL, dll"></div>
<div class="col-md-5"><label class="form-label required">Nama Lengkap</label><input type="text" name="nama" id="f_nama" class="form-control form-control-sm" required></div>
<div class="col-md-2"><label class="form-label">Gelar</label><input type="text" name="gelar" id="f_gelar" class="form-control form-control-sm" placeholder="S.Pd., M.Pd."></div>
<div class="col-md-3"><label class="form-label">Tempat Lahir</label><input type="text" name="tempat_lahir" id="f_tempat_lahir" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">Tanggal Lahir</label><input type="date" name="tanggal_lahir" id="f_tanggal_lahir" class="form-control form-control-sm"></div>
<div class="col-md-12"><label class="form-label">Alamat</label><input type="text" name="alamat" id="f_alamat" class="form-control form-control-sm" placeholder="Alamat lengkap"></div>
<div class="col-md-6"><label class="form-label">Unit Kerja</label><input type="text" name="unit_kerja" id="f_unit_kerja" class="form-control form-control-sm" placeholder="Yayasan / Sekolah / Kantor"></div>
</div>

<!-- Section 2: Jadwal Kerja & MOU -->
<h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-calendar3-range"></i> Jadwal Kerja &amp; Informasi MOU</h6>
<div class="row g-2 mb-3">
<div class="col-md-2"><label class="form-label">Hari Kerja</label><input type="text" name="hari_kerja" id="f_hari_kerja" class="form-control form-control-sm" placeholder="Senin-Jumat"></div>
<div class="col-md-2"><label class="form-label">Jam Kerja</label><input type="text" name="jam_kerja" id="f_jam_kerja" class="form-control form-control-sm" placeholder="08.00-16.00"></div>
<div class="col-md-2"><label class="form-label">Hari MOU</label><input type="text" name="hari" id="f_hari" class="form-control form-control-sm" placeholder="Senin"></div>
<div class="col-md-2"><label class="form-label">Tanggal MOU</label><input type="date" name="tgl_mou" id="f_tgl_mou" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">Tanggal Mulai</label><input type="date" name="tgl_mulai" id="f_tgl_mulai" class="form-control form-control-sm"></div>
<div class="col-md-2"><label class="form-label">Berlaku</label><input type="text" name="berlaku" id="f_berlaku" class="form-control form-control-sm" placeholder="1 Tahun"></div>
<div class="col-md-2"><label class="form-label">Tanggal Akhir</label><input type="date" name="tanggal_akhir" id="f_tanggal_akhir" class="form-control form-control-sm"></div>
</div>

<!-- Section 3: Gaji & Tunjangan -->
<h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-cash-stack"></i> Gaji &amp; Tunjangan (Rp)</h6>
<div class="row g-2 mb-3">
<div class="col-md-4"><label class="form-label">Gaji Pokok</label><input type="number" name="gaji_pokok" id="f_gaji_pokok" class="form-control form-control-sm" oninput="recalcTHP()" min="0"></div>
<div class="col-md-4"><label class="form-label">Tunjangan Jabatan</label><input type="number" name="tunjangan_jabatan" id="f_tunjangan_jabatan" class="form-control form-control-sm" oninput="recalcTHP()" min="0"></div>
<div class="col-md-4"><label class="form-label">Tunjangan Transport</label><input type="number" name="tunjangan_transport" id="f_tunjangan_transport" class="form-control form-control-sm" oninput="recalcTHP()" min="0"></div>
<div class="col-md-4"><label class="form-label">Tunjangan Kinerja</label><input type="number" name="tunjangan_kinerja" id="f_tunjangan_kinerja" class="form-control form-control-sm" oninput="recalcTHP()" min="0"></div>
<div class="col-md-4"><label class="form-label">Tunjangan Fungsional</label><input type="number" name="tunjangan_fungsional" id="f_tunjangan_fungsional" class="form-control form-control-sm" oninput="recalcTHP()" min="0"></div>
<div class="col-md-4"><label class="form-label">THP (Total)</label><input type="number" name="thp" id="f_thp" class="form-control form-control-sm" style="background:#e8f5e9;font-weight:bold;" readonly></div>
<div class="col-md-12"><label class="form-label">Terbilang</label><input type="text" name="terbilang" id="f_terbilang" class="form-control form-control-sm" placeholder="Otomatis dari THP (ketik manual untuk override)"></div>
</div>

<!-- Section 4: Saksi -->
<h6 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-people"></i> Saksi</h6>
<div class="row g-2 mb-2">
<div class="col-md-6"><label class="form-label">Nama Saksi 1</label><input type="text" name="saksi1" id="f_saksi1" class="form-control form-control-sm" placeholder="Nama saksi pertama"></div>
<div class="col-md-6"><label class="form-label">Nama Saksi 2</label><input type="text" name="saksi2" id="f_saksi2" class="form-control form-control-sm" placeholder="Nama saksi kedua"></div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
<button type="submit" name="simpan" id="btnSimpan" class="btn btn-primary"><i class="bi bi-check-circle"></i> Simpan</button>
<button type="submit" name="update" id="btnUpdate" class="btn btn-success" style="display:none"><i class="bi bi-check-circle"></i> Update</button>
</div>
</form>
</div>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
    $('#tblMou').DataTable({language:{url:'//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'},pageLength:10,scrollX:true,order:[],columnDefs:[{targets:'_all',orderable:true}]});
});

// Tambah - reset form dan buka modal
$('#btnTambah').on('click', function(){
    document.getElementById('frmMou').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle"></i> Tambah Data MOU';
    document.getElementById('btnSimpan').style.display = '';
    document.getElementById('btnUpdate').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalForm')).show();
});

// Edit - fetch data dan buka modal SETELAH data terisi
function openEdit(id){
    document.getElementById('f_id').value = id;
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil"></i> Edit Data MOU';
    document.getElementById('btnSimpan').style.display = 'none';
    document.getElementById('btnUpdate').style.display = '';
    fetch('data_mou.php?get='+id)
        .then(r => r.json())
        .then(d => {
            if(d.error){ alert('Data tidak ditemukan!'); return; }
            var fields = ['no_sk','no_tambahan','status_kepegawaian','status_detail','nama','gelar','hari_kerja','jam_kerja','alamat','hari','tgl_mou','tempat_lahir','tanggal_lahir','unit_kerja','gaji_pokok','tunjangan_jabatan','tunjangan_transport','tunjangan_kinerja','tunjangan_fungsional','thp','terbilang','tgl_mulai','berlaku','tanggal_akhir','saksi1','saksi2'];
            for(var k of fields){
                var el = document.getElementById('f_'+k);
                if(el) el.value = (d[k] !== null && d[k] !== undefined && d[k] !== '0000-00-00') ? d[k] : '';
            }
            new bootstrap.Modal(document.getElementById('modalForm')).show();
        })
        .catch(err => { console.error(err); alert('Gagal mengambil data!'); });
}

// Auto hitung THP
function recalcTHP(){
    var gp = parseFloat(document.getElementById('f_gaji_pokok').value) || 0;
    var tj = parseFloat(document.getElementById('f_tunjangan_jabatan').value) || 0;
    var tt = parseFloat(document.getElementById('f_tunjangan_transport').value) || 0;
    var tk = parseFloat(document.getElementById('f_tunjangan_kinerja').value) || 0;
    var tf = parseFloat(document.getElementById('f_tunjangan_fungsional').value) || 0;
    var thp = gp + tj + tt + tk + tf;
    document.getElementById('f_thp').value = thp;
    document.getElementById('f_terbilang').value = thp > 0 ? NumberToWords(thp) : '';
}

// Number to Indonesian Words
function NumberToWords(num){
    var units = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan'];
    var tens = ['', 'Sepuluh', 'Dua Puluh', 'Tiga Puluh', 'Empat Puluh', 'Lima Puluh', 'Enam Puluh', 'Tujuh Puluh', 'Delapan Puluh', 'Sembilan Puluh'];
    var scales = ['', 'Ribu', 'Juta', 'Milyar', 'Trilyun'];
    if(num == 0) return 'Nol';
    if(num < 0) return 'Minus ' + NumberToWords(-num);
    var words = '';
    var scaleIdx = 0;
    while(num > 0){
        var chunk = num % 1000;
        if(chunk > 0){
            var chunkWords = '';
            var hundreds = Math.floor(chunk / 100);
            var tensUnits = chunk % 100;
            if(hundreds > 0){
                chunkWords += (hundreds == 1 ? 'Seratus' : units[hundreds] + ' Ratus') + ' ';
            }
            if(tensUnits > 0){
                if(tensUnits < 10){
                    chunkWords += units[tensUnits];
                } else if(tensUnits < 20){
                    chunkWords += (tensUnits == 11 ? 'Sebelas' : units[tensUnits-10] + ' Belas');
                } else {
                    chunkWords += tens[Math.floor(tensUnits/10)] + (tensUnits%10 > 0 ? ' ' + units[tensUnits%10] : '');
                }
            }
            if(scaleIdx > 0 && chunk == 1 && scaleIdx == 1) chunkWords = 'Se' + scales[scaleIdx];
            else if(chunk > 0) chunkWords += ' ' + scales[scaleIdx];
            words = chunkWords.trim() + (words ? ' ' + words : '');
        }
        num = Math.floor(num / 1000);
        scaleIdx++;
    }
    return words.trim() + ' Rupiah';
}

// Auto-open edit modal if ?edit=id in URL
<?php if($edit): ?>
$(function(){ openEdit(<?= (int)$edit['id'] ?>); });
<?php endif; ?>
</script>
</body>
</html>
