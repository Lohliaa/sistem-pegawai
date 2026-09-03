<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$is_admin = ($_SESSION['role'] == 'admin');
$user_id = $_SESSION['user_id'];
$res_pegawai = $conn->query("SELECT * FROM pegawai WHERE user_id = $user_id LIMIT 1");
$pegawai = $res_pegawai && $res_pegawai->num_rows > 0 ? $res_pegawai->fetch_assoc() : null;
$uraian = ['A'=>['judul'=>'ASPEK PROFESIONAL DAN PEDAGOGIK','items'=>[1=>'persiapan_tugas|Mempersiapkan dan mengerjakan dokumen perangkat pembelajaran',2=>'pelaksanaan_tugas|Melaksanakan pembelajaran',3=>'pelaksanaan_tugas_2|Pencapaian ketuntasan pembelajaran',4=>'kompetensi|Memiliki kompetensi yang sesuai',5=>'penilaian_hasil|Melakukan penilaian hasil pembelajaran',6=>'supervisor_evelator|Mendukung pelaksanaan OKR yang melekat pada tupoksi',7=>'layanan_peserta_didik|Memberikan layanan terhadap peserta didik',8=>'layanan_orangtua_rekan|Memberikan layanan terhadap wali murid',9=>'tugas_tambahan|Melaksanakan tugas tambahan',10=>'uraian_tugas|Mempersiapkan kegiatan sesuai dengan uraian tugas']],'B'=>['judul'=>'ASPEK KOMITMEN KEISLAMAN','items'=>[11=>'sholat_berjamaah|Sholat wajib berjamah',12=>'baca_quran_harian|Membaca Al Quran 1 juz/hari',13=>'hafalan_quran|Hafalan Al Quran min 5 juz',14=>'kehadiran_bpi|Hadir BPI pekanan']],'C'=>['judul'=>'ASPEK KEPRIBADIAN DAN SOSIAL','items'=>[15=>'kejujuran|Kejujuran',16=>'tanggung_jawab|Tanggungjawab',17=>'interaksi_sosial|Interaksi Sosial']],'D'=>['judul'=>'ASPEK KEDISIPLINAN','items'=>[18=>'selalu_hadir|Selalu hadir',19=>'datang_tepat_waktu|Datang tepat waktu',20=>'tertib_berseragam|Berseragam']],'E'=>['judul'=>'ASPEK KELEMBAGAAN','items'=>[21=>'koordinasi_kelembagaan|Koordinasi kelembagaan',22=>'komitmen_kelembagaan|Komitmen kelembagaan']]];
$filter_pegawai_id = (int)($_GET['pegawai_id'] ?? 0);
$filter_periode_id = (int)($_GET['periode_id'] ?? 0);
$filter_pejabat_id = (int)($_GET['pejabat_id'] ?? 0);
if (!$is_admin) { $filter_pegawai_id = $pegawai['id'] ?? 0; }
$list_pegawai = [];
if ($is_admin) { $r = $conn->query("SELECT * FROM pegawai ORDER BY nama"); if ($r) while ($row = $r->fetch_assoc()) $list_pegawai[] = $row; }
$list_pejabat = []; $r = $conn->query("SELECT * FROM pejabat_penilai ORDER BY nama"); if ($r) while ($row = $r->fetch_assoc()) $list_pejabat[] = $row;
$list_periode = []; $r = $conn->query("SELECT * FROM periode_penilaian ORDER BY tahun DESC"); if ($r) while ($row = $r->fetch_assoc()) $list_periode[] = $row;
$where = []; if ($filter_pegawai_id > 0) $where[] = "fp.pegawai_id = $filter_pegawai_id"; if ($filter_periode_id > 0) $where[] = "fp.periode_id = $filter_periode_id"; if ($filter_pejabat_id > 0) $where[] = "fp.pejabat_id = $filter_pejabat_id";
$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$laporan = [];
$sql = "SELECT fp.*, p.nama as nama_pegawai, p.jabatan as jabatan_pegawai, p.status_kepegawaian, pj.nama as nama_pejabat, pj.jabatan as jabatan_pejabat, pr.nama_kuartal, pr.periode_bulan, pr.tahun FROM form_penilaian fp LEFT JOIN pegawai p ON fp.pegawai_id = p.id LEFT JOIN pejabat_penilai pj ON fp.pejabat_id = pj.id LEFT JOIN periode_penilaian pr ON fp.periode_id = pr.id $where_sql ORDER BY fp.pegawai_id, pr.tahun DESC";
$res = $conn->query($sql);
if ($res) { while ($r = $res->fetch_assoc()) { $key = $r['pegawai_id'] . '_' . $r['periode_id']; $laporan[$key]['info'] = ['pegawai_id'=>$r['pegawai_id'],'nama_pegawai'=>$r['nama_pegawai'],'jabatan_pegawai'=>$r['jabatan_pegawai'],'status_kepegawaian'=>$r['status_kepegawaian'],'nama_pejabat'=>$r['nama_pejabat'],'jabatan_pejabat'=>$r['jabatan_pejabat'],'nama_kuartal'=>$r['nama_kuartal'],'periode_bulan'=>$r['periode_bulan'],'tahun'=>$r['tahun']]; $laporan[$key]['nilai'][$r['nama_kolom']] = $r; } }
$current_page = 'laporan_penilaian.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Penilaian Kinerja</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
body { font-size: 0.85rem; }
.lp { font-size: 0.82rem; }
.lp th, .lp td { padding: 4px 8px; vertical-align: middle; }
.lp .aspek { background: #2c3e50; color: #fff; font-weight: bold; padding: 6px 10px; }
.lp .no-col { width: 30px; text-align: center; }
.lp .nilai-col { width: 50px; text-align: center; }
@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    body { background: #fff !important; }
    .col-md-10 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
}
</style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-3" style="background:#f4f6f9;min-height:100vh;">
<h5 class="mb-1"><i class="bi bi-file-earmark-bar-graph"></i> Laporan Penilaian Kinerja</h5>
<p class="text-muted small mb-2"><?= $is_admin ? 'Semua laporan penilaian dari seluruh akun.' : 'Laporan penilaian Anda.' ?></p>
<div class="card shadow-sm lp mb-2 no-print">
<div class="card-header bg-info text-white py-2"><h6 class="mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h6></div>
<div class="card-body p-2">
<form method="GET" class="row g-2 align-items-end">
<?php if ($is_admin): ?>
<div class="col-md-4">
<label class="form-label small mb-1">Pegawai</label>
<select name="pegawai_id" class="form-select form-select-sm">
<option value="">-- Semua Pegawai --</option>
<?php foreach($list_pegawai as $p): ?>
<option value="<?= $p['id'] ?>" <?= $filter_pegawai_id==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['jabatan']) ?>)</option>
<?php endforeach; ?>
</select>
</div>
<?php endif; ?>
<div class="col-md-3">
<label class="form-label small mb-1">Periode</label>
<select name="periode_id" class="form-select form-select-sm">
<option value="">-- Semua Periode --</option>
<?php foreach($list_periode as $pr): ?>
<option value="<?= $pr['id'] ?>" <?= $filter_periode_id==$pr['id']?'selected':'' ?>><?= htmlspecialchars($pr['nama_kuartal'] ?? '') ?> - <?= htmlspecialchars($pr['periode_bulan'] ?? '') ?> <?= $pr['tahun'] ?? '' ?></option>
<?php endforeach; ?>
</select>
</div>
<?php if ($is_admin): ?>
<div class="col-md-3">
<label class="form-label small mb-1">Pejabat Penilai</label>
<select name="pejabat_id" class="form-select form-select-sm">
<option value="">-- Semua Pejabat --</option>
<?php foreach($list_pejabat as $pj): ?>
<option value="<?= $pj['id'] ?>" <?= $filter_pejabat_id==$pj['id']?'selected':'' ?>><?= htmlspecialchars($pj['nama']) ?></option>
<?php endforeach; ?>
</select>
</div>
<?php endif; ?>
<div class="col-md-2">
<button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Tampilkan</button>
</div>
</form>
</div>

