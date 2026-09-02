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

// Fungsi untuk membuat user baru
function createUser($conn, $username, $role, $password = null)
{
    if ($password === null) {
        $password = '123456';
    }

    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        return ['success' => false, 'message' => "Username '$username' sudah digunakan!"];
    }

    $hashed_password = md5($password);
    $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$hashed_password', '$role')";

    if ($conn->query($query)) {
        $user_id = $conn->insert_id;
        return ['success' => true, 'user_id' => $user_id, 'message' => "User '$username' berhasil dibuat!"];
    } else {
        return ['success' => false, 'message' => "Gagal membuat user: " . $conn->error];
    }
}

// Fungsi untuk update user
function updateUser($conn, $user_id, $username, $role, $password = null)
{
    $check = $conn->query("SELECT id FROM users WHERE username = '$username' AND id != $user_id");
    if ($check->num_rows > 0) {
        return ['success' => false, 'message' => "Username '$username' sudah digunakan oleh user lain!"];
    }

    $query = "UPDATE users SET username='$username', role='$role'";

    if ($password !== null && !empty($password)) {
        $hashed_password = md5($password);
        $query .= ", password='$hashed_password'";
    }

    $query .= " WHERE id=$user_id";

    if ($conn->query($query)) {
        return ['success' => true, 'message' => "User berhasil diupdate!"];
    } else {
        return ['success' => false, 'message' => "Gagal update user: " . $conn->error];
    }
}

// Proses Tambah Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $akun_id = (int)($_POST['akun_id'] ?? 0);
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat'] ?? '');
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $golongan = mysqli_real_escape_string($conn, $_POST['golongan']);
    $status_kepegawaian = mysqli_real_escape_string($conn, $_POST['status_kepegawaian']);
    $masa_kerja = mysqli_real_escape_string($conn, $_POST['masa_kerja']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'staf');
    $buat_user = isset($_POST['buat_user']) ? 1 : 0;
    $password_user = mysqli_real_escape_string($conn, $_POST['password_user'] ?? '123456');

    if ($akun_id == 0 && $buat_user == 1) {
        $username = strtolower(str_replace(' ', '', $nama));
        $result = createUser($conn, $username, $role, $password_user);

        if ($result['success']) {
            $akun_id = $result['user_id'];
            $_SESSION['alert_message'] = "Data pegawai berhasil ditambahkan! " . $result['message'];
            $_SESSION['alert_icon'] = "success";
            $_SESSION['alert_title'] = "Berhasil!";
        } else {
            $_SESSION['alert_message'] = "Gagal membuat user: " . $result['message'];
            $_SESSION['alert_icon'] = "error";
            $_SESSION['alert_title'] = "Gagal!";
            header('Location: profile_pegawai.php');
            exit();
        }
    }

    $query = "INSERT INTO pegawai (user_id, nama, tempat, tanggal_lahir, alamat, jabatan, golongan, status_kepegawaian, masa_kerja, unit, role) 
              VALUES (NULLIF($akun_id, 0), '$nama', '$tempat', '$tanggal_lahir', '$alamat', '$jabatan', '$golongan', '$status_kepegawaian', '$masa_kerja', '$unit', '$role')";

    if ($conn->query($query)) {
        if (!isset($_SESSION['alert_message'])) {
            $_SESSION['alert_message'] = "Data pegawai berhasil ditambahkan!";
            $_SESSION['alert_icon'] = "success";
            $_SESSION['alert_title'] = "Berhasil!";
        }
        header('Location: profile_pegawai.php');
        exit();
    } else {
        $_SESSION['alert_message'] = "Gagal menambahkan data: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
        header('Location: profile_pegawai.php');
        exit();
    }
}

