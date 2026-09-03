<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$user_id = $_SESSION['user_id'];

// Ambil data pegawai yang login
$sql_pegawai = "SELECT * FROM pegawai WHERE user_id = $user_id LIMIT 1";
$res_pegawai = $conn->query($sql_pegawai);
$pegawai = $res_pegawai && $res_pegawai->num_rows > 0 ? $res_pegawai->fetch_assoc() : null;

// Ambil daftar pejabat penilai
$list_pejabat = [];
$res_pejabat = $conn->query("SELECT * FROM pejabat_penilai ORDER BY nama");
if ($res_pejabat) while ($r = $res_pejabat->fetch_assoc()) $list_pejabat[] = $r;

// Ambil daftar periode
$list_periode = [];
$res_periode = $conn->query("SELECT * FROM periode_penilaian ORDER BY tahun DESC, FIELD(nama_kuartal,'Q1','Q2','Q3','Q4') DESC");
if ($res_periode) while ($r = $res_periode->fetch_assoc()) $list_periode[] = $r;

// Handle POST identitas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_identity'])) {
    $pejabat_id = (int)($_POST['pejabat_id'] ?? 0);
    $periode_id = (int)($_POST['periode_id'] ?? 0);
    if ($pejabat_id > 0 && $periode_id > 0) {
        $_SESSION['form_pejabat_id'] = $pejabat_id;
        $_SESSION['form_periode_id'] = $periode_id;
        $_SESSION['msg'] = 'Identitas penilaian disimpan!';
    } else {
        $_SESSION['msg'] = 'Pilih pejabat & periode terlebih dahulu!';
    }
    header('Location: form_penilaian.php'); exit();
}

$selected_pejabat_id = $_SESSION['form_pejabat_id'] ?? 0;
$selected_periode_id = $_SESSION['form_periode_id'] ?? 0;

