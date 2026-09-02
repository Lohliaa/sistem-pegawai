<?php
// File: includes/sidebar_v2.php
// Sidebar versi fixed (untuk file-file lama yang sudah ada fixed layout)

if (!isset($current_page)) { $current_page = ''; }
$role = $_SESSION['role'] ?? '';

// Hanya load CSS sekali
if (!defined('SIDEBAR_V2_CSS_LOADED')) {
    define('SIDEBAR_V2_CSS_LOADED', true);
    echo '<style>
        .sidebar-v2 {
            background: #2c3e50;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            width: 220px;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
        }
        .sidebar-v2 .brand { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #34495e; }
        .sidebar-v2 .brand h4 { color: #fff; margin-bottom: 4px; }
        .sidebar-v2 .brand small { color: #bdc3c7; }
        .sidebar-v2 a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 15px;
            margin: 4px 0;
            border-radius: 5px;
            transition: background .2s;
        }
        .sidebar-v2 a i { margin-right: 10px; }
        .sidebar-v2 a:hover { background: #34495e; }
        .sidebar-v2 a.active { background: #3498db; }
        .sidebar-v2 .section-title {
            color: #7f8c8d;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 15px 15px 4px 15px;
            letter-spacing: 0.5px;
        }
        .sidebar-v2 .section-divider {
            border-color: #7f8c8d;
            margin: 5px 15px;
            opacity: 0.4;
        }
        .sidebar-v2 .logout-link { color: #e74c3c !important; margin-top: 30px; }
        .sidebar-v2 .logout-link:hover { background: #c0392b; color: #fff !important; }
    </style>';
}
?>

<div class="sidebar-v2">
    <div class="brand">
        <h4><i class="bi bi-building"></i> SIPS</h4>
        <small>Sistem Informasi Pegawai</small>
    </div>

    <a href="index.php" class="<?= $current_page=='index.php'?'active':'' ?>">
        <i class="bi bi-house"></i> <span>Dashboard</span>
    </a>

    <?php if ($role == 'admin'): ?>
        <a href="pengajuan_admin.php" class="<?= $current_page=='pengajuan_admin.php'?'active':'' ?>">
            <i class="bi bi-file-earmark-text"></i> <span>Manajemen Pengajuan</span>
        </a>
        <a href="profile_pegawai.php" class="<?= $current_page=='profile_pegawai.php'?'active':'' ?>">
            <i class="bi bi-person-badge"></i> <span>Profile Pegawai</span>
        </a>
        <a href="setup_users.php" class="<?= $current_page=='setup_users.php'?'active':'' ?>">
            <i class="bi bi-file-earmark-spreadsheet"></i> <span>Manajemen User</span>
        </a>

        <div class="section-title">Penilaian Kinerja</div>
        <hr class="section-divider">
        <a href="kinerja_status.php" class="<?= $current_page=='kinerja_status.php'?'active':'' ?>">
            <i class="bi bi-person-check"></i> <span>Status</span>
        </a>
        <a href="kinerja_periode.php" class="<?= $current_page=='kinerja_periode.php'?'active':'' ?>">
            <i class="bi bi-calendar3"></i> <span>Periode</span>
        </a>
        <a href="kinerja_pejabat.php" class="<?= $current_page=='kinerja_pejabat.php'?'active':'' ?>">
            <i class="bi bi-award"></i> <span>Pejabat</span>
        </a>
        <a href="kinerja_bahan.php" class="<?= $current_page=='kinerja_bahan.php'?'active':'' ?>">
            <i class="bi bi-file-earmark-text"></i> <span>Bahan Penilaian</span>
        </a>
    <?php endif; ?>

    <?php if ($role == 'staf'): ?>
        <a href="pengajuan_staf.php" class="<?= $current_page=='pengajuan_staf.php'?'active':'' ?>">
            <i class="bi bi-file-earmark-text"></i> <span>Pengajuan</span>
        </a>
        <a href="daftar_pengajuan.php" class="<?= $current_page=='daftar_pengajuan.php'?'active':'' ?>">
            <i class="bi bi-list-check"></i> <span>Daftar Pengajuan</span>
        </a>
    <?php elseif ($role == 'kanit' || $role == 'kabid'): ?>
        <a href="persetujuan_kanit.php" class="<?= $current_page=='persetujuan_kanit.php'?'active':'' ?>">
            <i class="bi bi-check-circle"></i> <span>Persetujuan Pengajuan</span>
        </a>
        <a href="approval.php" class="<?= $current_page=='approval.php'?'active':'' ?>">
            <i class="bi bi-check2-circle"></i> <span>Persetujuan</span>
        </a>
    <?php endif; ?>

    <a href="logout.php" class="logout-link">
        <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
    </a>
</div>