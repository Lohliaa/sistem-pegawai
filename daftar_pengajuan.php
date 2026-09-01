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
    <style>
        .sidebar { background: #2c3e50; min-height: 100vh; padding: 20px; position: fixed; width: 200px; left: 0; }
        .sidebar h4 { color: white; margin-bottom: 20px; }
        .sidebar a { color: white; display: block; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-bottom: 5px; }
        .sidebar a:hover { background: #34495e; }
        .sidebar a.active { background: #3498db; }
        .main-content { margin-left: 200px; padding: 30px; background: #f4f6f9; min-height: 100vh; }
        .badge-pending { background: #f39c12; }
        .badge-approved { background: #27ae60; }
        .badge-processing { background: #3498db; }
        .badge-completed { background: #2ecc71; }
        .badge-rejected { background: #e74c3c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4><i class="bi bi-building"></i> SIPS</h4>
        <a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
        <a href="pengajuan_staf.php"><i class="bi bi-file-earmark-text"></i> Pengajuan</a>
        <a href="daftar_pengajuan.php" class="active"><i class="bi bi-list-check"></i> Daftar Pengajuan Saya</a>
        <a href="logout.php" style="margin-top: 20px; color: #e74c3c;"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list-check text-primary"></i> Daftar Pengajuan Saya</h2>
            <span class="badge bg-info p-2"><i class="bi bi-person-badge"></i> STAF</span>
        </div>

        <?php if (isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?= $_SESSION['alert_icon'] == 'success' ? 'success' : 'warning' ?> alert-dismissible fade show" role="alert">
            <strong><?= htmlspecialchars($_SESSION['alert_title']) ?>!</strong> <?= htmlspecialchars($_SESSION['alert_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert_message'], $_SESSION['alert_icon'], $_SESSION['alert_title']); endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Daftar Pengajuan Saya</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th><th>Tipe</th><th>Nama</th><th>Unit</th><th>Tanggal Dibuat</th>
                                <th>Status</th><th>Riwayat Proses</th><th>Keterangan</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        $result = $conn->query("SELECT * FROM pengajuan WHERE created_by=$user_id ORDER BY created_at DESC");
                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                $status = $row['status'];
                                $status_class = $status == 'pending' ? 'pending' : ($status == 'approved_kanit' ? 'approved' : ($status == 'processing' ? 'processing' : ($status == 'completed' ? 'completed' : 'rejected')));
                                $status_label = $status == 'pending' ? 'Menunggu Approve' : ($status == 'approved_kanit' ? 'Disetujui Kanit/Kabid' : ($status == 'processing' ? 'Sedang Diproses' : ($status == 'completed' ? 'Selesai' : 'Ditolak')));
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                <td><?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                                <td><span class="badge badge-<?= $status_class ?>"><?= $status_label ?></span></td>
                                <td>
                                    <small class="d-block text-muted"><i class="bi bi-send"></i> Diajukan: <?= $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></small>
                                    <?php if (in_array($status, ['approved_kanit', 'processing', 'completed'], true)): ?>
                                    <small class="d-block text-success"><i class="bi bi-check-circle"></i> Disetujui: <?= $row['approved_date_kanit'] ? date('d/m/Y H:i', strtotime($row['approved_date_kanit'])) : '-' ?></small>
                                    <?php elseif ($status == 'rejected'): ?>
                                    <small class="d-block text-danger"><i class="bi bi-x-circle"></i> Ditolak: <?= $row['rejected_date_kanit'] ? date('d/m/Y H:i', strtotime($row['rejected_date_kanit'])) : '-' ?></small>
                                    <?php endif; ?>
                                    <?php if ($row['processed_date']): ?><small class="d-block text-info"><i class="bi bi-hourglass-split"></i> Diproses: <?= date('d/m/Y H:i', strtotime($row['processed_date'])) ?></small><?php endif; ?>
                                    <?php if ($row['completed_date']): ?><small class="d-block text-success"><i class="bi bi-check2-all"></i> Selesai: <?= date('d/m/Y H:i', strtotime($row['completed_date'])) ?></small><?php endif; ?>
                                </td>
                                <td><?php if ($status == 'rejected' && !empty($row['rejected_reason'])): ?><small class="text-danger"><?= htmlspecialchars($row['rejected_reason']) ?></small><?php elseif (!empty($row['keterangan'])): ?><small><?= htmlspecialchars(substr($row['keterangan'], 0, 50)) ?><?= strlen($row['keterangan']) > 50 ? '...' : '' ?></small><?php endif; ?></td>
                                <td><?php if ($status == 'pending'): ?><a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus pengajuan ini?')"><i class="bi bi-trash"></i> Hapus</a><?php else: ?><span class="text-muted small">-</span><?php endif; ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> Belum ada pengajuan</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
