<?php
session_start();
require_once 'config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];

// Ambil data identitas berdasarkan user yang login
// Query ini akan mencari data pegawai berdasarkan user_id atau username
$identity_result = $conn->query("
    SELECT 
        p.*, 
        u.username, 
        u.role AS akun_role 
    FROM users u 
    LEFT JOIN pegawai p ON p.user_id = u.id 
    WHERE u.id = $user_id 
    LIMIT 1
");

if ($identity_result && $identity_result->num_rows > 0) {
    $identity = $identity_result->fetch_assoc();
} else {
    // Jika tidak ada data di tabel pegawai, ambil dari users saja
    $identity_result = $conn->query("SELECT * FROM users WHERE id = $user_id LIMIT 1");
    $identity = $identity_result ? $identity_result->fetch_assoc() : null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengajuan MoU & SK Pegawai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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
        .content {
            padding: 20px;
        }
        .role-badge {
            position: fixed;
            top: 10px;
            right: 20px;
            z-index: 1000;
        }
        .status-pending { color: #f39c12; }
        .status-disetujui_kanit { color: #3498db; }
        .status-disetujui_kabid { color: #27ae60; }
        .status-ditolak { color: #e74c3c; }
        .card-identity {
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="role-badge">
        <span class="badge bg-info">Role: <?= strtoupper($user_role) ?></span>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h4 class="text-white mb-4">Sistem Pegawai</h4>
                <a href="index.php" class="active"><i class="bi bi-house"></i> Dashboard</a>
                <?php if ($user_role == 'admin'): ?>
                <a href="profile_pegawai.php"><i class="bi bi-person-badge"></i> Profile Pegawai</a>
                <?php endif; ?>
                <?php if ($user_role == 'staf'): ?>
                <a href="pengajuan_staf.php"><i class="bi bi-file-earmark-text"></i> Pengajuan</a>
                <a href="daftar_pengajuan.php"><i class="bi bi-list-check"></i> Daftar Pengajuan Saya</a>
                <?php elseif ($user_role == 'kanit' || $user_role == 'kabid'): ?>
                <a href="persetujuan_kanit.php"><i class="bi bi-check-circle"></i> Persetujuan Pengajuan</a>
                <?php elseif ($user_role == 'admin'): ?>
                <a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
                <a href="setup_users.php"><i class="bi bi-people-gear"></i> Manajemen User</a>
                <?php endif; ?>
                <?php if ($user_role == 'kanit' || $user_role == 'kabid'): ?>
                    <a href="approval.php"><i class="bi bi-check2-circle"></i> Persetujuan</a>
                <?php endif; ?>
                <?php if ($user_role == 'admin'): ?>
                    <a href="laporan.php"><i class="bi bi-file-earmark-spreadsheet"></i> Laporan</a>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2>Dashboard</h2>
                <hr>

                <!-- Identitas Diri -->
                <div class="card mb-4 shadow-sm card-identity">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Identitas Diri - <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($identity): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <strong>Nama</strong>
                                <br><?= htmlspecialchars($identity['nama'] ?? $identity['username'] ?? '-') ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Tempat, Tanggal Lahir</strong>
                                <br><?= htmlspecialchars($identity['tempat'] ?? '-') ?>, <?= !empty($identity['tanggal_lahir']) ? date('d/m/Y', strtotime($identity['tanggal_lahir'])) : '-' ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Alamat</strong>
                                <br><?= htmlspecialchars($identity['alamat'] ?? '-') ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Jabatan</strong>
                                <br><?= htmlspecialchars($identity['jabatan'] ?? '-') ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Status Kepegawaian</strong>
                                <br><?= htmlspecialchars($identity['status_kepegawaian'] ?? '-') ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Golongan</strong>
                                <br><?= htmlspecialchars($identity['golongan'] ?? '-') ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Masa Kerja</strong>
                                <br><?= htmlspecialchars($identity['masa_kerja'] ?? '-') ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Unit</strong>
                                <br><?= htmlspecialchars($identity['unit'] ?? '-') ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Role</strong>
                                <br><span class="badge bg-info"><?= htmlspecialchars(strtoupper($user_role)) ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Username</strong>
                                <br><?= htmlspecialchars($identity['username'] ?? $_SESSION['username']) ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Data pegawai belum terhubung dengan akun ini. Silakan hubungi admin untuk mengatur data pegawai.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user_role == 'admin'): ?>
                    <!-- Admin Dashboard -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Total Pegawai</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $result = $conn->query("SELECT COUNT(*) as total FROM pegawai");
                                        $row = $result->fetch_assoc();
                                        echo $row['total'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">MoU Pending</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $result = $conn->query("SELECT COUNT(*) as total FROM mou WHERE status='pending'");
                                        $row = $result->fetch_assoc();
                                        echo $row['total'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">SK Pending</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $result = $conn->query("SELECT COUNT(*) as total FROM sk WHERE status='pending'");
                                        $row = $result->fetch_assoc();
                                        echo $row['total'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Total User</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $result = $conn->query("SELECT COUNT(*) as total FROM users");
                                        $row = $result->fetch_assoc();
                                        echo $row['total'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data MoU dan SK Terbaru -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5>Data MoU Terbaru</h5>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Unit</th>
                                                <th>TMT</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT * FROM mou ORDER BY created_at DESC LIMIT 10";
                                            $result = $conn->query($query);
                                            while($row = $result->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($row['tanggal_tmt'])) ?></td>
                                                <td><span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'disetujui_kanit' ? 'info' : ($row['status'] == 'disetujui_kabid' ? 'success' : 'danger')) ?>"><?= $row['status'] ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5>Data SK Terbaru</h5>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Unit</th>
                                                <th>TMT</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT * FROM sk ORDER BY created_at DESC LIMIT 10";
                                            $result = $conn->query($query);
                                            while($row = $result->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($row['tanggal_tmt'])) ?></td>
                                                <td><span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'disetujui_kanit' ? 'info' : ($row['status'] == 'disetujui_kabid' ? 'success' : 'danger')) ?>"><?= $row['status'] ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($user_role == 'kanit' || $user_role == 'kabid'): ?>
                    <!-- Kanit/Kabid Dashboard -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h5>MoU Perlu Persetujuan</h5>
                                </div>
                                <div class="card-body">
                                    <h3>
                                        <?php
                                        $status = $user_role == 'kanit' ? 'pending' : 'disetujui_kanit';
                                        $result = $conn->query("SELECT COUNT(*) as total FROM mou WHERE status='$status'");
                                        $row = $result->fetch_assoc();
                                        echo $row['total'];
                                        ?>
                                    </h3>
                                    <a href="approval.php" class="btn btn-primary">Lihat & Setujui</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info">
                                    <h5>SK Perlu Persetujuan</h5>
                                </div>
                                <div class="card-body">
                                    <h3>
                                        <?php
                                        $status = $user_role == 'kanit' ? 'pending' : 'disetujui_kanit';
                                        $result = $conn->query("SELECT COUNT(*) as total FROM sk WHERE status='$status'");
                                        $row = $result->fetch_assoc();
                                        echo $row['total'];
                                        ?>
                                    </h3>
                                    <a href="approval.php" class="btn btn-primary">Lihat & Setujui</a>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($user_role == 'staf'): ?>
                    <!-- Staf Dashboard -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5>Pengajuan Saya</h5>
                                </div>
                                <div class="card-body">
                                    <h3>
                                        <?php
                                        $user_id = $_SESSION['user_id'];
                                        $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE created_by=$user_id");
                                        $row = $result->fetch_assoc();
                                        echo $row['total'];
                                        ?>
                                    </h3>
                                    <a href="pengajuan_staf.php" class="btn btn-primary">Lihat Pengajuan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>