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
    
    $query = "UPDATE users SET role = '$new_role' WHERE id = $user_id";
    
    if ($conn->query($query)) {
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
    
    $query = "DELETE FROM users WHERE id = $user_id";
    
    if ($conn->query($query)) {
        $_SESSION['alert_message'] = "User berhasil dihapus!";
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
    
    // Cek username sudah ada
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $_SESSION['alert_message'] = "Username '$username' sudah digunakan!";
        $_SESSION['alert_icon'] = "error";
        $_SESSION['alert_title'] = "Gagal!";
    } else {
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        if ($conn->query($query)) {
            $_SESSION['alert_message'] = "User '$username' berhasil ditambahkan!";
            $_SESSION['alert_icon'] = "success";
            $_SESSION['alert_title'] = "Berhasil!";
        } else {
            $_SESSION['alert_message'] = "Gagal menambah user: " . $conn->error;
            $_SESSION['alert_icon'] = "error";
            $_SESSION['alert_title'] = "Gagal!";
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen User</title>
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
        .sidebar a i { margin-right: 12px; width: 20px; font-size: 1.2rem; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(5px); }
        .sidebar a.active { background: linear-gradient(135deg, #3498db, #2980b9); color: #fff; box-shadow: 0 5px 15px rgba(52,152,219,0.3); }
        
        .main-content {
            margin-left: 250px;
            padding: 20px 30px;
            background: #f4f6f9;
            min-height: 100vh;
        }
        
        .card-custom {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: none;
            transition: all 0.3s;
            background: #fff;
        }
        .card-custom:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .brand h4, .sidebar .brand small, .sidebar a span { display: none; }
            .sidebar a { justify-content: center; padding: 12px; }
            .sidebar a i { margin-right: 0; font-size: 1.5rem; }
            .main-content { margin-left: 70px; padding: 15px; }
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
        <a href="profile_pegawai.php"><i class="bi bi-people"></i> <span>Profile Pegawai</span></a>
        <a href="setup_users.php" class="active"><i class="bi bi-people-gear"></i> <span>Manajemen User</span></a>
        <a href="ajuan_mou.php"><i class="bi bi-file-text"></i> <span>Ajuan MoU</span></a>
        <a href="ajuan_sk.php"><i class="bi bi-file-check"></i> <span>Ajuan SK</span></a>
        <a href="logout.php" style="margin-top: 30px; color: #e74c3c;"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people-gear text-primary"></i> Manajemen User</h2>
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

        <!-- Tombol Tambah User -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
            <i class="bi bi-person-plus"></i> Tambah User
        </button>

        <!-- Daftar Users -->
        <div class="card card-custom">
            <div class="card-header bg-info text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="mb-0"><i class="bi bi-people"></i> Daftar Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tableUsers">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Ubah Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT id, username, role FROM users ORDER BY id");
                            while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $row['role'] == 'admin' ? 'danger' : 
                                        ($row['role'] == 'kanit' ? 'warning' : 
                                        ($row['role'] == 'kabid' ? 'info' : 'success')) 
                                    ?>">
                                        <?= strtoupper($row['role']) ?>
                                    </span>
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
                                    <button onclick="deleteUser(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')" 
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
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus"></i> Tambah User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" value="123456" required>
                                <small class="text-muted">Default: 123456</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="staf">STAF</option>
                                    <option value="kanit">KANIT</option>
                                    <option value="kabid">KABID</option>
                                    <option value="admin">ADMIN</option>
                                </select>
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
    </div>

    <!-- SCRIPT - PASTIKAN URUTANNYA BENAR -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // PASTIKAN jQuery SUDAH LOAD SEBELUM MENJALANKAN DataTables
        $(document).ready(function() {
            // Cek apakah DataTables tersedia
            if ($.fn.DataTable) {
                $('#tableUsers').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                    },
                    "pageLength": 10,
                    "order": [[0, "asc"]],
                    "columnDefs": [
                        { "orderable": false, "targets": [3, 4] }
                    ]
                });
            } else {
                console.error("DataTables tidak ditemukan! Pastikan library sudah di-load.");
                // Fallback: tampilkan alert
                alert("DataTables tidak ditemukan. Refresh halaman atau cek koneksi internet.");
            }
        });

        // Fungsi reset password
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

        // Fungsi hapus user
        function deleteUser(userId, username) {
            Swal.fire({
                title: 'Hapus User',
                text: `Apakah Anda yakin ingin menghapus user "${username}"?`,
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
</body>
</html>