// Proses Edit Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $akun_id = (int)($_POST['akun_id'] ?? 0);
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat'] ?? '');
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $golongan = mysqli_real_escape_string($conn, $_POST['golongan']);
    $status_kepegawaian = mysqli_real_escape_string($conn, $_POST['status_kepegawaian']);
    $masa_kerja = mysqli_real_escape_string($conn, $_POST['masa_kerja']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'staf');
    $buat_user = isset($_POST['buat_user']) ? 1 : 0;
    $password_user = mysqli_real_escape_string($conn, $_POST['password_user'] ?? '');

    if ($akun_id == 0 && $buat_user == 1) {
        $username = strtolower(str_replace(' ', '', $nama));
        $result = createUser($conn, $username, $role, $password_user);

        if ($result['success']) {
            $akun_id = $result['user_id'];
            $_SESSION['alert_message'] = "Data pegawai berhasil diupdate! " . $result['message'];
            $_SESSION['alert_icon'] = "success";
            $_SESSION['alert_title'] = "Berhasil!";
        } else {
            $_SESSION['alert_message'] = "Gagal membuat user: " . $result['message'];
            $_SESSION['alert_icon'] = "error";
            $_SESSION['alert_title'] = "Gagal!";
            header('Location: profile_pegawai.php');
            exit();
        }
    }

    if ($akun_id > 0) {
        $user_data = $conn->query("SELECT username, role FROM users WHERE id=$akun_id")->fetch_assoc();
        if ($user_data) {
            $update_user = updateUser($conn, $akun_id, $user_data['username'], $role, $password_user);
            if (!$update_user['success']) {
                $_SESSION['alert_message'] = $update_user['message'];
                $_SESSION['alert_icon'] = "error";
                $_SESSION['alert_title'] = "Gagal!";
                header('Location: profile_pegawai.php');
                exit();
            }
        }
    }

    $query = "UPDATE pegawai SET 
              user_id=NULLIF($akun_id, 0),
              nama='$nama',
              tempat='$tempat', 
              tanggal_lahir='$tanggal_lahir', 
              alamat='$alamat', 
              jabatan='$jabatan',
              golongan='$golongan',
              status_kepegawaian='$status_kepegawaian',
              masa_kerja='$masa_kerja',
              unit='$unit',
              role='$role' 
              WHERE id=$id";

    if ($conn->query($query)) {
        if (!isset($_SESSION['alert_message'])) {
            $_SESSION['alert_message'] = "Data pegawai berhasil diupdate!";
            $_SESSION['alert_icon'] = "success";
            $_SESSION['alert_title'] = "Berhasil!";
        }
        header('Location: profile_pegawai.php');
        exit();
    } else {
        $_SESSION['alert_message'] = "Gagal mengupdate data: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
        header('Location: profile_pegawai.php');
        exit();
    }
}

// Proses Hapus Data
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $nama_query = $conn->query("SELECT nama FROM pegawai WHERE id=$id");
    $nama_data = $nama_query->fetch_assoc();
    $nama_pegawai = $nama_data['nama'];

    $query = "DELETE FROM pegawai WHERE id=$id";

    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Data pegawai '$nama_pegawai' berhasil dihapus!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
        header('Location: profile_pegawai.php');
        exit();
    } else {
        $_SESSION['alert_message'] = "Gagal menghapus data: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
        header('Location: profile_pegawai.php');
        exit();
    }
}

