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
$current_page = 'persetujuan_kanit.php';


$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// CEK SEMUA KOLOM YANG DIBUTUHKAN
$columns_to_check = ['approved_by_kabid', 'approved_date_kabid', 'catatan_kabid'];
$kolom_kabid_ada = true;

foreach ($columns_to_check as $col) {
    $check = $conn->query("SHOW COLUMNS FROM pengajuan LIKE '$col'");
    if (!$check || $check->num_rows == 0) {
        $kolom_kabid_ada = false;
        break;
    }
}

// Handle approve pengajuan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve'])) {
    $id = (int)$_POST['id'];
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    if ($role == 'kanit') {
        $query = "UPDATE pengajuan SET 
                  status='approved_kanit',
                  approved_by_kanit=$user_id,
                  approved_date_kanit=NOW(),
                  catatan_kanit='$catatan'
                  WHERE id=$id AND status='pending'";
    } else { // kabid
        // Cek apakah semua kolom kabid ada
        if ($kolom_kabid_ada) {
            $query = "UPDATE pengajuan SET 
                      status='approved_kabid',
                      approved_by_kabid=$user_id,
                      approved_date_kabid=NOW(),
                      catatan_kabid='$catatan'
                      WHERE id=$id AND status='approved_kanit'";
        } else {
            // Fallback jika kolom belum ada - hanya update status
            $query = "UPDATE pengajuan SET 
                      status='approved_kabid'
                      WHERE id=$id AND status='approved_kanit'";
        }
    }

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

