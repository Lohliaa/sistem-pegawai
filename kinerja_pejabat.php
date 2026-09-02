<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role']!='admin') { header('Location: login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $pid = (int)$_POST['pegawai_id'];
    if (isset($_POST['tambah'])) {
        // Ambil data pegawai berdasarkan id
        $peg = $conn->query("SELECT nama, jabatan, unit FROM pegawai WHERE id=$pid")->fetch_assoc();
        if (!$peg) {
            $_SESSION['msg']="Pegawai tidak ditemukan!";
        } else {
            $nm = mysqli_real_escape_string($conn, $peg['nama']);
            $jb = mysqli_real_escape_string($conn, $peg['jabatan']);
            $un = mysqli_real_escape_string($conn, $peg['unit']);
            // Cek apakah pejabat sudah ada untuk pegawai ini
            $cek = $conn->query("SELECT id FROM pejabat_penilai WHERE pegawai_id=$pid");
            if ($cek->num_rows > 0) {
                $_SESSION['msg']="Pegawai ini sudah terdaftar sebagai pejabat!";
            } else {
                $conn->query("INSERT INTO pejabat_penilai (pegawai_id, nama, jabatan, unit) VALUES ($pid,'$nm','$jb','$un')");
                $_SESSION['msg']="Pejabat ditambah!"; 
            }
        }
    }
    header('Location: kinerja_pejabat.php'); exit();
}
if (isset($_GET['hapus'])) {
    $conn->query("DELETE FROM pejabat_penilai WHERE id=".(int)$_GET['hapus']);
    $_SESSION['msg']="Dihapus!"; header('Location: kinerja_pejabat.php'); exit();
}
$res = $conn->query("SELECT jp.*, p.status_kepegawaian FROM pejabat_penilai jp LEFT JOIN pegawai p ON jp.pegawai_id = p.id ORDER BY jp.id DESC");
$list = []; while($r=$res->fetch_assoc()) $list[]=$r;
$current_page = 'kinerja_pejabat.php';

?>
<!DOCTYPE html>
<html>
<head>
<title>Pejabat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
<h2><i class="bi bi-award"></i> Manajemen Pejabat Penilai</h2>
<hr>
<?php if(isset($_SESSION['msg'])){echo "<script>Swal.fire({title:'Berhasil!',text:'".$_SESSION['msg']."',icon:'success'});</script>"; unset($_SESSION['msg']);} ?>
<div class="card">
<div class="card-header bg-success text-white d-flex justify-content-between">
<h5 class="mb-0">Daftar Pejabat</h5>
<button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#add"><i class="bi bi-plus"></i> Tambah</button>
</div>
<div class="card-body">
<table class="table table-striped" id="tbl">
<thead class="table-dark"><tr><th>No</th><th>Nama</th><th>Jabatan</th><th>Unit</th><th>Status</th><th>Aksi</th></tr></thead>
<tbody>
<?php foreach($list as $i=>$p): ?>
<tr><td><?= $i+1 ?></td><td><?= htmlspecialchars($p['nama']) ?></td><td><?= htmlspecialchars($p['jabatan']) ?></td><td><?= htmlspecialchars($p['unit']) ?></td><td>
<span class="badge bg-info"><?= htmlspecialchars($p['status_kepegawaian'] ?? '-') ?></span>
</td><td>
<button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#e<?= $p['id'] ?>"><i class="bi bi-eye"></i></button>
<a href="?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?');"><i class="bi bi-trash"></i></a>
</td></tr>
<div class="modal fade" id="e<?= $p['id'] ?>"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5>Detail Pejabat</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="alert alert-secondary py-2 small"><i class="bi bi-person-badge"></i> <strong><?= htmlspecialchars($p['nama']) ?></strong> — <?= htmlspecialchars($p['jabatan']) ?> (<?= htmlspecialchars($p['unit']) ?>)</div>
<div class="mb-2"><label>Status Kepegawaian</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($p['status_kepegawaian'] ?? '-') ?>" readonly>
</div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
</div></div></div>
<?php endforeach; ?>
</tbody></table></div></div></div></div></div>
<div class="modal fade" id="add"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5>Tambah Pejabat</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST">
<div class="modal-body">
<div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> Pilih pegawai yang memiliki role <strong>Kanit</strong> atau <strong>Kabid</strong> di Profile Pegawai.</div>
<div class="mb-2"><label>Pilih Pegawai <span class="text-danger">*</span></label>
<select name="pegawai_id" id="pegawai_id_add" class="form-select" required>
<option value="">-- Pilih Pegawai (Kanit/Kabid) --</option>
<?php 
$peg_add = $conn->query("SELECT id, nama, jabatan, unit, status_kepegawaian FROM pegawai WHERE role IN ('kanit','kabid') ORDER BY nama");
while($p = $peg_add->fetch_assoc()): ?>
<option value="<?= $p['id'] ?>" data-nama="<?= htmlspecialchars($p['nama']) ?>" data-jabatan="<?= htmlspecialchars($p['jabatan']) ?>" data-unit="<?= htmlspecialchars($p['unit']) ?>" data-status="<?= htmlspecialchars($p['status_kepegawaian']) ?>">
<?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['jabatan']) ?> (<?= htmlspecialchars($p['unit']) ?>)
</option>
<?php endwhile; ?>
</select>
</div>
<div class="mb-2"><label>Nama</label><input type="text" id="nama_add" class="form-control" readonly placeholder="Otomatis terisi"></div>
<div class="mb-2"><label>Jabatan</label><input type="text" id="jabatan_add" class="form-control" readonly placeholder="Otomatis terisi"></div>
<div class="mb-2"><label>Unit</label><input type="text" id="unit_add" class="form-control" readonly placeholder="Otomatis terisi"></div>
<div class="mb-2"><label>Status Kepegawaian</label><input type="text" id="status_add" class="form-control" readonly placeholder="Otomatis terisi"></div>
</div>
<div class="modal-footer"><button type="submit" name="tambah" class="btn btn-primary">Simpan</button></div>
</form>
</div></div></div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function(){
    $('#tbl').DataTable();
    // Auto-fill nama, jabatan, unit, status saat pilih pegawai di modal tambah
    $('#pegawai_id_add').change(function(){
        var opt = $(this).find('option:selected');
        $('#nama_add').val(opt.data('nama') || '');
        $('#jabatan_add').val(opt.data('jabatan') || '');
        $('#unit_add').val(opt.data('unit') || '');
        $('#status_add').val(opt.data('status') || '');
    });
});
</script>
</body></html>
