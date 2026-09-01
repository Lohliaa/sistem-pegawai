<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle tambah data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $tanggal_tmt = $_POST['tanggal_tmt'];
    $unit = $_POST['unit'];
    
    $query = "INSERT INTO sk (nama, tanggal_lahir, tanggal_tmt, unit, created_by) 
              VALUES ('$nama', '$tanggal_lahir', '$tanggal_tmt', '$unit', $user_id)";
    $conn->query($query);
    header('Location: ajuan_sk.php');
    exit();
}

// Handle hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $conn->query("DELETE FROM sk WHERE id=$id AND created_by=$user_id");
    header('Location: ajuan_sk.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajuan SK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar" style="background:#2c3e50;min-height:100vh;padding:20px;">
                <h4 class="text-white mb-4">Sistem Pegawai</h4>
                <a href="index.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-house"></i> Dashboard</a>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="profile_pegawai.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-person-badge"></i> Profile Pegawai</a>
                <a href="pengajuan_admin.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
                <?php elseif ($_SESSION['role'] == 'staf'): ?>
                <a href="pengajuan_staf.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-file-earmark-text"></i> Pengajuan</a>
                <?php elseif ($_SESSION['role'] == 'kanit' || $_SESSION['role'] == 'kabid'): ?>
                <a href="persetujuan_kanit.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-check-circle"></i> Persetujuan Pengajuan</a>
                <?php endif; ?>
                <a href="ajuan_sk.php" class="text-white" style="display:block;padding:10px 15px;background:#3498db;border-radius:5px;text-decoration:none;"><i class="bi bi-file-earmark-text"></i> Ajuan SK</a>
                <a href="ajuan_sk.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-file-earmark-check"></i> Ajuan SK</a>
                <a href="logout.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
            
            <div class="col-md-10 p-4">
                <h2>Ajuan SK</h2>
                <hr>
                
                <?php if ($role == 'staf' || $role == 'admin'): ?>
                <!-- Form Tambah -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5>Tambah Ajuan SK</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Nama</label>
                                    <input type="text" name="nama" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Tanggal TMT</label>
                                    <input type="date" name="tanggal_tmt" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit</label>
                                    <select name="unit" class="form-control" required>
                                        <option value="SD">SD</option>
                                        <option value="SMP">SMP</option>
                                        <option value="SMA">SMA</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label>
                                    <button type="submit" name="tambah" class="btn btn-success w-100">Tambah</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Daftar Ajuan -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>Daftar Ajuan SK</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Tanggal Lahir</th>
                                    <th>TMT</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <?php if ($role == 'staf' || $role == 'admin'): ?>
                                    <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $where = ($role == 'staf' || $role == 'admin') ? "WHERE created_by=$user_id" : "";
                                $query = "SELECT * FROM sk $where ORDER BY created_at DESC";
                                $result = $conn->query($query);
                                while($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['nama'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal_lahir'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal_tmt'])) ?></td>
                                    <td><?= $row['unit'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'disetujui_kanit' ? 'info' : ($row['status'] == 'disetujui_kabid' ? 'success' : 'danger')) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <?php if ($role == 'staf' || $role == 'admin'): ?>
                                    <td>
                                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>