// Handle tolak pengajuan (hanya untuk kanit)
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
    <title>Persetujuan Pengajuan - <?= strtoupper($role) ?></title>
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
            width: 250px;
            left: 0;
            top: 0;
        }

        .sidebar .brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar a.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            background: #f4f6f9;
            min-height: 100vh;
        }

        .card-custom {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .badge-pending {
            background: #f39c12;
            color: #fff;
        }

        .badge-approved_kanit {
            background: #3498db;
            color: #fff;
        }

        .badge-approved_kabid {
            background: #27ae60;
            color: #fff;
        }

        .badge-rejected {
            background: #e74c3c;
            color: #fff;
        }

        .badge-processing {
            background: #f39c12;
            color: #fff;
        }

        .badge-completed {
            background: #2ecc71;
            color: #fff;
        }

        .stat-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 5px 0;
        }

        .stat-card .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar_v2.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-check-circle text-primary"></i> Persetujuan Pengajuan</h2>
            <span class="badge bg-<?= $role == 'kanit' ? 'warning' : 'info' ?> p-2">
                <i class="bi bi-person-badge"></i> <?= strtoupper($role) ?>
            </span>
        </div>

        <!-- Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #f39c12;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Menunggu Persetujuan</div>
                            <div class="stat-number">
                                <?php
                                if ($role == 'kanit') {
                                    $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE status='pending'");
                                } else {
                                    $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE status='approved_kanit'");
                                }
                                echo $result ? $result->fetch_assoc()['total'] : 0;
                                ?>
                            </div>
                        </div>
                        <i class="bi bi-hourglass-split" style="font-size: 2.5rem; color: #f39c12; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #27ae60;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Sudah Disetujui</div>
                            <div class="stat-number">
                                <?php
                                if ($role == 'kanit') {
                                    $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE approved_by_kanit=$user_id");
                                } else {
                                    if ($kolom_kabid_ada) {
                                        $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE approved_by_kabid=$user_id");
                                    } else {
                                        $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE status='approved_kabid'");
                                    }
                                }
                                echo $result ? $result->fetch_assoc()['total'] : 0;
                                ?>
                            </div>
                        </div>
                        <i class="bi bi-check-circle-fill" style="font-size: 2.5rem; color: #27ae60; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #e74c3c;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Ditolak</div>
                            <div class="stat-number">
                                <?php
                                $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan WHERE rejected_by_kanit=$user_id");
                                echo $result ? $result->fetch_assoc()['total'] : 0;
                                ?>
                            </div>
                        </div>
                        <i class="bi bi-x-circle-fill" style="font-size: 2.5rem; color: #e74c3c; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #3498db;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Pengajuan</div>
                            <div class="stat-number">
                                <?php
                                $result = $conn->query("SELECT COUNT(*) as total FROM pengajuan");
                                echo $result ? $result->fetch_assoc()['total'] : 0;
                                ?>
                            </div>
                        </div>
                        <i class="bi bi-file-earmark-text" style="font-size: 2.5rem; color: #3498db; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert -->
        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_icon'] == 'success' ? 'success' : ($_SESSION['alert_icon'] == 'warning' ? 'warning' : 'danger') ?> alert-dismissible fade show" role="alert">
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
        <div class="card card-custom mb-4">
            <div class="card-header bg-warning text-dark" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0"><i class="bi bi-hourglass-split"></i> Pengajuan Menunggu Persetujuan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="tablePending">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pengaju</th>
                                <th>Tipe</th>
                                <th>Nama</th>
                                <th>Unit</th>
                                <th>Tgl Dibuat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($role == 'kanit') {
                                $status_filter = "status='pending'";
                            } else {
                                $status_filter = "status='approved_kanit'";
                            }

                            $query = "SELECT p.*, u.username as nama_pengaju 
                                      FROM pengajuan p 
                                      LEFT JOIN users u ON p.created_by = u.id 
                                      WHERE $status_filter 
                                      ORDER BY p.created_at ASC";
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $status_label = '';
                                    $status_class = '';
                                    if ($row['status'] == 'pending') {
                                        $status_label = 'Menunggu Kanit';
                                        $status_class = 'pending';
                                    } elseif ($row['status'] == 'approved_kanit') {
                                        $status_label = 'Menunggu Kabid';
                                        $status_class = 'approved_kanit';
                                    } elseif ($row['status'] == 'processing') {
                                        $status_label = '⏳ Sedang Diproses';
                                        $status_class = 'processing';
                                    }
                            ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_pengaju'] ?? 'Unknown') ?></strong></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['unit']) ?></span></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                        <td><span class="badge badge-<?= $status_class ?>"><?= $status_label ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $row['id'] ?>">
                                                <i class="bi bi-eye"></i> Detail
                                            </button>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalSetujui<?= $row['id'] ?>">
                                                <i class="bi bi-check"></i> Setuju
                                            </button>
                                            <?php if ($role == 'kanit'): ?>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalTolak<?= $row['id'] ?>">
                                                    <i class="bi bi-x"></i> Tolak
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal Detail -->
                                    <div class="modal fade" id="modalDetail<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Pengajuan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6"><label><strong>Pengaju</strong></label>
                                                            <p><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></p>
                                                        </div>
                                                        <div class="col-md-6"><label><strong>Tipe Pengajuan</strong></label>
                                                            <p><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></p>
                                                        </div>
                                                        <div class="col-md-6"><label><strong>Nama</strong></label>
                                                            <p><?= htmlspecialchars($row['nama']) ?></p>
                                                        </div>
                                                        <div class="col-md-6"><label><strong>Unit</strong></label>
                                                            <p><span class="badge bg-info text-dark"><?= htmlspecialchars($row['unit']) ?></span></p>
                                                        </div>
                                                        <div class="col-md-6"><label><strong>Tanggal Lahir</strong></label>
                                                            <p><?= date('d/m/Y', strtotime($row['tanggal_lahir'])) ?></p>
                                                        </div>
                                                        <div class="col-md-6"><label><strong>Tanggal TMT</strong></label>
                                                            <p><?= date('d/m/Y', strtotime($row['tanggal_tmt'])) ?></p>
                                                        </div>
                                                        <div class="col-md-12"><label><strong>Keterangan</strong></label>
                                                            <p><?= htmlspecialchars($row['keterangan'] ?? '-') ?></p>
                                                        </div>
                                                        <?php if ($row['status'] == 'approved_kanit' && $row['catatan_kanit']): ?>
                                                            <div class="col-md-12"><label><strong>Catatan Kanit</strong></label>
                                                                <p class="text-info"><?= htmlspecialchars($row['catatan_kanit']) ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
                                                        <div class="mb-3">
                                                            <label class="form-label">Catatan (Opsional)</label>
                                                            <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan..."></textarea>
                                                        </div>
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

                                    <!-- Modal Tolak (hanya untuk kanit) -->
                                    <?php if ($role == 'kanit'): ?>
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
                                                            <div class="mb-3">
                                                                <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                                <textarea name="alasan" class="form-control" rows="3" placeholder="Jelaskan alasan penolakan..." required></textarea>
                                                            </div>
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
                                    <?php endif; ?>

                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <br>
                                        Tidak ada pengajuan yang menunggu persetujuan
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat Persetujuan -->
        <div class="card card-custom">
            <div class="card-header bg-info text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Persetujuan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="tableRiwayat">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pengaju</th>
                                <th>Tipe</th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($role == 'kanit') {
                                $query = "SELECT p.*, u.username as nama_pengaju 
                                  FROM pengajuan p 
                                  LEFT JOIN users u ON p.created_by = u.id 
                                  WHERE p.approved_by_kanit=$user_id OR p.rejected_by_kanit=$user_id
                                  ORDER BY COALESCE(p.approved_date_kanit, p.rejected_date_kanit) DESC 
                                  LIMIT 20";
                            } else {
                                if ($kolom_kabid_ada) {
                                    $query = "SELECT p.*, u.username as nama_pengaju 
                                      FROM pengajuan p 
                                      LEFT JOIN users u ON p.created_by = u.id 
                                      WHERE p.approved_by_kabid=$user_id
                                      ORDER BY p.approved_date_kabid DESC 
                                      LIMIT 20";
                                } else {
                                    $query = "SELECT p.*, u.username as nama_pengaju 
                                      FROM pengajuan p 
                                      LEFT JOIN users u ON p.created_by = u.id 
                                      WHERE p.status='approved_kabid'
                                      ORDER BY p.created_at DESC 
                                      LIMIT 20";
                                }
                            }
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $status_text = '';
                                    $status_class = '';

                                    // MENENTUKAN STATUS DENGAN LENGKAP
                                    if ($row['status'] == 'pending') {
                                        $status_text = '⏳ Menunggu Persetujuan';
                                        $status_class = 'pending';
                                    } elseif ($row['status'] == 'approved_kanit') {
                                        $status_text = '✅ Disetujui Kanit - Menunggu Kabid';
                                        $status_class = 'approved_kanit';
                                    } elseif ($row['status'] == 'approved_kabid') {
                                        $status_text = '✅✅ Disetujui Kabid (Selesai)';
                                        $status_class = 'approved_kabid';
                                    } elseif ($row['status'] == 'rejected') {
                                        $status_text = '❌ Ditolak';
                                        $status_class = 'rejected';
                                    } elseif ($row['status'] == 'processing') {
                                        $status_text = '⏳ Sedang Diproses';
                                        $status_class = 'processing';
                                    } else {
                                        $status_text = $row['status'];
                                        $status_class = 'secondary';
                                    }

                                    $tanggal = '';
                                    if (!empty($row['approved_date_kanit'])) {
                                        $tanggal = $row['approved_date_kanit'];
                                    } elseif (!empty($row['rejected_date_kanit'])) {
                                        $tanggal = $row['rejected_date_kanit'];
                                    } elseif (!empty($row['approved_date_kabid'])) {
                                        $tanggal = $row['approved_date_kabid'];
                                    }
                            ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengaju'] ?? '-') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tipe_pengajuan']) ?></span></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><span class="badge badge-<?= $status_class ?>"><?= $status_text ?></span></td>
                                        <td><?= $tanggal ? date('d/m/Y H:i', strtotime($tanggal)) : '-' ?></td>
                                        <td><?= htmlspecialchars($row['catatan_kanit'] ?? $row['rejected_reason'] ?? $row['catatan_kabid'] ?? '-') ?></td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#tablePending').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                },
                "pageLength": 10,
                "order": [
                    [0, "asc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [7]
                }]
            });

            $('#tableRiwayat').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                },
                "pageLength": 10,
                "order": [
                    [5, "desc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [6]
                }]
            });
        });
    </script>
</body>

</html>