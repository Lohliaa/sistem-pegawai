<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role']!='admin') { header('Location: login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $ns = mysqli_real_escape_string($conn, $_POST['nama_status']);
    if (isset($_POST['tambah'])) {
        $c = $conn->query("SELECT id FROM status_kepegawaian WHERE nama_status='$ns'");
        if ($c->num_rows > 0) { $_SESSION['alert_message']="Status sudah ada!"; $_SESSION['alert_icon']="error"; $_SESSION['alert_title']="Gagal!"; }
        else { $conn->query("INSERT INTO status_kepegawaian (nama_status) VALUES ('$ns')"); $_SESSION['alert_message']="Status ditambah!"; $_SESSION['alert_icon']="success"; $_SESSION['alert_title']="Berhasil!"; }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE status_kepegawaian SET nama_status='$ns' WHERE id=$id");
        $_SESSION['alert_message']="Status diupdate!"; $_SESSION['alert_icon']="success"; $_SESSION['alert_title']="Berhasil!";
    }
    header('Location: kinerja_status.php'); exit();
}
if (isset($_GET['hapus'])) {
    $conn->query("DELETE FROM status_kepegawaian WHERE id=".(int)$_GET['hapus']);
    $_SESSION['alert_message']="Dihapus!"; $_SESSION['alert_icon']="success"; $_SESSION['alert_title']="Berhasil!";
    header('Location: kinerja_status.php'); exit();
}
$res = $conn->query("SELECT * FROM status_kepegawaian ORDER BY id");
$list = [];
while($r=$res->fetch_assoc()) $list[]=$r;
$current_page = 'kinerja_status.php';

?>
<!DOCTYPE html>
<html>
<head>
<title>Manajemen Status</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
<h2><i class="bi bi-person-check"></i> Manajemen Status Kepegawaian</h2>
<hr>
<?php if (isset($_SESSION['alert_message'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){Swal.fire({title:'<?= $_SESSION['alert_title'] ?>',text:'<?= $_SESSION['alert_message'] ?>',icon:'<?= $_SESSION['alert_icon'] ?>'});});</script>
<?php unset($_SESSION['alert_message'],$_SESSION['alert_icon'],$_SESSION['alert_title']); endif; ?>
<div class="card shadow-sm">
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
<h5 class="mb-0"><i class="bi bi-list"></i> Daftar Status</h5>
<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#tambahModal"><i class="bi bi-plus-circle"></i> Tambah</button>
</div>
<div class="card-body table-responsive">
<table class="table table-striped" id="tableStatus">
<thead class="table-dark"><tr><th class="text-center">No</th><th>Nama Status</th><th class="text-center">Aksi</th></tr></thead>
<tbody>
<?php foreach($list as $i=>$s): ?>
<tr>
<td class="text-center"><?= $i+1 ?></td>
<td><?= htmlspecialchars($s['nama_status']) ?></td>
<td class="text-center">
<button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?= $s['id'] ?>"><i class="bi bi-pencil"></i></button>
<a href="?hapus=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
</td>
</tr>
<div class="modal fade" id="edit<?= $s['id'] ?>" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header"><h5>Edit Status</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="id" value="<?= $s['id'] ?>">
<label>Nama Status</label>
<input type="text" name="nama_status" class="form-control" value="<?= htmlspecialchars($s['nama_status']) ?>" required>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit" class="btn btn-primary">Simpan</button></div>
</form>
</div>
</div>
</div>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<div class="modal fade" id="tambahModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header"><h5>Tambah Status</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST">
<div class="modal-body">
<label>Nama Status</label>
<input type="text" name="nama_status" class="form-control" placeholder="Contoh: Mitra" required>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" name="tambah" class="btn btn-primary">Simpan</button></div>
</form>
</div>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>$(document).ready(function(){$('#tableStatus').DataTable({"language":{"url":"//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"}});});</script>
</body>
</html>
<div class="brand mb-4">
<h4 style="color:#fff;"><i class="bi bi-building"></i> SIPS</h4>
<small style="color:#fff;">Sistem Informasi Pegawai</small>
</div>
<a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
<a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
<a href="profile_pegawai.php"><i class="bi bi-person-badge"></i> Profile Pegawai</a>
<a href="setup_users.php"><i class="bi bi-file-earmark-spreadsheet"></i> Manajemen User</a>
<div class="mt-4 mb-2">
<span style="color:#7f8c8d;font-size:0.8rem;text-transform:uppercase;">Penilaian Kinerja</span>
<hr style="border-color:#7f8c8d;margin:5px 0;">
</div>
<a href="kinerja_status.php" class="active"><i class="bi bi-person-check"></i> Status</a>
<a href="kinerja_periode.php"><i class="bi bi-calendar3"></i> Periode</a>
<a href="kinerja_pejab