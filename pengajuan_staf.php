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

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Ambil data user
$user_query = $conn->query("SELECT * FROM users WHERE id=$user_id");
$user_data = $user_query->fetch_assoc();

// Handle tambah pengajuan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $tipe_pengajuan = mysqli_real_escape_string($conn, $_POST['tipe_pengajuan']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $tanggal_tmt = mysqli_real_escape_string($conn, $_POST['tanggal_tmt']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    $query = "INSERT INTO pengajuan (tipe_pengajuan, nama, tanggal_lahir, tanggal_tmt, unit, keterangan, status, created_by, created_at) 
              VALUES ('$tipe_pengajuan', '$nama', '$tanggal_lahir', '$tanggal_tmt', '$unit', '$keterangan', 'pending', $user_id, NOW())";

    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Pengajuan berhasil dibuat! Menunggu persetujuan kanit/kabid.";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Gagal membuat pengajuan: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }
    header('Location: pengajuan_staf.php');
    exit();
}

// Handle hapus pengajuan (hanya jika masih pending)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $check = $conn->query("SELECT * FROM pengajuan WHERE id=$id AND created_by=$user_id AND status='pending'");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM pengajuan WHERE id=$id");
        $_SESSION['alert_message'] = "Pengajuan berhasil dihapus!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Pengajuan tidak bisa dihapus (sudah diproses atau bukan pengajuan Anda)!";
        $_SESSION['alert_icon'] = "warning";
        $_SESSION['alert_title'] = "Peringatan!";
    }
    header('Location: pengajuan_staf.php');
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Pengajuan - Staf</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4><i class="bi bi-building"></i> SIPS</h4>
            <small>Sistem Informasi Pegawai</small>
        </div>
        <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <?php if ($role == 'admin'): ?>
            <a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
        <?php endif; ?>
        <a href="pengajuan_staf.php" class="active"><i class="bi bi-file-earmark-text"></i> Pengajuan</a>
        <a href="daftar_pengajuan.php"><i class="bi bi-list-check"></i> Daftar Pengajuan Saya</a>
        <a href="logout.php" style="margin-top: 30px; color: #e74c3c;"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text text-primary"></i> Pengajuan Saya</h2>
            <span class="badge bg-info p-2">
                <i class="bi bi-person-badge"></i> STAF
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

        <!-- Form Pengajuan -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Buat Pengajuan Baru</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formPengajuan">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipe Pengajuan <span class="text-danger">*</span></label>
                            <select name="tipe_pengajuan" class="form-select" required>
                                <option value="">Pilih Tipe</option>
                                <option value="MoU">MoU (Memorandum of Understanding)</option>
                                <option value="SK">SK (Surat Keputusan)</option>
                                <option value="Kontrak">Kontrak Kerja</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select" required>
                                <option value="">Pilih Unit</option>
                                <option value="Daycare">Daycare Permata</option>
                                <option value="TPA">TPA Permata</option>
                                <option value="KBIT">KBIT Permata</option>
                                <option value="TKIT">TKIT Permata</option>
                                <option value="TKIP">TKIP Permata</option>
                                <option value="MI">MI Permata</option>
                                <option value="SDIT">SDIT Permata</option>
                                <option value="SMPIT">SMPIT Permata</option>
                                <option value="MA">MA Permata</option>
                                <option value="PKBM">PKBM Permata</option>
                                <option value="Yayasan">Yayasan Permata</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal TMT <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_tmt" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Tambahkan keterangan jika diperlukan..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="tambah" class="btn btn-primary">
                                <i class="bi bi-send"></i> Kirim Pengajuan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <a href="daftar_pengajuan.php" class="btn btn-info text-white">
            <i class="bi bi-list-check"></i> Lihat Daftar Pengajuan Saya
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>