$uraian = [
    'A' => ['judul' => 'ASPEK PROFESIONAL DAN PEDAGOGIK', 'items' => [
        1  => 'persiapan_tugas|Mempersiapkan dan mengerjakan dokumen perangkat pembelajaran',
        2  => 'pelaksanaan_tugas|Melaksanakan pembelajaran',
        3  => 'pelaksanaan_tugas_2|Pencapaian ketuntasan pembelajaran',
        4  => 'kompetensi|Memiliki kompetensi yang sesuai',
        5  => 'penilaian_hasil|Melakukan penilaian hasil pembelajaran',
        6  => 'supervisor_evelator|Mendukung pelaksanaan OKR yang melekat pada tupoksi',
        7  => 'layanan_peserta_didik|Memberikan layanan terhadap peserta didik',
        8  => 'layanan_orangtua_rekan|Memberikan layanan terhadap wali murid',
        9  => 'tugas_tambahan|Melaksanakan tugas tambahan yang melekat pada pelaksanaan kegiatan pokok sesuai dengan beban kerja',
        10 => 'uraian_tugas|Mempersiapkan kegiatan sesuai dengan uraian tugas',
    ]],
    'B' => ['judul' => 'ASPEK KOMITMEN KEISLAMAN', 'items' => [
        11 => 'sholat_berjamaah|Sholat wajib berjamah di masjid (laki-laki), awal waktu (perempuan)',
        12 => 'baca_quran_harian|Membaca Al Qur\'an dengan tartil min. 1 juz/hari',
        13 => 'hafalan_quran|Memiliki hafalan Al Qur\'an (min. 5 juz)',
        14 => 'kehadiran_bpi|Hadir dalam pertemuan pekanan Bina Pribadi Islam',
    ]],
    'C' => ['judul' => 'ASPEK KEPRIBADIAN DAN SOSIAL', 'items' => [
        15 => 'kejujuran|Memiliki Kejujuran',
        16 => 'tanggung_jawab|Tanggungjawab dan Ketuntasan Tugas',
        17 => 'interaksi_sosial|Interaksi Sosial',
    ]],
    'D' => ['judul' => 'ASPEK KEDISIPLINAN', 'items' => [
        18 => 'selalu_hadir|Selalu hadir',
        19 => 'datang_tepat_waktu|Datang tepat waktu',
        20 => 'tertib_berseragam|Berseragam sesuai ketentuan yang berlaku',
    ]],
    'E' => ['judul' => 'ASPEK KELEMBAGAAN', 'items' => [
        21 => 'koordinasi_kelembagaan|Mengikuti kegiatan koordinasi dan kelembagaan',
        22 => 'komitmen_kelembagaan|Komitmen kelembagaan',
    ]],
];
$total_nilai = 0; $jumlah_nilai = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['key'])) {
    $_SESSION['msg'] = '';
    $key = $_POST['key'] ?? '';
    $nilai = (int)($_POST['nilai'] ?? 0);
    $catatan = $_POST['catatan'] ?? '';
    if ($nilai < 0 || $nilai > 4) { $_SESSION['msg'] = 'Nilai harus 0-4'; }
    elseif (!$pegawai || empty($pegawai['id'])) { $_SESSION['msg'] = 'Data pegawai tidak ditemukan. Hubungi admin.'; }
    elseif ($selected_pejabat_id <= 0 || $selected_periode_id <= 0) { $_SESSION['msg'] = 'Pilih Pejabat Penilai & Periode terlebih dahulu!'; }
    else {
        $peg_id = $pegawai['id'];
        $pj_id = $selected_pejabat_id;
        $pr_id = $selected_periode_id;
        $key_esc = mysqli_real_escape_string($conn, $key);
        $cat_esc = mysqli_real_escape_string($conn, $catatan);
        // Atomic upsert - composite UNIQUE idx_3key (nama_kolom, pegawai_id, periode_id) handles duplicates
        $conn->query("INSERT INTO form_penilaian (nama_kolom, nilai, catatan, pegawai_id, pejabat_id, periode_id) VALUES ('$key_esc', $nilai, '$cat_esc', '$peg_id', '$pj_id', '$pr_id') ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan), pejabat_id = VALUES(pejabat_id), updated_at = CURRENT_TIMESTAMP");
        $_SESSION['msg'] = 'Nilai disimpan!';
    }
    header('Location: form_penilaian.php'); exit();
}
$data = [];
if ($selected_periode_id > 0 && isset($pegawai['id'])) {
    $peg_id = $pegawai['id'];
    $res = $conn->query("SELECT * FROM form_penilaian WHERE pegawai_id='$peg_id' AND periode_id='$selected_periode_id'");
    if ($res) while ($r = $res->fetch_assoc()) {
        $data[$r['nama_kolom']] = $r;
        if ($r['nilai'] > 0) { $total_nilai += $r['nilai']; $jumlah_nilai++; }
    }
}
$rata_rata = $jumlah_nilai > 0 ? round($total_nilai / $jumlah_nilai, 2) : 0;
$current_page = 'form_penilaian.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Form Penilaian Kinerja</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
body { font-size: 0.85rem; }
.fp { font-size: 0.82rem; }
.fp th, .fp td { padding: 4px 8px; vertical-align: middle; }
.fp .aspek { background: #2c3e50; color: #fff; font-weight: bold; padding: 6px 10px; font-size: 0.85rem; }
.fp .no-col { width: 30px; text-align: center; }
.fp .nilai-col { width: 60px; text-align: center; }
.fp .aksi-col { width: 50px; text-align: center; }
.fp select { font-size: 0.8rem; padding: 2px 4px; }
</style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-3" style="background:#f4f6f9;min-height:100vh;">
<h5 class="mb-1"><i class="bi bi-clipboard-check"></i> Form Penilaian Kinerja</h5>
<p class="text-muted small mb-2">5 Aspek, 22 item indikator penilaian.</p>
<?php if(isset($_SESSION['msg']) && $_SESSION['msg']): ?>
<div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
<i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['msg']) ?>
<button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['msg']); endif; ?>

