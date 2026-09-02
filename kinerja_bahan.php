<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role']!='admin') { header('Location: login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $nb = mysqli_real_escape_string($conn, $_POST['nama_bahan']);
    $lk = mysqli_real_escape_string($conn, $_POST['link']);
    $kt = mysqli_real_escape_string($conn, $_POST['keterangan']);
    if (isset($_POST['tambah'])) {
        $conn->query("INSERT INTO bahan_penilaian (nama_bahan, link, keterangan) VALUES ('$nb','$lk','$kt')");
        $_SESSION['msg']="Bahan ditambah!"; 
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE bahan_penilaian SET nama_bahan='$nb', link='$lk', keterangan='$kt' WHERE id=$id");
        $_SESSION['msg']="Bahan diupdate!";
    }
    header('Location: kinerja_bahan.php'); exit();
}
if (isset($_GET['hapus'])) {
    $conn->query("DELETE FROM bahan_penilaian WHERE id=".(int)$_GET['hapus']);
    $_SESSION['msg']="Dihapus!"; header('Location: kinerja_bahan.php'); exit();
}
$res = $conn->query("SELECT * FROM bahan_penilaian ORDER BY id DESC");
$list = []; while($r=$res->fetch_assoc()) $list[]=$r;
$current_page = 'kinerja_bahan.php';

?>
<!DOCTYPE html>
<html>
<head>
<title>Bahan Penilai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
<h2><i class="bi bi-file-earmark-text"></i> Manajemen Bahan Penilaian</h2>
<hr>
<?php if(isset($_SESSION['msg'])){echo "<script>Swal.fire({title:'Berhasil!',text:'".$_SESSION['msg']."',icon:'success'});</script>"; unset($_SESSION['msg']);} ?>
<div class="card">
<div class="card-header bg-info text-white d-flex justify-content-between">
<h5 class="mb-0">Daftar Bahan Penilaian</h5>
<button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#add"><i class="bi bi-plus"></i> Tambah</button>
</div>
<div class="card-body">
<table class="table table-striped" id="tbl">
<thead class="table-dark"><tr><th>No</th><th>Nama Bahan</th><th>Link</th><th>Keterangan</th><th>Aksi</th></tr></thead>
<tbody>
<?php foreach($list as $i=>$b): ?>
<tr><td><?= $i+1 ?></td><td><?= $b['nama_bahan'] ?></td>
<td><a href="<?= $b['link'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-link-45deg"></i> Buka</a></td>
<td><?= $b['keterangan'] ?></td>
<td>
<button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#e<?= $b['id'] ?>"><i class="bi bi-pencil"></i></button>
<a href="?hapus=<?= $b['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
</td></tr>
<div class="modal fade" id="e<?= $b['id'] ?>"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5>Edit Bahan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST">
<div class="modal-body">
<input type="hidden" name="id" value="<?= $b['id'] ?>">
<div class="mb-2"><label>Nama</label><input type="text" name="nama_bahan" class="form-control" value="<?= $b['nama_bahan'] ?>" required></div>
<div class="mb-2"><label>Link</label><input type="url" name="link" class="form-control" value="<?= $b['link'] ?>" required></div>
<div class="mb-2"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="3"><?= $b['keterangan'] ?></textarea></div>
</div>
<div class="modal-footer"><button type="submit" name="edit" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<?php endforeach; ?>
</tbody></table></div></div></div></div></div>
<div class="modal fade" id="add"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5>Tambah Bahan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST">
<div class="modal-body">
<div class="mb-2"><label>Nama Bahan</label><input type="text" name="nama_bahan" class="form-control" placeholder="Contoh: SKP 2026" required></div>
<div class="mb-2"><label>Link</label><input type="url" name="link" class="form-control" placeholder="https://..." required></div>
<div class="mb-2"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan"></textarea></div>
</div>
<div class="modal-footer"><button type="submit" name="tambah" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>$(function(){$('#tbl').DataTable();});</script>
</body></html>
<style>
.sidebar{min-height:100vh;background:linear-gradient(180deg,#2c3e50 0%,#1a252f 100%);padding:20px;}
.sidebar a{color:white;text-decoration:none;display:block;padding:10px 15px;margin:5px 0;border-radius:5px;}
.sidebar a:hover{background:#34495e;}
.sidebar a.active{background:#3498db;}
</style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 sidebar">
<h4 style="color:#fff;"><i class="bi bi-building"></i> SIPS</h4>
<a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
<a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
<a href="profile_pegawai.php"><i class="bi bi-person-badge"></i> Profile Pegawai</a>
<a href="setup_users.php"><i class="bi bi-file-earmark-spreadsheet"></i> Manajemen User</a>
<div class="mt-4 mb-2"><span style="color:#7f8c8d;font-size:0.8rem;text-transform:uppercase;">Penilaian Kinerja</span><hr style="border-color:#7f8c8d;margin:5px 0;"></div>
<a href="kinerja_status.php"><i class="bi bi-person-check"></i> Status</a>
<a href="kinerja_periode.php"><i class="bi bi-calendar3"></i> Periode</a>
<a href="kinerja_pejab