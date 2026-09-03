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

// Proses Update Role
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);

    // Update role di users
    $query = "UPDATE users SET role = '$new_role' WHERE id = $user_id";

    if ($conn->query($query)) {
        // Update role di pegawai juga
        $conn->query("UPDATE pegawai SET role = '$new_role' WHERE user_id = $user_id");

        $_SESSION['alert_message'] = "Role user berhasil diupdate!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Gagal update role: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Proses Reset Password
if (isset($_GET['reset_password'])) {
    $user_id = (int)$_GET['reset_password'];
    $new_password = md5('123456');

    $query = "UPDATE users SET password = '$new_password' WHERE id = $user_id";

    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Password user berhasil direset ke '123456'!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Gagal reset password: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Proses Hapus User
if (isset($_GET['delete_user'])) {
    $user_id = (int)$_GET['delete_user'];

    // Cek apakah user sedang login
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['alert_message'] = "Tidak bisa menghapus user yang sedang login!";
        $_SESSION['alert_icon'] = "warning";
        $_SESSION['alert_title'] = "Peringatan!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Ambil username untuk pesan
    $user_data = $conn->query("SELECT username FROM users WHERE id = $user_id")->fetch_assoc();
    $username = $user_data['username'] ?? 'Unknown';

    // Cek apakah user memiliki data pegawai
    $pegawai_check = $conn->query("SELECT id, nama FROM pegawai WHERE user_id = $user_id");
    $pegawai_data = $pegawai_check->fetch_assoc();

    // Hapus user (ON DELETE CASCADE akan menghapus pegawai jika ada foreign key)
    $query = "DELETE FROM users WHERE id = $user_id";

    if ($conn->query($query)) {
        $message = "User '$username' berhasil dihapus!";
        if ($pegawai_data) {
            $message .= " Data pegawai '{$pegawai_data['nama']}' juga ikut terhapus.";
        }
        $_SESSION['alert_message'] = $message;
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Gagal menghapus user: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Proses Tambah User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $tempat = mysqli_real_escape_string($conn, $_POST['tempat'] ?? '');
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir'] ?? '');
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan'] ?? '');
    $golongan = mysqli_real_escape_string($conn, $_POST['golongan'] ?? '');
    $status_kepegawaian = mysqli_real_escape_string($conn, $_POST['status_kepegawaian'] ?? '');
    $masa_kerja = mysqli_real_escape_string($conn, $_POST['masa_kerja'] ?? '');
    $unit = mysqli_real_escape_string($conn, $_POST['unit'] ?? '');

    // Cek username sudah ada
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $_SESSION['alert_message'] = "Username '$username' sudah digunakan!";
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Mulai transaction
    $conn->begin_transaction();

    try {
        // Insert user
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        if (!$conn->query($query)) {
            throw new Exception("Gagal menambah user: " . $conn->error);
        }
        $user_id = $conn->insert_id;

        // Jika ada data pegawai, insert juga
        if (!empty($nama)) {
            $query = "INSERT INTO pegawai (user_id, nama, tempat, tanggal_lahir, alamat, jabatan, golongan, status_kepegawaian, masa_kerja, unit, role) 
                      VALUES ($user_id, '$nama', '$tempat', '$tanggal_lahir', '$alamat', '$jabatan', '$golongan', '$status_kepegawaian', '$masa_kerja', '$unit', '$role')";
            if (!$conn->query($query)) {
                throw new Exception("Gagal menambah data pegawai: " . $conn->error);
            }
            $message = "User '$username' dan data pegawai '$nama' berhasil ditambahkan!";
        } else {
            $message = "User '$username' berhasil ditambahkan! (Silakan hubungkan dengan data pegawai)";
        }

        $conn->commit();
        $_SESSION['alert_message'] = $message;
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['alert_message'] = $e->getMessage();
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Proses Hubungkan User dengan Pegawai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hubungkan_pegawai'])) {
    $user_id = (int)$_POST['user_id'];
    $pegawai_id = (int)$_POST['pegawai_id'];

    if ($pegawai_id > 0) {
        // Update pegawai dengan user_id
        $query = "UPDATE pegawai SET user_id = $user_id WHERE id = $pegawai_id";
        if ($conn->query($query)) {
            // Update role di pegawai sesuai role user
            $user_data = $conn->query("SELECT role FROM users WHERE id = $user_id")->fetch_assoc();
            if ($user_data) {
                $conn->query("UPDATE pegawai SET role = '{$user_data['role']}' WHERE id = $pegawai_id");
            }
            $_SESSION['alert_message'] = "User berhasil dihubungkan dengan data pegawai!";
            $_SESSION['alert_icon'] = "success";
            $_SESSION['alert_title'] = "Berhasil!";
        } else {
            $_SESSION['alert_message'] = "Gagal menghubungkan: " . $conn->error;
            $_SESSION['alert_icon'] = "error";
            $_SESSION['alert_title'] = "Gagal!";
        }
    } else {
        $_SESSION['alert_message'] = "Silakan pilih data pegawai!";
        $_SESSION['alert_icon'] = "warning";
        $_SESSION['alert_title'] = "Peringatan!";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Proses Lepaskan Hubungan User dengan Pegawai
if (isset($_GET['lepas_pegawai'])) {
    $user_id = (int)$_GET['lepas_pegawai'];
    $query = "UPDATE pegawai SET user_id = NULL WHERE user_id = $user_id";

    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "Hubungan user dengan pegawai berhasil dilepaskan!";
        $_SESSION['alert_icon'] = "success";
        $_SESSION['alert_title'] = "Berhasil!";
    } else {
        $_SESSION['alert_message'] = "Gagal melepaskan hubungan: " . $conn->error;
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// ===== PROSES IMPORT USER =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_user'])) {
    if (isset($_FILES['file_user']) && $_FILES['file_user']['error'] == 0) {
        $file = $_FILES['file_user']['tmp_name'];
        $extension = strtolower(pathinfo($_FILES['file_user']['name'], PATHINFO_EXTENSION));

        if ($extension == 'csv' || $extension == 'xls' || $extension == 'xlsx') {
            $row = 1;
            $imported = 0;
            $errors = [];
            $users_created = 0;
            $pegawai_created = 0;

            if (($handle = fopen($file, "r")) !== FALSE) {
                // Skip header
                fgetcsv($handle, 1000, ",");

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $num = count($data);
                    // Minimal 3 kolom: username, password, role
                    if ($num >= 3) {
                        $username = mysqli_real_escape_string($conn, trim($data[0]));
                        $password_input = trim($data[1] ?? '123456');
                        $password = md5($password_input);
                        $role = mysqli_real_escape_string($conn, trim($data[2] ?? 'staf'));

                        // Data pegawai opsional
                        $nama = isset($data[3]) ? mysqli_real_escape_string($conn, trim($data[3])) : '';
                        $tempat = isset($data[4]) ? mysqli_real_escape_string($conn, trim($data[4])) : '';
                        $tanggal_lahir = isset($data[5]) ? mysqli_real_escape_string($conn, trim($data[5])) : '';
                        $alamat = isset($data[6]) ? mysqli_real_escape_string($conn, trim($data[6])) : '';
                        $jabatan = isset($data[7]) ? mysqli_real_escape_string($conn, trim($data[7])) : '';
                        $golongan = isset($data[8]) ? mysqli_real_escape_string($conn, trim($data[8])) : '';
                        $status_kepegawaian = isset($data[9]) ? mysqli_real_escape_string($conn, trim($data[9])) : '';
                        $masa_kerja = isset($data[10]) ? mysqli_real_escape_string($conn, trim($data[10])) : '';
                        $unit = isset($data[11]) ? mysqli_real_escape_string($conn, trim($data[11])) : '';

                        // Validasi username
                        if (empty($username)) {
                            $errors[] = "Baris $row: Username kosong!";
                            $row++;
                            continue;
                        }

                        // Konversi tanggal
                        if (!empty($tanggal_lahir) && strpos($tanggal_lahir, '/') !== false) {
                            $parts = explode('/', $tanggal_lahir);
                            if (count($parts) == 3) {
                                $tanggal_lahir = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                            }
                        }

                        // Cek username sudah ada
                        $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
                        if ($check->num_rows > 0) {
                            $errors[] = "Baris $row: Username '$username' sudah digunakan!";
                            $row++;
                            continue;
                        }

                        // Insert user
                        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
                        if ($conn->query($query)) {
                            $user_id = $conn->insert_id;
                            $users_created++;

                            // Jika ada data pegawai, insert juga
                            if (!empty($nama)) {
                                $query = "INSERT INTO pegawai (user_id, nama, tempat, tanggal_lahir, alamat, jabatan, golongan, status_kepegawaian, masa_kerja, unit, role) 
                                          VALUES ($user_id, '$nama', '$tempat', '$tanggal_lahir', '$alamat', '$jabatan', '$golongan', '$status_kepegawaian', '$masa_kerja', '$unit', '$role')";
                                if ($conn->query($query)) {
                                    $pegawai_created++;
                                } else {
                                    $errors[] = "Baris $row: Gagal insert pegawai - " . $conn->error;
                                }
                            }
                            $imported++;
                        } else {
                            $errors[] = "Baris $row: Gagal insert user - " . $conn->error;
                        }
                    } else {
                        $errors[] = "Baris $row: Data tidak lengkap (minimal 3 kolom)!";
                    }
                    $row++;
                }
                fclose($handle);
            }

            // Pesan hasil import
            if ($imported > 0) {
                $message = "Berhasil mengimport $imported user! ($users_created user baru, $pegawai_created data pegawai)";
                if (!empty($errors)) {
                    $message .= " | Error: " . implode(", ", $errors);
                }
                $_SESSION['alert_message'] = $message;
                $_SESSION['alert_icon'] = "success";
                $_SESSION['alert_title'] = "Berhasil!";
            } else {
                $message = "Tidak ada data yang berhasil diimport. " . implode(", ", $errors);
                $_SESSION['alert_message'] = $message;
                $_SESSION['alert_icon'] = "warning";
                $_SESSION['alert_title'] = "Peringatan!";
            }
        } else {
            $_SESSION['alert_message'] = "Format file tidak didukung. Gunakan format CSV.";
            $_SESSION['alert_icon'] = "error";
            $_SESSION['alert_title'] = "Gagal!";
        }
    } else {
        $_SESSION['alert_message'] = "Silakan pilih file terlebih dahulu!";
        $_SESSION['alert_icon'] = "warning";
        $_SESSION['alert_title'] = "Peringatan!";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manajemen User & Pegawai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
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

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .status-connected {
            color: #27ae60;
        }

        .status-disconnected {
            color: #e74c3c;
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
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php $current_page = 'setup_users.php'; include 'includes/sidebar.php'; ?>
            </div>
            <div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
                <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people-gear text-primary"></i> Manajemen User & Pegawai</h2>
            <span class="badge bg-danger p-2">
                <i class="bi bi-person-badge"></i> <?= strtoupper($_SESSION['role']) ?>
            </span>
        </div>

        <!-- Alert -->
        <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_icon'] == 'success' ? 'success' : ($_SESSION['alert_icon'] == 'warning' ? 'warning' : 'danger') ?> alert-dismissible fade show">
                <i class="bi <?= $_SESSION['alert_icon'] == 'success' ? 'bi-check-circle' : ($_SESSION['alert_icon'] == 'warning' ? 'bi-exclamation-triangle' : 'bi-x-circle') ?>"></i>
                <?= $_SESSION['alert_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php
            unset($_SESSION['alert_message']);
            unset($_SESSION['alert_icon']);
            unset($_SESSION['alert_title']);
        endif;
        ?>

        <!-- Tombol Aksi -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                <i class="bi bi-person-plus"></i> Tambah User
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hubungkanModal">
                <i class="bi bi-link"></i> Hubungkan User dengan Pegawai
            </button>
            <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-excel"></i> Import User
            </button>
            <a href="export_users.php" class="btn btn-secondary">
                <i class="bi bi-download"></i> Export User
            </a>
        </div>

        <!-- Daftar Users -->
        <div class="card card-custom">
            <div class="card-header bg-info text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0"><i class="bi bi-people"></i> Daftar Users & Keterhubungan dengan Pegawai</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tableUsers">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Terhubung dengan Pegawai</th>
                                <th>Ubah Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("
                                SELECT u.*, p.id as pegawai_id, p.nama as pegawai_nama 
                                FROM users u 
                                LEFT JOIN pegawai p ON u.id = p.user_id 
                                ORDER BY u.id
                            ");
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?=
                                                                $row['role'] == 'admin' ? 'danger' : ($row['role'] == 'kanit' ? 'warning' : ($row['role'] == 'kabid' ? 'info' : 'success'))
                                                                ?>">
                                            <?= strtoupper($row['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['pegawai_id']): ?>
                                            <span class="badge bg-success status-connected">
                                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($row['pegawai_nama']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger status-disconnected">
                                                <i class="bi bi-x-circle"></i> Belum terhubung
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <div class="input-group input-group-sm">
                                                <select name="new_role" class="form-select form-select-sm" style="width: auto;">
                                                    <option value="staf" <?= $row['role'] == 'staf' ? 'selected' : '' ?>>STAF</option>
                                                    <option value="kanit" <?= $row['role'] == 'kanit' ? 'selected' : '' ?>>KANIT</option>
                                                    <option value="kabid" <?= $row['role'] == 'kabid' ? 'selected' : '' ?>>KABID</option>
                                                    <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : '' ?>>ADMIN</option>
                                                </select>
                                                <button type="submit" name="update_role" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <button onclick="resetPassword(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')"
                                            class="btn btn-warning btn-sm" title="Reset Password">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <?php if ($row['pegawai_id']): ?>
                                            <a href="?lepas_pegawai=<?= $row['id'] ?>"
                                                class="btn btn-secondary btn-sm"
                                                title="Lepaskan hubungan dengan pegawai"
                                                onclick="return confirm('Lepaskan hubungan user dengan pegawai <?= htmlspecialchars($row['pegawai_nama']) ?>?')">
                                                <i class="bi bi-link-45deg"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button onclick="deleteUser(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>', <?= $row['pegawai_id'] ? 'true' : 'false' ?>)"
                                            class="btn btn-danger btn-sm" title="Hapus User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah User -->
        <div class="modal fade" id="tambahUserModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus"></i> Tambah User & Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" value="123456" required>
                                    <small class="text-muted">Default: 123456</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select" required>
                                        <option value="staf">STAF</option>
                                        <option value="kanit">KANIT</option>
                                        <option value="kabid">KABID</option>
                                        <option value="admin">ADMIN</option>
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
                                <div class="col-12">
                                    <hr>
                                    <h6><i class="bi bi-person-badge"></i> Data Pegawai (Opsional)</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tempat" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jabatan</label>
                                    <input type="text" name="jabatan" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Golongan</label>
                                    <input type="text" name="golongan" class="form-control" placeholder="Contoh: III/B">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status Kepegawaian</label>
                                    <select name="status_kepegawaian" class="form-select">
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
                                    <label class="form-label">Masa Kerja</label>
                                    <input type="text" name="masa_kerja" class="form-control" placeholder="Contoh: 10 Tahun">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah_user" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Hubungkan User dengan Pegawai -->
        <div class="modal fade" id="hubungkanModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-link"></i> Hubungkan User dengan Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Pilih User <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">Pilih User</option>
                                    <?php
                                    $users = $conn->query("
                                        SELECT u.id, u.username, u.role 
                                        FROM users u 
                                        LEFT JOIN pegawai p ON u.id = p.user_id 
                                        WHERE p.user_id IS NULL
                                        ORDER BY u.username
                                    ");
                                    while ($user = $users->fetch_assoc()):
                                    ?>
                                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?> (<?= strtoupper($user['role']) ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Hanya user yang belum terhubung dengan pegawai yang ditampilkan</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pilih Data Pegawai <span class="text-danger">*</span></label>
                                <select name="pegawai_id" class="form-select" required>
                                    <option value="">Pilih Pegawai</option>
                                    <?php
                                    $pegawai = $conn->query("
                                        SELECT id, nama, jabatan, unit 
                                        FROM pegawai 
                                        WHERE user_id IS NULL OR user_id = 0
                                        ORDER BY nama
                                    ");
                                    while ($p = $pegawai->fetch_assoc()):
                                    ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['jabatan']) ?> (<?= htmlspecialchars($p['unit']) ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Hanya pegawai yang belum memiliki user yang ditampilkan</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="hubungkan_pegawai" class="btn btn-success">
                                <i class="bi bi-link"></i> Hubungkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== MODAL IMPORT USER ===== -->
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-file-excel"></i> Import User dari Excel/CSV</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="upload-area" onclick="document.getElementById('file_user').click()">
                                <i class="bi bi-cloud-upload"></i>
                                <p class="mt-2">Klik atau drag file Excel/CSV disini</p>
                                <small class="text-muted">Format: .csv, .xlsx, atau .xls</small>
                                <input type="file" name="file_user" id="file_user" class="d-none" accept=".csv,.xlsx,.xls" required>
                            </div>
                            <div id="fileInfo" class="mt-2 text-center"></div>

                            <hr>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Format file Excel/CSV:
                                <br>
                                <small>
                                    <strong>Kolom 1:</strong> Username <span class="text-danger">*</span> |
                                    <strong>Kolom 2:</strong> Password |
                                    <strong>Kolom 3:</strong> Role (staf/kanit/kabid/admin) |
                                    <strong>Kolom 4:</strong> Nama |
                                    <strong>Kolom 5:</strong> Tempat Lahir |
                                    <strong>Kolom 6:</strong> Tanggal Lahir |
                                    <strong>Kolom 7:</strong> Alamat |
                                    <strong>Kolom 8:</strong> Jabatan |
                                    <strong>Kolom 9:</strong> Golongan |
                                    <strong>Kolom 10:</strong> Status Kepegawaian |
                                    <strong>Kolom 11:</strong> Masa Kerja |
                                    <strong>Kolom 12:</strong> Unit
                                </small>
                                <br>
                                <small class="text-muted">Minimal 3 kolom (Username, Password, Role)</small>
                                <br>
                                <a href="#" class="btn btn-sm btn-outline-primary mt-2" onclick="downloadTemplateUser()">
                                    <i class="bi bi-download"></i> Download Template
                                </a>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="import_user" class="btn btn-success">
                                <i class="bi bi-upload"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('#tableUsers').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                    },
                    "pageLength": 10,
                    "order": [
                        [0, "asc"]
                    ],
                    "columnDefs": [{
                        "orderable": false,
                        "targets": [3, 4, 5]
                    }]
                });
            }
        });

        // File input handler
        document.getElementById('file_user').addEventListener('change', function(e) {
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

        // Download template import user
        function downloadTemplateUser() {
            const headers = ['Username', 'Password', 'Role', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Jabatan', 'Golongan', 'Status Kepegawaian', 'Masa Kerja', 'Unit'];
            const sample1 = ['admin2', '123456', 'admin', 'Admin Dua', 'Jakarta', '15/05/1990', 'Jl. Merdeka No. 10', 'Kepala', 'III/B', 'PNS', '10 Tahun', 'SMA'];
            const sample2 = ['staf2', '123456', 'staf', 'Staf Dua', 'Bandung', '20/08/1985', 'Jl. Sudirman No. 5', 'Staf', 'II/C', 'Honorer', '5 Tahun', 'SMP'];

            let csv = '\uFEFF' + headers.join(',') + '\n';
            csv += sample1.join(',') + '\n';
            csv += sample2.join(',') + '\n';

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'template_import_user.csv';
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

        function resetPassword(userId, username) {
            Swal.fire({
                title: 'Reset Password',
                text: `Reset password untuk user "${username}"? Password akan direset ke "123456"`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?reset_password=${userId}`;
                }
            });
        }

        function deleteUser(userId, username, hasPegawai) {
            let message = `Apakah Anda yakin ingin menghapus user "${username}"?`;
            if (hasPegawai) {
                message += '\n\n⚠️ Data pegawai yang terhubung juga akan ikut terhapus!';
            }

            Swal.fire({
                title: 'Hapus User',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?delete_user=${userId}`;
                }
            });
        }
    </script>
            </div>
        </div>
    </div>
</body>

</html>