<!-- IDENTITAS PENILAIAN -->
<div class="card shadow-sm fp mb-2">
<div class="card-header bg-secondary text-white py-2 d-flex justify-content-between align-items-center">
<h6 class="mb-0"><i class="bi bi-person-vcard"></i> IDENTITAS PENILAIAN</h6>
<button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#identitasModal">
<i class="bi bi-pencil-square"></i> <?= $selected_pejabat_id>0 && $selected_periode_id>0 ? 'Ubah' : 'Pilih' ?>
</button>
</div>
<div class="card-body p-2">
<div class="row g-2">
<div class="col-md-3">
<div class="border rounded p-2 h-100" style="background:#f8f9fa;">
<div class="small text-muted"><i class="bi bi-person"></i> Nama Pegawai</div>
<div class="fw-bold"><?= htmlspecialchars($pegawai['nama'] ?? $_SESSION['nama'] ?? '-') ?></div>
</div>
</div>
<div class="col-md-3">
<div class="border rounded p-2 h-100" style="background:#f8f9fa;">
<div class="small text-muted"><i class="bi bi-shield-check"></i> Status Kepegawaian</div>
<div class="fw-bold"><?= htmlspecialchars($pegawai['status_kepegawaian'] ?? '-') ?></div>
</div>
</div>
<div class="col-md-3">
<div class="border rounded p-2 h-100" style="background:#f8f9fa;">
<div class="small text-muted"><i class="bi bi-briefcase"></i> Jabatan</div>
<div class="fw-bold"><?= htmlspecialchars($pegawai['jabatan'] ?? '-') ?></div>
</div>
</div>
<div class="col-md-3">
<div class="border rounded p-2 h-100" style="background:#e3f2fd;">
<div class="small text-muted"><i class="bi bi-person-badge"></i> Pejabat Penilai</div>
<?php
$pejabat_nama = '-';
foreach ($list_pejabat as $pj) {
    if ($pj['id'] == $selected_pejabat_id) {
        $pejabat_nama = $pj['nama'] . ' (' . $pj['jabatan'] . ')';
        break;
    }
}
?>
<div class="fw-bold"><?= htmlspecialchars($pejabat_nama) ?></div>
</div>
</div>
<div class="col-md-3">
<div class="border rounded p-2 h-100" style="background:#fff3cd;">
<div class="small text-muted"><i class="bi bi-calendar3"></i> Periode Penilaian</div>
<?php
$periode_label = '-';
foreach ($list_periode as $pr) {
    if ($pr['id'] == $selected_periode_id) {
        $periode_label = ($pr['nama_kuartal'] ?? '') . ' - ' . ($pr['periode_bulan'] ?? '') . ' ' . ($pr['tahun'] ?? '');
        break;
    }
}
?>
<div class="fw-bold"><?= htmlspecialchars($periode_label) ?></div>
</div>
</div>
</div>
</div>
</div>
<div class="card shadow-sm fp">
<div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
<h6 class="mb-0"><i class="bi bi-list-check"></i> URAIAN PENILAIAN</h6>
<div>
<span class="badge bg-light text-dark me-2">Total: <?= $total_nilai ?></span>
<span class="badge bg-warning text-dark">Rata-rata: <?= $rata_rata ?></span>
</div>
</div>
<div class="card-body p-2">
<div class="table-responsive">
<table class="table table-bordered table-sm table-hover mb-0 fp">
<thead class="table-dark">
<tr>
<th class="no-col">No</th>
<th>URAIAN</th>
<th class="nilai-col">Nilai</th>
<th class="aksi-col">Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach($uraian as $huruf => $aspek): ?>
<tr><td colspan="4" class="aspek"><?= $huruf ?>. <?= htmlspecialchars($aspek['judul']) ?></td></tr>
<?php foreach($aspek['items'] as $no => $item):
    $parts = explode('|', $item);
    $key = $parts[0];
    $label = $parts[1];
    $val = $data[$key]['nilai'] ?? '';