// Proses Import Excel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_excel'])) {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $file = $_FILES['file_excel']['tmp_name'];
        $extension = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

        if ($extension == 'csv' || $extension == 'xls' || $extension == 'xlsx') {
            $row = 1;
            $imported = 0;
            $errors = [];
            $users_created = 0;

            if (($handle = fopen($file, "r")) !== FALSE) {
                fgetcsv($handle, 1000, ",");

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $num = count($data);
                    if ($num >= 9) {
                        $nama = mysqli_real_escape_string($conn, trim($data[0]));
                        $tempat = mysqli_real_escape_string($conn, trim($data[1] ?? ''));
                        $tanggal_lahir = mysqli_real_escape_string($conn, trim($data[2] ?? ''));
                        $alamat = mysqli_real_escape_string($conn, trim($data[3] ?? ''));
                        $jabatan = mysqli_real_escape_string($conn, trim($data[4] ?? ''));
                        $golongan = mysqli_real_escape_string($conn, trim($data[5] ?? ''));
                        $status_kepegawaian = mysqli_real_escape_string($conn, trim($data[6] ?? ''));
                        $masa_kerja = mysqli_real_escape_string($conn, trim($data[7] ?? ''));
                        $unit = mysqli_real_escape_string($conn, trim($data[8] ?? ''));
                        $role = isset($data[9]) ? mysqli_real_escape_string($conn, trim($data[9])) : 'staf';

                        if (strpos($tanggal_lahir, '/') !== false) {
                            $parts = explode('/', $tanggal_lahir);
                            if (count($parts) == 3) {
                                $tanggal_lahir = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                            }
                        }

                        if ($nama && $tanggal_lahir && $unit) {
                            $username = strtolower(str_replace(' ', '', $nama));
                            $result = createUser($conn, $username, $role, '123456');

                            $user_id = 0;
                            if ($result['success']) {
                                $user_id = $result['user_id'];
                                $users_created++;
                            }

                            $query = "INSERT INTO pegawai (user_id, nama, tempat, tanggal_lahir, alamat, jabatan, golongan, status_kepegawaian, masa_kerja, unit, role)
                                      VALUES (NULLIF($user_id, 0), '$nama', '$tempat', '$tanggal_lahir', '$alamat', '$jabatan', '$golongan', '$status_kepegawaian', '$masa_kerja', '$unit', '$role')";

                            if ($conn->query($query)) {
                                $imported++;
                            } else {
                                $errors[] = "Baris " . $row . ": " . $conn->error;
                            }
                        }
                    }
                    $row++;
                }
                fclose($handle);
            }

            if ($imported > 0) {
                $_SESSION['alert_message'] = "Berhasil mengimport $imported data pegawai! ($users_created user baru dibuat)";
                $_SESSION['alert_icon'] = "success";
                $_SESSION['alert_title'] = "Berhasil!";
            } else {
                $_SESSION['alert_message'] = "Tidak ada data yang berhasil diimport. " . implode(", ", $errors);
                $_SESSION['alert_icon'] = "warning";
                $_SESSION['alert_title'] = "Peringatan!";
            }
            header('Location: profile_pegawai.php');
            exit();
        } else {
            $_SESSION['alert_message'] = "Format file tidak didukung. Gunakan format CSV.";
            $_SESSION['alert_icon'] = "error";
            $_SESSION['alert_title'] = "Gagal!";
            header('Location: profile_pegawai.php');
            exit();
        }
    } else {
        $_SESSION['alert_message'] = "Silakan pilih file Excel terlebih dahulu!";
        $_SESSION['alert_icon'] = "warning";
        $_SESSION['alert_title'] = "Peringatan!";
        header('Location: profile_pegawai.php');
        exit();
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $result = $conn->query("SELECT * FROM pegawai WHERE id=$id");
    $edit_data = $result->fetch_assoc();
}
$users_result = $conn->query("SELECT id, username, role FROM users ORDER BY username");

// Data untuk dashboard - PERBAIKAN STATISTIK
$total_pegawai = $conn->query("SELECT COUNT(*) as total FROM pegawai")->fetch_assoc()['total'];

// Status Kepegawaian
$total_mitra = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian='Mitra'")->fetch_assoc()['total'];
$total_magang = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian='Magang'")->fetch_assoc()['total'];
$total_honorer = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian='Honorer'")->fetch_assoc()['total'];
$total_cpt = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian='CPT'")->fetch_assoc()['total'];
$total_cgt = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian='CGT'")->fetch_assoc()['total'];
$total_gt = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian='GT'")->fetch_assoc()['total'];
$total_pt = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian='PT'")->fetch_assoc()['total'];

$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

