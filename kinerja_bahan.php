<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$can_crud = in_array($_SESSION['role'], ['admin', 'kabid', 'kanit']);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_crud) {
    $nb = mysqli_real_escape_string($conn, $_POST['nama_bahan']);
    $lk = mysqli_real_escape_string($conn, $_POST['link']);
    $kt = mysqli_real_escape_string($conn, $_POST['keterangan']);
    if (isset($_POST['tambah'])) {
        $conn->query("INSERT INTO bahan_penilaian (nama_bahan, link, keterangan) VALUES ('$nb','$lk','$kt')");
        $_SESSION['msg'] = "Bahan ditambah!";
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE bahan_penilaian SET nama_bahan='$nb', link='$lk', keterangan='$kt' WHERE id=$id");
        $_SESSION['msg'] = "Bahan diupdate!";
    }
    header('Location: kinerja_bahan.php');
    exit();
}
if (isset($_GET['hapus']) && $can_crud) {
    $conn->query("DELETE FROM bahan_penilaian WHERE id=" . (int)$_GET['hapus']);
    $_SESSION['msg'] = "Dihapus!";
    header('Location: kinerja_bahan.php');
    exit();
}
$res = $conn->query("SELECT * FROM bahan_penilaian ORDER BY id DESC");
$list = [];
while ($r = $res->fetch_assoc()) $list[] = $r;
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
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
            <div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
                <h2><i class="bi bi-file-earmark-text"></i> Manajemen Bahan Penilaian</h2>
                <hr>
                <?php if (isset($_SESSION['msg'])) {
                    echo "<script>Swal.fire({title:'Berhasil!',text:'" . $_SESSION['msg'] . "',icon:'success'});</script>";
                    unset($_SESSION['msg']);
                } ?>
                <div class="card">
                    <div class="card-header bg-info text-white d-flex justify-content-between">
                        <h5 class="mb-0">Daftar Bahan Penilaian</h5>
                        <?php if (!$can_crud): ?><span class="badge bg-secondary"><i class="bi bi-eye"></i> Read Only</span><?php endif; ?>
                        <?php if ($can_crud): ?><button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#add"><i class="bi bi-plus"></i> Tambah</button><?php endif; ?>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="tbl">
                            <thead class="table-dark">
                                <tr><th>No</th><th>Nama Bahan</th><th>Link</th><th>Keterangan</th><th>Aksi</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="add">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Tambah Bahan</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-2"><label>Nama Bahan</label><input type="text" name="nama_bahan" class="form-control" placeholder="Contoh: SKP 2026" required></div>
                        <div class="mb-2"><label>Link</label><input type="url" name="link" class="form-control" placeholder="https://..." required></div>
                        <div class="mb-2"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan"></textarea></div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="tambah" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            $('#tbl').DataTable();
        });
    </script>
</body>

</html>
<style>
    .sidebar {
        min-height: 100vh;
        background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
        padding: 20px;
    }

    .sidebar a {
        color: white;
        text-decoration: none;
        display: block;
        padding: 10px 15px;
        margin: 5px 0;
        border-radius: 5px;
    }

    .sidebar a:hover {
        background: #34495e;
    }

    .sidebar a.active {
        background: #3498db;
    }
</style>
</head>
