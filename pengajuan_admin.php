<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle ubah status menjadi processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses'])) {
    $id = (int)$_POST['id'];
    
    $query = "UPDATE pengajuan SET 
              status='processing',
              processed_by=$user_id,
              processed_date=NOW()
              WHERE id=$id AND status='approved_kanit'";
    
    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Pengajuan mulai diproses!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    }
    header('Location: pengajuan_admin.php');
    exit();
}

// Handle selesaikan pengajuan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selesai'])) {
    $id = (int)$_POST['id'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');
    
    $query = "UPDATE pengajuan SET 
              status='completed',
              completed_by=$user_id,
              completed_date=NOW(),
              catatan_admin='$catatan'
              WHERE id=$id AND status='processing'";
    
    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Pengajuan berhasil diselesaikan!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    }
    header('Location: pengajuan_admin.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pengajuan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            width: 200px;
            left: 0;
        }
        .sidebar h4 {
            color: white;
            margin-bottom: 20px;
        }
        .sidebar a {
            color: white;
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .sidebar a:hover {
            background: #34495e;
        }
        .sidebar a.active {
            background: #3498db;
        }
        .main-content {
            margin-left: 200px;
            padding: 30px;
            background: #f4f6f9;
            min-height: 100vh;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4><i class="bi bi-building"></i> SIPS</h4>
        <a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="profile_pegawai.php"><i class="bi bi-people"></i> Profile Pegawai</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'staf'): ?>
        <a href="pengajuan_staf.php"><i class="bi bi-file-earmark-text"></i> Pengajuan</a>
        <?php elseif ($_SESSION['role'] == 'kanit' || $_SESSION['role'] == 'kabid'): ?>
        <a href="persetujuan_kanit.php"><i class="bi bi-check-circle"></i> Persetujuan Pengajuan</a>
        <?php elseif ($_SESSION['role'] == 'admin'): ?>
        <a href="pengajuan_admin.php" class="active"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
        <a href="setup_users.php"><i class="bi bi-people-gear"></i> Manajemen User</a>
        <?php endif; ?>
        <a href="ajuan_mou.php"><i class="bi bi-file-text"></i> <span>Ajuan MoU</span></a>
        <a href="ajuan_sk.php"><i class="bi bi-file-check"></i> <span>Ajuan SK</span></a>
        <?php if ($_SESSION['role'] == 'kanit' || $_SESSION['role'] == 'kabid'): ?>
        <a href="approval.php"><i class="bi bi-check2-circle"></i> <span>Persetujuan</span></a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="laporan.php"><i class="bi bi-file-earmark-spreadsheet"></i> <span>Laporan</span></a>
        <?php endif; ?>
        <a href="logout.php" style="margin-top: 20px; color: #e74c3c;"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text text-primary"></i> Manajemen Pengajuan</h2>
            <span class="badge bg-danger p-2">
                <i class="bi bi-person-badge"></i> ADMIN
            </span>
        </div>

        <!-- Alert -->
        <?php if (isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?= $_SESSION['alert_icon'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <strong><?= $_SESSION['alert_title'] ?>!</strong> <?= $_SESSION['alert_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_icon']);
        unset($_SESSION['alert_title']);
        endif; 
        ?>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-label">Menunggu Approve</div>
                    <div class="stat-number">
                        <?= $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE status='pending'")->fetch_assoc()['total'] ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-label">Disetujui</div>
                    <div class="stat-number" style="color: #27ae60;">
                        <?= $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE status='approved_kanit'")->fetch_assoc()['total'] ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-label">Sedang Diproses</div>
                    <div class="stat-number" style="color: #f39c12;">
                        <?= $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE status='processing'")->fetch_assoc()['total'] ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-label">Selesai</div>
                    <div class="stat-number" style="color: #2ecc71;">
                        <?= $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE status='completed'")->fetch_assoc()['total'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-approved">Siap Diproses</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-processing">Sedang Diproses</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-all">Semua Riwayat</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab: Siap Diproses -->
            <div id="tab-approved" class="tab-pane fade show active">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-check-circle"></i> Pengajuan Siap Diproses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Pengaju</th>
                                        <th>Tipe</th>
                                        <th>Nama</th>
                                        <th>Unit</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Persetujuan Kanit</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query = "SELECT p.*, u.username as nama_pengaju, uk.username as nama_kanit FROM pengajuan p 
                                              LEFT JOIN users u ON p.created_by = u.id
                                              LEFT JOIN users uk ON p.approved_by_kanit = uk.id
                                              WHERE p.status='approved_kanit' 
                                              ORDER BY p.approved_date_kanit DESC";
                                    $result = $conn->query($query);
                                    
                                    if ($result->num_rows > 0):
                                        while($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= htmlspecialchars($row['unit']) ?></td>
                                        <td><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                                        <td>
                                            <small><strong><?= htmlspecialchars($row['nama_kanit'] ?? 'N/A') ?></strong><br>
                                            <?= $row['approved_date_kanit'] ? date('d/m/Y', strtotime($row['approved_date_kanit'])) : '-' ?><br>
                                            <em><?= htmlspecialchars($row['catatan_kanit'] ?? '') ?></em></small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalProses<?= $row['id'] ?>">
                                                <i class="bi bi-play-circle"></i> Proses
                                            </button>

                                            <!-- Modal Proses -->
                                            <div class="modal fade" id="modalProses<?= $row['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title">Mulai Proses Pengajuan</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                            <div class="modal-body">
                                                                <p>Pengajuan dari <strong><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></strong> akan mulai diproses.</p>
                                                                <p>Pastikan semua dokumen sudah lengkap sebelum memproses.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="proses" class="btn btn-primary">
                                                                    <i class="bi bi-play-circle"></i> Mulai Proses
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            Tidak ada pengajuan yang siap diproses
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Sedang Diproses -->
            <div id="tab-processing" class="tab-pane fade">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Pengajuan Sedang Diproses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Pengaju</th>
                                        <th>Tipe</th>
                                        <th>Nama</th>
                                        <th>Unit</th>
                                        <th>Tanggal Proses</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query = "SELECT p.*, u.username as nama_pengaju FROM pengajuan p 
                                              LEFT JOIN users u ON p.created_by = u.id
                                              WHERE p.status='processing' 
                                              ORDER BY p.processed_date DESC";
                                    $result = $conn->query($query);
                                    
                                    if ($result->num_rows > 0):
                                        while($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= htmlspecialchars($row['unit']) ?></td>
                                        <td><?= $row['processed_date'] ? date('d/m/Y H:i', strtotime($row['processed_date'])) : '-' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalSelesai<?= $row['id'] ?>">
                                                <i class="bi bi-check-circle"></i> Selesai
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal Selesai -->
                                    <div class="modal fade" id="modalSelesai<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title">Selesaikan Pengajuan</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <div class="modal-body">
                                                        <label class="form-label">Catatan Penyelesaian</label>
                                                        <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan penyelesaian..." required></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="selesai" class="btn btn-success">
                                                            <i class="bi bi-check-circle"></i> Selesaikan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Tidak ada pengajuan yang sedang diproses
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Semua Riwayat -->
            <div id="tab-all" class="tab-pane fade">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Semua Riwayat Pengajuan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="tableHistory">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Pengaju</th>
                                        <th>Tipe</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th>Persetujuan</th>
                                        <th>Diproses</th>
                                        <th>Selesai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query = "SELECT p.*, u.username as nama_pengaju FROM pengajuan p 
                                              LEFT JOIN users u ON p.created_by = u.id
                                              ORDER BY p.created_at DESC";
                                    $result = $conn->query($query);
                                    
                                    while($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['tipe_pengajuan']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $row['status'] == 'pending' ? 'warning' : 
                                                ($row['status'] == 'approved_kanit' ? 'success' : 
                                                ($row['status'] == 'processing' ? 'info' : 
                                                ($row['status'] == 'completed' ? 'success' : 'danger')))
                                            ?>">
                                                <?= $row['status'] == 'pending' ? 'Menunggu Approve' : 
                                                    ($row['status'] == 'approved_kanit' ? 'Disetujui' : 
                                                    ($row['status'] == 'processing' ? 'Diproses' : 
                                                    ($row['status'] == 'completed' ? 'Selesai' : 'Ditolak')))
                                                ?>
                                            </span>
                                        </td>
                                        <td><?= $row['created_at'] ? date('d/m/Y', strtotime($row['created_at'])) : '-' ?></td>
                                        <td><?= $row['approved_date_kanit'] ? date('d/m/Y', strtotime($row['approved_date_kanit'])) : '-' ?></td>
                                        <td><?= $row['processed_date'] ? date('d/m/Y', strtotime($row['processed_date'])) : '-' ?></td>
                                        <td><?= $row['completed_date'] ? date('d/m/Y', strtotime($row['completed_date'])) : '-' ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tableHistory').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                }
            });
        });
    </script>
</body>
</html>
