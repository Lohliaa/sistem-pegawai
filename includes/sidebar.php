<?php
// File: includes/sidebar.php
// Sidebar terpusat untuk konsistensi semua halaman
// Cara pakai: sertakan file ini di dalam <body> halaman masing-masing
if (!isset($current_page)) {
    $current_page = '';
}
$role = $_SESSION['role'] ?? '';
?>
<style>
    .sidebar {
        background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
        min-height: 100vh;
        padding: 20px;
        color: #fff;
        width: 100%;
        box-sizing: border-box;
    }

    .sidebar .brand {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #34495e;
    }

    .sidebar .brand h4 {
        color: #fff;
        margin-bottom: 4px;
    }

    .sidebar .brand small {
        color: #bdc3c7;
    }

    .sidebar a {
        color: #fff;
        text-decoration: none;
        display: block;
        padding: 10px 15px;
        margin: 5px 0;
        border-radius: 5px;
        transition: background .2s;
    }

    .sidebar a:hover {
        background: #34495e;
    }

    .sidebar a.active {
        background: #3498db;
    }

    .sidebar .section-title {
        color: #7f8c8d;
        font-size: 0.75rem;
        text-transform: uppercase;
        padding: 15px 15px 4px 15px;
        letter-spacing: 0.5px;
    }

    .sidebar .section-divider {
        border-color: #7f8c8d;
        margin: 5px 15px;
        opacity: 0.4;
    }

    .sidebar .logout-link {
        color: #e74c3c !important;
        margin-top: 30px;
    }

    .sidebar .logout-link:hover {
        background: #c0392b;
        color: #fff !important;
    }
</style>

<div class="sidebar">
    <div class="brand">
        <h4><i class="bi bi-building"></i> SIPS</h4>
        <small>Sistem Informasi Pegawai</small>
    </div>

    <!-- Menu Utama -->
    <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-house"></i> Dashboard
    </a>

    <!-- Section Pengajuan (semua role) -->
    <div class="section-title">Pengajuan</div>
    <hr class="section-divider">
    <?php if ($role == 'admin'): ?>
        <a href="pengajuan_admin.php" class="<?= $current_page == 'pengajuan_admin.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan
        </a>
    <?php elseif ($role == 'staf'): ?>
        <a href="pengajuan_staf.php" class="<?= $current_page == 'pengajuan_staf.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Pengajuan
        </a>
        <a href="daftar_pengajuan.php" class="<?= $current_page == 'daftar_pengajuan.php' ? 'active' : '' ?>">
            <i class="bi bi-list-check"></i> Daftar Pengajuan Saya
        </a>
    <?php elseif ($role == 'kanit' || $role == 'kabid'): ?>
        <a href="persetujuan_kanit.php" class="<?= $current_page == 'persetujuan_kanit.php' ? 'active' : '' ?>">
            <i class="bi bi-check-circle"></i> Persetujuan Pengajuan
        </a>
    <?php endif; ?>

    <!-- Menu khusus Admin (lainnya) -->
    <?php if ($role == 'admin'): ?>
        <a href="profile_pegawai.php" class="<?= $current_page == 'profile_pegawai.php' ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i> Profile Pegawai
        </a>
        <a href="setup_users.php" class="<?= $current_page == 'setup_users.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-spreadsheet"></i> Manajemen User
        </a>
        <a href="data_mou.php" class="<?= $current_page == 'data_mou.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-ruled"></i> Data MOU
        </a>
        <a href="data_sk.php" class="<?= $current_page == 'data_sk.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Data SK
        </a>
    <?php endif; ?>

    <!-- Sekat Penilaian Kinerja - Visible untuk SEMUA role -->
    <div class="section-title">Penilaian Kinerja</div>
    <hr class="section-divider">
    <a href="kinerja_status.php" class="<?= $current_page == 'kinerja_status.php' ? 'active' : '' ?>">
        <i class="bi bi-person-check"></i> Status
    </a>
    <a href="kinerja_periode.php" class="<?= $current_page == 'kinerja_periode.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar3"></i> Periode
    </a>
    <a href="kinerja_pejabat.php" class="<?= $current_page == 'kinerja_pejabat.php' ? 'active' : '' ?>">
        <i class="bi bi-award"></i> Pejabat
    </a>
    <a href="kinerja_bahan.php" class="<?= $current_page == 'kinerja_bahan.php' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-text"></i> Bahan Penilaian
    </a>
    <a href="form_penilaian.php" class="<?= $current_page == 'form_penilaian.php' ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check"></i> Form Penilaian
    </a>
    <a href="laporan_penilaian.php" class="<?= $current_page == 'laporan_penilaian.php' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-bar-graph"></i> Laporan Penilaian
    </a>

    <a href="logout.php" class="logout-link">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</div>