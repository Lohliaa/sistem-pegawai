<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] != 'staf') {
    header('Location: index.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $check = $conn->query("SELECT id FROM pengajuan WHERE id=$id AND created_by=$user_id AND status='pending'");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM pengajuan WHERE id=$id");
        $_SESSION['alert_message'] = 'Pengajuan berhasil dihapus!';
        $_SESSION['alert_icon'] = 'success';
        $_SESSION['alert_title'] = 'Berhasil!';
    } else {
        $_SESSION['alert_message'] = 'Pengajuan tidak bisa dihapus (sudah diproses atau bukan pengajuan Anda)!';
        $_SESSION['alert_icon'] = 'warning';
        $_SESSION['alert_title'] = 'Peringatan!';
    }
    header('Location: daftar_pengajuan.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Pengajuan - Staf</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .sidebar { background: #2c3e50; min-height: 100vh; padding: 20px; position: fixed; width: 250px; left: 0; top: 0; }
        .sidebar .brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar .brand h4 {
            color: #fff;
            font-weight: 600;
        }
        .sidebar .brand small {
            color: #8ba0b8;
        }
        .sidebar a {
            color: #b0c4de;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 25px;
            margin: 3px 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .sidebar a i {
            margin-right: 12px;
            width: 20px;
            font-size: 1.2rem;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            transform: translateX(5px);
        }
        .sidebar a.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
        }
        .main-content { margin-left: 250px; padding: 30px; background: #f4f6f9; min-height: 100vh; }
        .badge-pending { background: #f39c12; }
        .badge-approved_kanit { background: #3498db; }
        .badge-approved_kabid { background: #27ae60; }
        .badge-rejected { background: #e74c3c; }
        .card-custom {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <h4><i class="bi bi-building"></i> SIPS</h4>
            <small>Sistem Informasi Pegawai</small>
        </div>
        <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
        <?php endif; ?>
        <a href="pengajuan_staf.php"><i class="bi bi-file-earmark-text"></i> Pengajuan</a>
        <a href="daftar_pengajuan.php" class="active"><i class="bi bi-list-check"></i> Daftar Pengajuan Saya</a>
        <a href="logout.php" style="margin-top: 30px; color: #e74c3c;"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list-check text-primary"></i> Daftar Pengajuan Saya</h2>
            <span class="badge bg-success p-2"><i class="bi bi-person-badge"></i> STAF</span>
        </div>

        <?php if (isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?= $_SESSION['alert_icon'] == 'success' ? 'success' : 'warning' ?> alert-dismissible fade show" role="alert">
            <strong><?= htmlspecialchars($_SESSION['alert_title']) ?>!</strong> <?= htmlspecialchars($_SESSION['alert_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert_message'], $_SESSION['alert_icon'], $_SESSION['alert_title']); endif; ?>

        <div class="card card-custom">
            <div class="card-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Daftar Pengajuan Saya</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="tablePengajuan">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tipe</th>
                                <th>Nama</th>
                                <th>Unit</th>
                                <th>Tanggal Dibuat</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        $result = $conn->query("SELECT * FROM pengajuan WHERE created_by=$user_id ORDER BY created_at DESC");
                        if ($result && $result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                $status = $row['status'];
                                
                                // Status Label dan Class yang BENAR
                                if ($status == 'pending') {
                                    $status_label = '⏳ Menunggu Kanit';
                                    $status_class = 'pending';
                                } elseif ($status == 'approved_kanit') {
                                    $status_label = '✅ Disetujui Kanit - Menunggu Kabid';
                                    $status_class = 'approved_kanit';
                                } elseif ($status == 'approved_kabid') {
                                    $status_label = '✅✅ Disetujui Kabid (Selesai)';
                                    $status_class = 'approved_kabid';
                                } elseif ($status == 'rejected') {
                                    $status_label = '❌ Ditolak';
                                    $status_class = 'rejected';
                                } else {
                                    $status_label = $status;
                                    $status_class = 'secondary';
                                }
                                
                                // Ambil catatan dari berbagai sumber
                                $catatan = '';
                                if ($status == 'rejected' && !empty($row['rejected_reason'])) {
                                    $catatan = 'Alasan: ' . $row['rejected_reason'];
                                } elseif ($status == 'approved_kanit' && !empty($row['catatan_kanit'])) {
                                    $catatan = 'Catatan Kanit: ' . $row['catatan_kanit'];
                                } elseif ($status == 'approved_kabid' && !empty($row['catatan_kabid'])) {
                                    $catatan = 'Catatan Kabid: ' . $row['catatan_kabid'];
                                } elseif (!empty($row['keterangan'])) {
                                    $catatan = $row['keterangan'];
                                }
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['unit']) ?></span></td>
                                <td><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                                <td><span class="badge badge-<?= $status_class ?>"><?= $status_label ?></span></td>
                                <td>
                                    <?php if (!empty($catatan)): ?>
                                        <small><?= htmlspecialchars(substr($catatan, 0, 50)) ?><?= strlen($catatan) > 50 ? '...' : '' ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status == 'pending'): ?>
                                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus pengajuan ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size: 2rem;"></i><br>Belum ada pengajuan</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablePengajuan').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                },
                "pageLength": 10,
                "order": [[4, "desc"]]
            });
        });
    </script>
</body>
</html>