?>
<tr>
<td class="no-col"><?= $no ?></td>
<td><?= htmlspecialchars($label) ?></td>
<td class="nilai-col">
<form method="POST" class="d-inline">
<input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
<input type="hidden" name="catatan" value="">
<select name="nilai" class="form-select form-select-sm" onchange="this.form.submit()" required>
<option value="0" <?= $val==='0'||$val===0?'selected':'' ?>>0</option>
<option value="1" <?= $val==1?'selected':'' ?>>1</option>
<option value="2" <?= $val==2?'selected':'' ?>>2</option>
<option value="3" <?= $val==3?'selected':'' ?>>3</option>
<option value="4" <?= $val==4?'selected':'' ?>>4</option>
</select>
</form>
</td>
<td class="aksi-col">
<button class="btn btn-sm btn-outline-secondary py-0 px-1" data-bs-toggle="modal" data-bs-target="#cat<?= $key ?>" title="Catatan">
<i class="bi bi-sticky"></i>
</button>
</td>
</tr>
<div class="modal fade" id="cat<?= $key ?>"><div class="modal-dialog modal-sm"><div class="modal-content">
<div class="modal-header py-2"><h6 class="modal-title small">No. <?= $no ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST">
<div class="modal-body p-2">
<input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
<input type="hidden" name="nilai" value="<?= $val !== '' && $val !== null ? $val : 1 ?>">
<p class="small mb-2"><strong><?= htmlspecialchars($label) ?></strong></p>
<div class="mb-2">
<label class="form-label small">Nilai:</label>
<select name="nilai" class="form-select form-select-sm">
<option value="1" <?= $val==1?'selected':'' ?>>1 - Kurang</option>
<option value="2" <?= $val==2?'selected':'' ?>>2 - Cukup</option>
<option value="3" <?= $val==3?'selected':'' ?>>3 - Baik</option>
<option value="4" <?= $val==4?'selected':'' ?>>4 - Sangat Baik</option>
</select>
</div>
<div class="mb-2">
<label class="form-label small">Catatan:</label>
<textarea name="catatan" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($data[$key]['catatan'] ?? '') ?></textarea>
</div>
</div>
<div class="modal-footer p-2"><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
</form>
</div></div></div>
<?php endforeach; endforeach; ?>
</tbody>
<tfoot class="table-secondary">
<tr>
<td colspan="2" class="text-end"><strong>TOTAL & RATA-RATA</strong></td>
<td class="nilai-col"><span class="badge bg-primary"><?= $total_nilai ?></span> / <span class="badge bg-warning text-dark"><?= $rata_rata ?></span></td>
<td></td>
</tr>
</tfoot>
</table>
</div>
</div>
</div>
<div class="mt-2">
<label class="form-label small fw-bold">CATATAN UMUM:</label>
<textarea class="form-control form-control-sm" rows="2" placeholder="Catatan umum penilaian..."></textarea>
</div>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

<!-- MODAL IDENTITAS -->
<div class="modal fade" id="identitasModal">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header bg-secondary text-white">
<h6 class="modal-title"><i class="bi bi-person-vcard"></i> Pilih Pejabat & Periode Penilaian</h6>
<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<form method="POST">
<div class="modal-body p-3">
<div class="mb-3">
<label class="form-label small fw-bold">Pejabat Penilai <span class="text-danger">*</span></label>
<select name="pejabat_id" class="form-select" required>
<option value="">-- Pilih Pejabat --</option>
<?php foreach($list_pejabat as $pj): ?>
<option value="<?= $pj['id'] ?>" <?= $pj['id']==$selected_pejabat_id?'selected':'' ?>>
<?= htmlspecialchars($pj['nama']) ?> - <?= htmlspecialchars($pj['jabatan']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label class="form-label small fw-bold">Periode Penilaian <span class="text-danger">*</span></label>
<select name="periode_id" class="form-select" required>
<option value="">-- Pilih Periode --</option>
<?php foreach($list_periode as $pr): ?>
<option value="<?= $pr['id'] ?>" <?= $pr['id']==$selected_periode_id?'selected':'' ?>>
<?= htmlspecialchars($pr['nama_kuartal'] ?? '') ?> - <?= htmlspecialchars($pr['periode_bulan'] ?? '') ?> <?= $pr['tahun'] ?? '' ?>
</option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" name="set_identity" class="btn btn-primary">Simpan</button>
</div>
</form>
</div>
</div>
</div>
<script
</html>
