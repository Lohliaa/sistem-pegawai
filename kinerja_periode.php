<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$can_crud = in_array($_SESSION['role'], ['admin', 'kabid', 'kanit']);
if ($_SERVER['REQUEST_METHOD']=='POST' && $can_crud) {
    $_SESSION['msg'] = '';
    if (empty($_POST['nama_kuartal'])) { $_SESSION['msg'] = 'Kuartal harus dipilih'; }
    elseif (empty($_POST['periode_bulan'])) { $_SESSION['msg'] = 'Periode harus diisi'; }
    elseif (empty($_POST['tahun']) || (int)$_POST['tahun'] < 2020) { $_SESSION['msg'] = 'Tahun tidak valid'; }
    else {
        $nk = mysqli_real_escape_string($conn, $_POST['nama_kuartal']);
        $pb = mysqli_real_escape_string($conn, $_POST['periode_bulan']);
        $th = (int)$_POST['tahun'];
        if (isset($_POST['tambah'])) {
            $cek = $conn->query("SELECT id FROM periode_penilaian WHERE nama_kuartal='$nk' AND tahun=$th");
            if ($cek->num_rows > 0) { $_SESSION['msg'] = "Periode $nk Tahun $th sudah ada!"; }
            else { $conn->query("INSERT INTO periode_penilaian (nama_kuartal, periode_bulan, tahun) VALUES ('$nk','$pb',$th)"); $_SESSION['msg'] = 'Periode ditambah!'; }
        } elseif (isset($_POST['edit'])) {
            $id = (int)$_POST['id'];
            $cek = $conn->query("SELECT id FROM periode_penilaian WHERE nama_kuartal='$nk' AND tahun=$th AND id!=$id");
            if ($cek->num_rows > 0) { $_SESSION['msg'] = "Periode $nk Tahun $th sudah ada!"; }
            else { $conn->query("UPDATE periode_penilaian SET nama_kuartal='$nk', periode_bulan='$pb', tahun=$th WHERE id=$id"); $_SESSION['msg'] = 'Periode diupdate!'; }
        }
    }
    header('Location: kinerja_periode.php'); exit();
}
if (isset($_GET['hapus']) && $can_crud) {
    $conn->query('DELETE FROM periode_penilaian WHERE id='.(int)$_GET['hapus']);
    $_SESSION['msg'] = 'Dihapus!';
    header('Location: kinerja_periode.php'); exit();
}
$res = $conn->query("SELECT * FROM periode_penilaian ORDER BY tahun DESC, FIELD(nama_kuartal,'QI','Q2','Q3','Q4')");
$list = []; while($r=$res->fetch_assoc()) $list[]=$r;
$current_page = 'kinerja_periode.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Manajemen Periode</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
<h2><i class="bi bi-calendar3"></i> Manajemen Periode Penilaian</h2>
<p class="text-muted">Kelola periode penilaian kinerja per kuartal (3 bulanan).</p>
<hr>
<?php if(isset($_SESSION['msg']) && $_SESSION['msg']): ?>
<?php $isErr = strpos($_SESSION['msg'], 'sudah ada') !== false; ?>
<div class="alert alert-<?= $isErr ? 'warning' : 'success' ?> alert-dismissible fade show" role="alert">
<i class="bi bi-<?= $isErr ? 'exclamation-triangle' : 'check-circle' ?>"></i> <?= htmlspecialchars($_SESSION['msg']) ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['msg']); endif; ?>
<div class="card shadow-sm">
<div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list"></i> Daftar Periode</h5>
                        <?php if (!$can_crud): ?><span class="badge bg-secondary"><i class="bi bi-eye"></i> Read Only</span><?php endif; ?>
                        <?php if ($can_crud): ?><button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#add"><i class="bi bi-plus-circle"></i> Tambah</button><?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tbl">
                                <thead class="table-dark">
                                    <tr><th>No</th><th>Kuartal</th><th>Periode</th><th>Tahun</th><th class="text-center">Aksi</th></tr>
                                </thead>
                                <tbody></tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<div class="modal fade" id="add"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-warning text-dark"><h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Periode</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST"><div class="modal-body">
<div class="mb-3"><label class="form-label">Kuartal <span class="text-danger">*</span></label>
<select name="nama_kuartal" class="form-select" required>
<option value="">-- Pilih Kuartal --</option>
<option>Q1</option>
<option>Q2</option>
<option>Q3</option>
<option>Q4</option>
</select>
</div>
<div class="mb-3"><label class="form-label">Periode <span class="text-danger">*</span></label>
<input type="text" name="periode_bulan" class="form-control" placeholder="Contoh: Januari - Maret" required></div>
<div class="mb-3"><label class="form-label">Tahun <span class="text-danger">*</span></label>
<input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>" min="2020" max="2099" required></div>
</div>
<div class="modal-footer"><button type="submit" name="tambah" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>$(function(){ $('#tbl').DataTable(); });</script>
</body>
</html>
