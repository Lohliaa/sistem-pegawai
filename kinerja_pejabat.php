<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$can_crud = in_array($_SESSION['role'], ['admin', 'kabid', 'kanit']);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_crud) {
    $_SESSION['msg'] = '';
    $pid = (int)$_POST['pegawai_id'];
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');
    if (isset($_POST['tambah'])) {
        $cek = $conn->query("SELECT id FROM pejabat_penilai WHERE pegawai_id=$pid");
        if ($cek->num_rows > 0) { $_SESSION['msg'] = 'Pegawai sudah menjadi pejabat!'; }
        else { $conn->query("INSERT INTO pejabat_penilai (pegawai_id, keterangan) VALUES ($pid, '$ket')"); $_SESSION['msg'] = 'Berhasil ditambahkan!'; }
    }
    if (isset($_POST['edit'])) {
        $eid = (int)$_POST['edit_id'];
        $conn->query("UPDATE pejabat_penilai SET keterangan='$ket' WHERE id=$eid");
        $_SESSION['msg'] = 'Berhasil diperbarui!';
    }
    header('Location: kinerja_pejabat.php'); exit();
}
if (isset($_GET['hapus']) && $can_crud) {
    $conn->query("DELETE FROM pejabat_penilai WHERE id='".(int)$_GET['hapus']."'");
    $_SESSION['msg'] = 'Dihapus!';
    header('Location: kinerja_pejabat.php'); exit();
}
$list = [];
$res = $conn->query("SELECT pj.*, pg.nama, pg.jabatan, pg.unit FROM pejabat_penilai pj LEFT JOIN pegawai pg ON pj.pegawai_id=pg.id ORDER BY pg.nama");
if ($res) while ($r = $res->fetch_assoc()) $list[] = $r;
$peg_list = [];
$res2 = $conn->query("SELECT * FROM pegawai ORDER BY nama");
if ($res2) while ($r = $res2->fetch_assoc()) $peg_list[] = $r;
$msg = $_SESSION['msg'] ?? '';
$current_page = 'kinerja_pejabat.php';
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Pejabat Penilai</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-3" style="background:#f4f6f9;min-height:100vh;">
<h2><i class="bi bi-people"></i> Manajemen Pejabat Penilai</h2>
<p class="text-muted">Kelola pejabat penilai kinerja pegawai yang bertanggung jawab menilai.</p>
<hr>
<?php if ($msg): ?>
<div class="alert alert-<?= strpos($msg,'berhasil') !== false ? 'success' : 'warning' ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['msg']); endif; ?>

<div class="card">
    <div class="card-header bg-success text-white d-flex justify-content-between">
        <h5 class="mb-0">Daftar Pejabat Penilai</h5>
        <?php if (!$can_crud): ?><span class="badge bg-secondary"><i class="bi bi-eye"></i> Read Only</span><?php endif; ?>
        <?php if ($can_crud): ?><button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#add"><i class="bi bi-plus"></i> Tambah</button><?php endif; ?>
    </div>
    <div class="card-body">
        <table class="table table-striped" id="tbl">
            <thead class="table-dark"><tr><th>No</th><th>Nama</th><th>Jabatan</th><th>Unit</th><th>Keterangan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($list as $i => $p): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($p['nama'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['jabatan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['unit'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['keterangan'] ?? '-') ?></td>
                    <td>
                        <?php if ($can_crud): ?>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?= $p['id'] ?>"><i class="bi bi-pencil"></i></button>
                        <a href="?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                        <?php else: ?>
                        <span class="text-muted small"><i class="bi bi-eye"></i> View</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($can_crud): ?>
                <div class="modal fade" id="edit<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header"><h5 class="modal-title">Edit Pejabat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <p><strong><?= htmlspecialchars($p['nama'] ?? '-') ?></strong></p>
                                    <input type="hidden" name="edit" value="1"><input type="hidden" name="edit_id" value="<?= $p['id'] ?>">
                                    <div class="mb-3"><label class="form-label">Keterangan</label><input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($p['keterangan'] ?? '') ?>"></div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($can_crud): ?>
<div class="modal fade" id="add" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Tambah Pejabat Penilai</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="tambah" value="1">
                    <div class="mb-3"><label class="form-label">Pegawai</label><select name="pegawai_id" class="form-select" required><option value="">-- Pilih --</option><?php foreach ($peg_list as $pg): ?><option value="<?= $pg['id'] ?>"><?= htmlspecialchars($pg['nama']) ?> (<?= htmlspecialchars($pg['jabatan']) ?>)</option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label">Keterangan</label><input type="text" name="keterangan" class="form-control" placeholder="Contoh: Kabag TU"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Tambah</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>$(function(){ $('#tbl').DataTable(); });</script>
</body>
</html>