<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['role'];

if ($user_role != 'kanit' && $user_role != 'kabid') {
    header('Location: index.php');
    exit();
}

// Handle approval
if (isset($_GET['type']) && isset($_GET['id']) && isset($_GET['action'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    $action = $_GET['action'];
    $status = '';
    
    if ($user_role == 'kanit') {
        $status = ($action == 'approve') ? 'disetujui_kanit' : 'ditolak';
    } else if ($user_role == 'kabid') {
        $status = ($action == 'approve') ? 'disetujui_kabid' : 'ditolak';
    }
    
    $table = ($type == 'mou') ? 'mou' : 'sk';
    $conn->query("UPDATE $table SET status='$status' WHERE id=$id");
    header('Location: approval.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approval MoU & SK</title>
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
                <a href="persetujuan_kanit.php" class="text-white" style="display:block;padding:10px 15px;background:#3498db;border-radius:5px;text-decoration:none;"><i class="bi bi-check-circle"></i> Persetujuan Pengajuan</a>
                <?php endif; ?>
                <a href="approval.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-check2-circle"></i> Persetujuan</a>
                <a href="logout.php" class="text-white" style="display:block;padding:10px 15px;text-decoration:none;"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
            
            <div class="col-md-10 p-4">
                <h2>Persetujuan MoU & SK</h2>
                <hr>
                
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#mou">MoU</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sk">SK</a>
                    </li>
                </ul>
                
                <div class="tab-content mt-3">
                    <!-- MoU Tab -->
                    <div class="tab-pane active" id="mou">
                        <div class="card">
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama</th>
                                            <th>TMT</th>
                                            <th>Unit</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $status = ($user_role == 'kanit') ? 'pending' : 'disetujui_kanit';
                                        $query = "SELECT * FROM mou WHERE status='$status'";
                                        $result = $conn->query($query);
                                        while($row = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $row['nama'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_tmt'])) ?></td>
                                            <td><?= $row['unit'] ?></td>
                                            <td><span class="badge bg-warning"><?= $row['status'] ?></span></td>
                                            <td>
                                                <a href="?type=mou&id=<?= $row['id'] ?>&action=approve" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check"></i> Setuju
                                                </a>
                                                <a href="?type=mou&id=<?= $row['id'] ?>&action=reject" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-x"></i> Tolak
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SK Tab -->
                    <div class="tab-pane" id="sk">
                        <div class="card">
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama</th>
                                            <th>TMT</th>
                                            <th>Unit</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $status = ($user_role == 'kanit') ? 'pending' : 'disetujui_kanit';
                                        $query = "SELECT * FROM sk WHERE status='$status'";
                                        $result = $conn->query($query);
                                        while($row = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $row['nama'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($row['tanggal_tmt'])) ?></td>
                                            <td><?= $row['unit'] ?></td>
                                            <td><span class="badge bg-warning"><?= $row['status'] ?></span></td>
                                            <td>
                                                <a href="?type=sk&id=<?= $row['id'] ?>&action=approve" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check"></i> Setuju
                                                </a>
                                                <a href="?type=sk&id=<?= $row['id'] ?>&action=reject" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-x"></i> Tolak
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>