// Status Pegawai yang belum dipetakan (untuk validasi)
$total_lainnya = $conn->query("SELECT COUNT(*) as total FROM pegawai WHERE status_kepegawaian NOT IN ('Mitra', 'Magang', 'Honorer', 'CPT', 'CGT', 'GT', 'PT')")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Profile Pegawai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            padding: 20px 0;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
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
            padding: 20px 30px;
            background: #f4f6f9;
            min-height: 100vh;
        }

        .card-custom {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: none;
            transition: all 0.3s;
            background: #fff;
        }

        .card-custom:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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

        .btn-action {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 8px;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }

        .upload-area i {
            font-size: 3rem;
            color: #3498db;
        }

        .toggle-password {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .password-wrapper {
            position: relative;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar .brand h4,
            .sidebar .brand small,
            .sidebar a span {
                display: none;
            }

            .sidebar a {
                justify-content: center;
                padding: 12px;
            }

            .sidebar a i {
                margin-right: 0;
                font-size: 1.5rem;
            }

            .main-content {
                margin-left: 70px;
                padding: 15px;
            }
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
        <a href="index.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> <span>Manajemen Pengajuan</span></a>
            <a href="profile_pegawai.php" class="active"><i class="bi bi-people"></i> <span>Profile Pegawai</span></a>
            <a href="setup_users.php"><i class="bi bi-file-earmark-spreadsheet"></i> <span>Manajemen User</span></a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'staf'): ?>
            <a href="pengajuan_staf.php"><i class="bi bi-file-earmark-text"></i> <span>Pengajuan</span></a>
        <?php elseif ($_SESSION['role'] == 'kanit' || $_SESSION['role'] == 'kabid'): ?>
            <a href="persetujuan_kanit.php"><i class="bi bi-check-circle"></i> <span>Persetujuan Pengajuan</span></a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'kanit' || $_SESSION['role'] == 'kabid'): ?>
            <a href="approval.php"><i class="bi bi-check2-circle"></i> <span>Persetujuan</span></a>
        <?php endif; ?>
        <a href="logout.php" style="margin-top: 30px; color: #e74c3c;"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people-fill text-primary"></i> Profile Pegawai</h2>
            <span class="badge bg-<?= $_SESSION['role'] == 'admin' ? 'danger' : 'success' ?> p-2">
                <i class="bi bi-person-badge"></i> <?= strtoupper($_SESSION['role']) ?>
            </span>
        </div>

        <!-- Statistik Status Pegawai -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #3498db;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Pegawai</div>
                            <div class="stat-number"><?= $total_pegawai ?></div>
                        </div>
                        <i class="bi bi-people-fill" style="font-size: 2rem; color: #3498db; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #2ecc71;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Mitra</div>
                            <div class="stat-number"><?= $total_mitra ?></div>
                        </div>
                        <i class="bi bi-handshake" style="font-size: 2rem; color: #2ecc71; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #f31270;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Magang</div>
                            <div class="stat-number"><?= $total_magang ?></div>
                        </div>
                        <i class="bi bi-mortarboard" style="font-size: 2rem; color: #f31270; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-color: #f39c12;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Honorer</div>
                            <div class="stat-number"><?= $total_honorer ?></div>
                        </div>
                        <i class="bi bi-person-x-fill" style="font-size: 2rem; color: #f39c12; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="stat-card" style="border-color: #9b59b6;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">CPT</div>
                            <div class="stat-number"><?= $total_cpt ?></div>
                        </div>
                        <i class="bi bi-person-vcard" style="font-size: 2rem; color: #9b59b6; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card" style="border-color: #8e44ad;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">CGT</div>
                            <div class="stat-number"><?= $total_cgt ?></div>
                        </div>
                        <i class="bi bi-person-video" style="font-size: 2rem; color: #8e44ad; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card" style="border-color: #1abc9c;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">GT</div>
                            <div class="stat-number"><?= $total_gt ?></div>
                        </div>
                        <i class="bi bi-person-badge" style="font-size: 2rem; color: #1abc9c; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card" style="border-color: #16a085;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">PT</div>
                            <div class="stat-number"><?= $total_pt ?></div>
                        </div>
                        <i class="bi bi-person-check" style="font-size: 2rem; color: #16a085; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card" style="border-color: #e74c3c;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total User</div>
                            <div class="stat-number"><?= $total_users ?></div>
                        </div>
                        <i class="bi bi-person-circle" style="font-size: 2rem; color: #e74c3c; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
            <?php if ($total_lainnya > 0): ?>
                <div class="col-md-2">
                    <div class="stat-card" style="border-color: #95a5a6;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Lainnya</div>
                                <div class="stat-number"><?= $total_lainnya ?></div>
                            </div>
                            <i class="bi bi-question-circle" style="font-size: 2rem; color: #95a5a6; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tombol Aksi -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                <i class="bi bi-plus-circle"></i> Tambah Pegawai
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-excel"></i> Import Excel
            </button>
            <a href="export_excel.php" class="btn btn-info text-white">
                <i class="bi bi-download"></i> Export Excel
            </a>
            <button class="btn btn-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>

        <!-- Card Daftar Pegawai -->
        <div class="card-custom">
            <div class="card-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Daftar Pegawai</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="tablePegawai">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Jabatan</th>
                                <th>Golongan</th>
                                <th>Status</th>
                                <th>Unit</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $result = $conn->query("
                                SELECT p.*, u.username 
                                FROM pegawai p 
                                LEFT JOIN users u ON p.user_id = u.id 
                                ORDER BY p.nama
                            ");
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                                    <td>
                                        <?php if ($row['username']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($row['username']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak ada akun</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($row['jabatan']) ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($row['golongan']) ?></span></td>
                                    <td>
                                        <span class="badge bg-<?=
                                                                $row['status_kepegawaian'] == 'Mitra' ? 'success' : ($row['status_kepegawaian'] == 'Magang' ? 'danger' : ($row['status_kepegawaian'] == 'Honorer' ? 'warning text-dark' : ($row['status_kepegawaian'] == 'CPT' ? 'info' : ($row['status_kepegawaian'] == 'CGT' ? 'primary' : ($row['status_kepegawaian'] == 'GT' ? 'secondary' : ($row['status_kepegawaian'] == 'PT' ? 'dark' : 'light text-dark'))))))
                                                                ?>">
                                            <?= htmlspecialchars($row['status_kepegawaian']) ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['unit']) ?></span></td>
                                    <td>
                                        <span class="badge bg-<?=
                                                                $row['role'] == 'admin' ? 'danger' : ($row['role'] == 'kanit' ? 'warning' : ($row['role'] == 'kabid' ? 'info' : ($row['role'] == 'staf' ? 'success' : 'dark')))
                                                                ?>">
                                            <?= htmlspecialchars(strtoupper($row['role'] ?? 'N/A')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-action btn-sm" title="Edit" data-bs-toggle="tooltip">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama']) ?>')" class="btn btn-danger btn-action btn-sm" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Data -->
        <div class="modal fade" id="tambahModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="formTambah">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" required id="nama_tambah">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Akun Login</label>
                                    <select name="akun_id" class="form-select" id="akun_id_tambah">
                                        <option value="0">Buat akun baru otomatis</option>
                                        <?php
                                        $users_result_tambah = $conn->query("SELECT id, username, role FROM users ORDER BY username");
                                        while ($akun = $users_result_tambah->fetch_assoc()):
                                        ?>
                                            <option value="<?= $akun['id'] ?>"><?= htmlspecialchars($akun['username']) ?> (<?= strtoupper($akun['role']) ?>)</option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                    <input type="text" name="tempat" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_lahir" class="form-control" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                    <textarea name="alamat" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" name="jabatan" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Golongan <span class="text-danger">*</span></label>
                                    <input type="text" name="golongan" class="form-control" placeholder="Contoh: III/B" required>
                                </div>
                                <div class="col-md-4">
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
                                    <label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label>
                                    <select name="status_kepegawaian" class="form-select" required>
                                        <option value="">Pilih Status</option>
                                        <option value="Mitra">Mitra</option>
                                        <option value="Magang">Magang</option>
                                        <option value="Honorer">Honorer</option>
                                        <option value="CPT">CPT</option>
                                        <option value="CGT">CGT</option>
                                        <option value="GT">GT</option>
                                        <option value="PT">PT</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Masa Kerja <span class="text-danger">*</span></label>
                                    <input type="text" name="masa_kerja" class="form-control" placeholder="Contoh: 10 Tahun" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select" required>
                                        <option value="">Pilih Role</option>
                                        <option value="staf">STAF</option>
                                        <option value="kanit">KANIT</option>
                                        <option value="kabid">KABID</option>
                                        <option value="admin">ADMIN</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="password_section_tambah" style="display: none;">
                                    <label class="form-label">Password (default: 123456)</label>
                                    <div class="password-wrapper">
                                        <input type="password" name="password_user" class="form-control" value="123456">
                                        <i class="bi bi-eye toggle-password" onclick="togglePassword(this)"></i>
                                    </div>
                                    <small class="text-muted">Password default: 123456</small>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="buat_user" value="1" id="buat_user_tambah" checked>
                                        <label class="form-check-label" for="buat_user_tambah">
                                            <i class="bi bi-person-plus"></i> Buat akun login otomatis (username dari nama)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Import Excel -->
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-file-excel"></i> Import Data Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="upload-area" onclick="document.getElementById('file_excel').click()">
                                <i class="bi bi-cloud-upload"></i>
                                <p class="mt-2">Klik atau drag file Excel disini</p>
                                <small class="text-muted">Format: .csv, .xlsx, atau .xls</small>
                                <input type="file" name="file_excel" id="file_excel" class="d-none" accept=".csv,.xlsx,.xls" required>
                            </div>
                            <div id="fileInfo" class="mt-2 text-center"></div>

                            <hr>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Format file Excel/CSV:
                                <br>
                                <small>
                                    Kolom 1: Nama | Kolom 2: Tempat Lahir | Kolom 3: Tanggal Lahir | Kolom 4: Alamat |
                                    Kolom 5: Jabatan | Kolom 6: Golongan | Kolom 7: Status | Kolom 8: Masa Kerja |
                                    Kolom 9: Unit | Kolom 10: Role (opsional)
                                </small>
                                <br>
                                <small class="text-success">
                                    <i class="bi bi-check-circle"></i> Akun login akan dibuat otomatis dengan password default: 123456
                                </small>
                                <br>
                                <a href="#" class="btn btn-sm btn-outline-primary mt-2" onclick="downloadTemplate()">
                                    <i class="bi bi-download"></i> Download Template
                                </a>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="import_excel" class="btn btn-success">
                                <i class="bi bi-upload"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Edit Data -->
        <?php if ($edit_data): ?>
            <div class="modal fade show" id="editModal" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Pegawai</h5>
                            <button type="button" class="btn-close" onclick="window.location.href='profile_pegawai.php'"></button>
                        </div>
                        <form method="POST" action="profile_pegawai.php">
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($edit_data['nama']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Akun Login</label>
                                        <select name="akun_id" class="form-select">
                                            <option value="0">Tidak dihubungkan</option>
                                            <?php
                                            $edit_users_result = $conn->query("SELECT id, username, role FROM users ORDER BY username");
                                            while ($akun = $edit_users_result->fetch_assoc()):
                                            ?>
                                                <option value="<?= $akun['id'] ?>" <?= (int)($edit_data['user_id'] ?? 0) === (int)$akun['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($akun['username']) ?> (<?= strtoupper($akun['role']) ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                        <input type="text" name="tempat" class="form-control" value="<?= htmlspecialchars($edit_data['tempat'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= $edit_data['tanggal_lahir'] ?>" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                        <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($edit_data['alamat']) ?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Jabatan <span class="text-danger">*</span></label>
                                        <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($edit_data['jabatan']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Golongan <span class="text-danger">*</span></label>
                                        <input type="text" name="golongan" class="form-control" value="<?= htmlspecialchars($edit_data['golongan']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                                        <select name="unit" class="form-select" required>
                                            <option value="SD" <?= $edit_data['unit'] == 'SD' ? 'selected' : '' ?>>SD</option>
                                            <option value="SMP" <?= $edit_data['unit'] == 'SMP' ? 'selected' : '' ?>>SMP</option>
                                            <option value="SMA" <?= $edit_data['unit'] == 'SMA' ? 'selected' : '' ?>>SMA</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label>
                                        <select name="status_kepegawaian" class="form-select" required>
                                            <option value="Mitra" <?= $edit_data['status_kepegawaian'] == 'Mitra' ? 'selected' : '' ?>>Mitra</option>
                                            <option value="Magang" <?= $edit_data['status_kepegawaian'] == 'Magang' ? 'selected' : '' ?>>Magang</option>
                                            <option value="Honorer" <?= $edit_data['status_kepegawaian'] == 'Honorer' ? 'selected' : '' ?>>Honorer</option>
                                            <option value="CPT" <?= $edit_data['status_kepegawaian'] == 'CPT' ? 'selected' : '' ?>>CPT</option>
                                            <option value="CGT" <?= $edit_data['status_kepegawaian'] == 'CGT' ? 'selected' : '' ?>>CGT</option>
                                            <option value="GT" <?= $edit_data['status_kepegawaian'] == 'GT' ? 'selected' : '' ?>>GT</option>
                                            <option value="PT" <?= $edit_data['status_kepegawaian'] == 'PT' ? 'selected' : '' ?>>PT</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Masa Kerja <span class="text-danger">*</span></label>
                                        <input type="text" name="masa_kerja" class="form-control" value="<?= htmlspecialchars($edit_data['masa_kerja']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role <span class="text-danger">*</span></label>
                                        <select name="role" class="form-select" required>
                                            <option value="staf" <?= ($edit_data['role'] ?? 'staf') == 'staf' ? 'selected' : '' ?>>STAF</option>
                                            <option value="kanit" <?= ($edit_data['role'] ?? 'staf') == 'kanit' ? 'selected' : '' ?>>KANIT</option>
                                            <option value="kabid" <?= ($edit_data['role'] ?? 'staf') == 'kabid' ? 'selected' : '' ?>>KABID</option>
                                            <option value="admin" <?= ($edit_data['role'] ?? 'staf') == 'admin' ? 'selected' : '' ?>>ADMIN</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="password_section_edit">
                                        <label class="form-label">Password (kosongkan jika tidak diubah)</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="password_user" class="form-control" placeholder="Kosongkan jika tidak diubah">
                                            <i class="bi bi-eye toggle-password" onclick="togglePassword(this)"></i>
                                        </div>
                                        <small class="text-muted">Isi jika ingin mengubah password</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="window.location.href='profile_pegawai.php'">Batal</button>
                                <button type="submit" name="edit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#tablePegawai').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                },
                "pageLength": 10,
                "order": [
                    [1, "asc"]
                ]
            });

            $('[data-bs-toggle="tooltip"]').tooltip();

            $('#akun_id_tambah').on('change', function() {
                if ($(this).val() == '0') {
                    $('#password_section_tambah').show();
                    $('#buat_user_tambah').prop('checked', true);
                } else {
                    $('#password_section_tambah').hide();
                    $('#buat_user_tambah').prop('checked', false);
                }
            });

            $('#nama_tambah').on('input', function() {
                const nama = $(this).val().toLowerCase().replace(/\s/g, '');
                if (nama) {
                    $('#preview_username').text('Username: ' + nama);
                }
            });
        });

        function togglePassword(element) {
            const input = element.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                element.classList.remove('bi-eye');
                element.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                element.classList.remove('bi-eye-slash');
                element.classList.add('bi-eye');
            }
        }

        function confirmDelete(id, nama) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: `Data pegawai "${nama}" akan dihapus secara permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?hapus=${id}`;
                }
            });
        }

        <?php if (isset($_SESSION['alert_message'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['alert_icon'] ?? 'info' ?>',
                title: '<?= $_SESSION['alert_title'] ?? 'Info' ?>',
                text: '<?= $_SESSION['alert_message'] ?>',
                timer: 4000,
                timerProgressBar: true,
                showConfirmButton: true,
                confirmButtonColor: '#3085d6'
            });
        <?php
            unset($_SESSION['alert_message']);
            unset($_SESSION['alert_icon']);
            unset($_SESSION['alert_title']);
        endif;
        ?>

        document.getElementById('file_excel').addEventListener('change', function(e) {
            const file = this.files[0];
            const info = document.getElementById('fileInfo');
            if (file) {
                info.innerHTML = `
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i> ${file.name} (${(file.size/1024).toFixed(2)} KB)
                    </span>
                `;
            }
        });

        function downloadTemplate() {
            const headers = ['Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Jabatan', 'Golongan', 'Status Kepegawaian', 'Masa Kerja', 'Unit', 'Role'];
            const sample1 = ['Budi Santoso', 'Jakarta', '15/05/1990', 'Jl. Merdeka No. 10', 'Kepala Sekolah', 'III/B', 'Mitra', '10 Tahun', 'SMA', 'staf'];
            const sample2 = ['Siti Rahayu', 'Bandung', '20/08/1985', 'Jl. Sudirman No. 5', 'Wakil Kepala', 'III/C', 'Honorer', '15 Tahun', 'SMP', 'staf'];
            const sample3 = ['Ahmad Fauzi', 'Bogor', '10/03/1992', 'Jl. Pahlawan No. 8', 'Guru', 'II/D', 'Magang', '5 Tahun', 'SD', 'staf'];

            let csv = headers.join(',') + '\n';
            csv += sample1.join(',') + '\n';
            csv += sample2.join(',') + '\n';
            csv += sample3.join(',') + '\n';

            const blob = new Blob(['\uFEFF' + csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'template_pegawai.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Template CSV telah didownload. Buka dengan Excel untuk mengisi data.',
                timer: 3000,
                timerProgressBar: true
            });
        }
    </script>
</body>

</html>