<?php if (empty($laporan)): ?>
<div class="card shadow-sm lp"><div class="card-body text-center py-5">
<i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
<p class="text-muted mt-2">Tidak ada data laporan<?= $is_admin ? '' : ' Anda' ?>.</p>
</div></div>
<?php else: foreach ($laporan as $key => $lap):
    $info = $lap['info'];
    $total = 0; $jml = 0;
    foreach ($lap['nilai'] as $n) { if ($n['nilai'] > 0) { $total += $n['nilai']; $jml++; } }
    $rata = $jml > 0 ? round($total / $jml, 2) : 0;
    $qs = http_build_query(array_filter(['pegawai_id'=>$filter_pegawai_id,'periode_id'=>$filter_periode_id,'pejabat_id'=>$filter_pejabat_id]));
?>
<div class="card shadow-sm lp mb-3">
<div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center no-print">
<h6 class="mb-0"><i class="bi bi-person-vcard"></i> <?= htmlspecialchars($info['nama_pegawai'] ?? '?') ?> - <?= htmlspecialchars($info['nama_kuartal'] ?? '') ?> <?= htmlspecialchars($info['periode_bulan'] ?? '') ?> <?= $info['tahun'] ?? '' ?></h6>
<div>
<a href="laporan_penilaian_export.php?action=excel&<?= $qs ?>&single=<?= urlencode($key) ?>" class="btn btn-sm btn-success"><i class="bi bi-file-excel"></i> Excel</a>
<a href="laporan_penilaian_export.php?action=pdf&<?= $qs ?>&single=<?= urlencode($key) ?>" class="btn btn-sm btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
<button onclick="printLaporan('<?= $key ?>')" class="btn btn-sm btn-light"><i class="bi bi-printer"></i> Print</button>
</div>
</div>
<div id="printArea-<?= $key ?>">
<div class="text-center mb-2 p-3" style="border-bottom:2px solid #333;">
<h6 class="mb-0 fw-bold">LAPORAN PENILAIAN KINERJA PEGAWAI</h6><small>Lembaga Penjaminan Mutu</small>
</div>
<div class="p-3" style="background:#f8f9fa;">
<table class="table table-sm table-borderless mb-0 lp">
<tr><td width="120"><strong>Nama</strong></td><td>: <?= htmlspecialchars($info['nama_pegawai'] ?? '-') ?></td><td width="120"><strong>Status</strong></td><td>: <?= htmlspecialchars($info['status_kepegawaian'] ?? '-') ?></td></tr>
<tr><td><strong>Jabatan</strong></td><td>: <?= htmlspecialchars($info['jabatan_pegawai'] ?? '-') ?></td><td><strong>Pejabat</strong></td><td>: <?= htmlspecialchars($info['nama_pejabat'] ?? '-') ?></td></tr>
<tr><td><strong>Periode</strong></td><td>: <?= htmlspecialchars($info['nama_kuartal'] ?? '') ?> <?= htmlspecialchars($info['periode_bulan'] ?? '') ?> <?= $info['tahun'] ?? '' ?></td><td><strong>Tanggal Cetak</strong></td><td>: <?= date('d-m-Y') ?></td></tr>
</table>
</div>

