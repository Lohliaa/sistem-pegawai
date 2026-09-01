<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] != 'kanit' && $_SESSION['role'] != 'kabid') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Ambil data user
$user_query = $conn->query("SELECT * FROM users WHERE id=$user_id");
$user_data = $user_query->fetch_assoc();

// Handle approve pengajuan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve'])) {
    $id = (int)$_POST['id'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');
    
    $query = "UPDATE pengajuan SET 
              status='approved_kanit',
              approved_by_kanit=$user_id,
              approved_date_kanit=NOW(),
              catatan_kanit='$catatan'
              WHERE id=$id AND status='pending'";
    
    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Pengajuan berhasil disetujui!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Gagal menyetujui pengajuan: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }
    header('Location: persetujuan_kanit.php');
    exit();
}

// Handle tolak pengajuan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tolak'])) {
    $id = (int)$_POST['id'];
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan'] ?? '');
    
    $query = "UPDATE pengajuan SET 
              status='rejected',
              rejected_by_kanit=$user_id,
              rejected_reason='$alasan',
              rejected_date_kanit=NOW()
              WHERE id=$id AND status='pending'";
    
    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Pengajuan berhasil ditolak!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Gagal menolak pengajuan: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }
    header('Location: persetujuan_kanit.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Persetujuan Pengajuan - Kanit/Kabid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4><i class="bi bi-building"></i> SIPS</h4>
        <a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
        <a href="persetujuan_kanit.php" class="active"><i class="bi bi-check-circle"></i> Persetujuan</a>
        <a href="logout.php" style="margin-top: 20px; color: #e74c3c;"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-check-circle text-primary"></i> Persetujuan Pengajuan</h2>
            <span class="badge bg-warning p-2">
                <i class="bi bi-person-badge"></i> <?= strtoupper($role) ?>
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

        <!-- Pengajuan Menunggu Persetujuan -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Pengajuan Menunggu Persetujuan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Pengaju</th>
                                <th>Tipe</th>
                                <th>Unit</th>
                                <th>Tanggal Dibuat</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = "SELECT p.*, u.username as nama_pengaju FROM pengajuan p 
                                      LEFT JOIN users u ON p.created_by = u.id 
                                      WHERE p.status='pending' 
                                      ORDER BY p.created_at DESC";
                            $result = $conn->query($query);
                            
                            if ($result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></td>
                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                <td><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                                <td><?= htmlspecialchars(substr($row['keterangan'] ?? '', 0, 30)) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id'] ?>">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Detail & Approve -->
                            <div class="modal fade" id="modalDetail<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">Detail Pengajuan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Nama Pengaju</strong></label>
                                                    <p><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Tipe Pengajuan</strong></label>
                                                    <p><?= htmlspecialchars($row['tipe_pengajuan']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Nama</strong></label>
                                                    <p><?= htmlspecialchars($row['nama']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Unit</strong></label>
                                                    <p><?= htmlspecialchars($row['unit']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Tanggal Lahir</strong></label>
                                                    <p><?= $row['tanggal_lahir'] ? date('d/m/Y', strtotime($row['tanggal_lahir'])) : '-' ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><strong>Tanggal TMT</strong></label>
                                                    <p><?= $row['tanggal_tmt'] ? date('d/m/Y', strtotime($row['tanggal_tmt'])) : '-' ?></p>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label"><strong>Keterangan</strong></label>
                                                    <p><?= htmlspecialchars($row['keterangan']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSetujui<?= $row['id'] ?>" data-bs-dismiss="modal">
                                                <i class="bi bi-check-circle"></i> Setujui
                                            </button>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalTolak<?= $row['id'] ?>" data-bs-dismiss="modal">
                                                <i class="bi bi-x-circle"></i> Tolak
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Setujui -->
                            <div class="modal fade" id="modalSetujui<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Setujui Pengajuan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="modal-body">
                                                <label class="form-label">Catatan (Opsional)</label>
                                                <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan..."></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="approve" class="btn btn-success">
                                                    <i class="bi bi-check-circle"></i> Setujui
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Tolak -->
                            <div class="modal fade" id="modalTolak<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Tolak Pengajuan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="modal-body">
                                                <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                <textarea name="alasan" class="form-control" rows="3" placeholder="Jelaskan alasan penolakan..." required></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="tolak" class="btn btn-danger">
                                                    <i class="bi bi-x-circle"></i> Tolak
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
                                    <i class="bi bi-inbox"></i> Tidak ada pengajuan yang menunggu persetujuan
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat Persetujuan -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Persetujuan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Pengaju</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th>Tanggal Persetujuan</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $query = "SELECT p.*, u.username as nama_pengaju FROM pengajuan p 
                                      LEFT JOIN users u ON p.created_by = u.id 
                                      WHERE (p.approved_by_kanit=$user_id OR p.rejected_by_kanit=$user_id)
                                      ORDER BY COALESCE(p.approved_date_kanit, p.rejected_date_kanit) DESC LIMIT 10";
                            $result = $conn->query($query);
                            
                            if ($result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['tipe_pengajuan']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status'] == 'approved_kanit' ? 'success' : 'danger' ?>">
                                        <?= $row['status'] == 'approved_kanit' ? 'Disetujui' : 'Ditolak' ?>
                                    </span>
                                </td>
                                <td><?= ($row['approved_date_kanit'] ?? $row['rejected_date_kanit']) ? date('d/m/Y H:i', strtotime($row['approved_date_kanit'] ?? $row['rejected_date_kanit'])) : '-' ?></td>
                                <td><?= htmlspecialchars($row['catatan_kanit'] ?? $row['rejected_reason'] ?? '') ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada riwayat persetujuan
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