<table class="table table-bordered table-sm table-hover mb-0 lp">
<thead class="table-dark">
<tr><th class="no-col">No</th><th>URAIAN</th><th class="nilai-col">Nilai</th><th>Catatan</th></tr>
</thead>
<tbody>
<?php foreach($uraian as $huruf => $aspek): ?>
<tr><td colspan="4" class="aspek"><?= $huruf ?>. <?= htmlspecialchars($aspek['judul']) ?></td></tr>
<?php foreach($aspek['items'] as $no => $item):
    $parts = explode('|', $item); $k = $parts[0]; $lbl = $parts[1];
    $val = $lap['nilai'][$k]['nilai'] ?? '';
    $cat = $lap['nilai'][$k]['catatan'] ?? '';
?>
<tr>
<td class="no-col"><?= $no ?></td>
<td><?= htmlspecialchars($lbl) ?></td>
<td class="nilai-col"><?= $val !== '' && $val > 0 ? '<span class="badge bg-success">'.$val.'</span>' : '<span class="text-muted">-</span>' ?></td>
<td class="small"><?= htmlspecialchars($cat) ?></td>
</tr>
<?php endforeach; endforeach; ?>
</tbody>
<tfoot class="table-secondary">
<tr><td colspan="2" class="text-end"><strong>TOTAL & RATA-RATA</strong></td><td class="nilai-col"><span class="badge bg-primary"><?= $total ?></span> / <span class="badge bg-warning text-dark"><?= $rata ?></span></td><td></td></tr>
</tfoot>
</table>
<div class="row p-3 mt-1 no-print">
<div class="col-6 text-center"><div class="mb-5">Pejabat Penilai,</div><div><strong><?= htmlspecialchars($info['nama_pejabat'] ?? '....................') ?></strong></div><div class="small text-muted"><?= htmlspecialchars($info['jabatan_pejabat'] ?? '') ?></div></div>
<div class="col-6 text-center"><div class="mb-5">Pegawai yang Dinilai,</div><div><strong><?= htmlspecialchars($info['nama_pegawai'] ?? '-') ?></strong></div><div class="small text-muted"><?= htmlspecialchars($info['jabatan_pegawai'] ?? '') ?></div></div>
</div>
</div>
</div>
<?php endforeach; endif; ?>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function printLaporan(key) {
    var content = document.getElementById('printArea-' + key);
    var win = window.open('', '_blank');
    win.document.write('<html><head><title>Cetak Laporan</title>');
    win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
    win.document.write('<style>body{font-size:11px;} .lp{font-size:10px;} .lp th,.lp td{padding:3px 6px;} .aspek{background:#2c3e50!important;color:#fff!important;}</style>');
    win.document.write('</head><body>');
    win.document.write(content.innerHTML);
    win.document.write('</body></html>');
    win.document.close();
    setTimeout(function() { win.print(); }, 300);
}
</script>
</body>
</html>
</div>
