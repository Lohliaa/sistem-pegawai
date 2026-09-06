<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location:login.php');
    exit();
}
$is_admin = ($_SESSION['role'] == 'admin');
$uid = $_SESSION['user_id'];
$rp = $conn->query("SELECT*FROM pegawai WHERE user_id=$uid LIMIT 1");
$pg = $rp && $rp->num_rows > 0 ? $rp->fetch_assoc() : null;
$uraian = [
    'A' => ['judul' => 'ASPEK PROFESIONAL DAN PEDAGOGIK', 'items' => [1 => 'persiapan_tugas|Mempersiapkan dan mengerjakan dokumen perangkat pembelajaran', 2 => 'pelaksanaan_tugas|Melaksanakan pembelajaran', 3 => 'pelaksanaan_tugas_2|Pencapaian ketuntasan pembelajaran', 4 => 'kompetensi|Memiliki kompetensi yang sesuai', 5 => 'penilaian_hasil|Melakukan penilaian hasil pembelajaran', 6 => 'supervisor_evelator|Mendukung pelaksanaan OKR yang melekat pada tupoksi', 7 => 'layanan_peserta_didik|Memberikan layanan terhadap peserta didik', 8 => 'layanan_orangtua_rekan|Memberikan layanan terhadap wali murid', 9 => 'tugas_tambahan|Melaksanakan tugas tambahan', 10 => 'uraian_tugas|Mempersiapkan kegiatan sesuai dengan uraian tugas']],
    'B' => ['judul' => 'ASPEK KOMITMEN KEISLAMAN', 'items' => [11 => 'sholat_berjamaah|Sholat wajib berjamah', 12 => 'baca_quran_harian|Membaca Al Quran 1 juz/hari', 13 => 'hafalan_quran|Hafalan Al Quran min 5 juz', 14 => 'kehadiran_bpi|Hadir BPI pekanan']],
    'C' => ['judul' => 'ASPEK KEPRIBADIAN DAN SOSIAL', 'items' => [15 => 'kejujuran|Kejujuran', 16 => 'tanggung_jawab|Tanggungjawab', 17 => 'interaksi_sosial|Interaksi Sosial']],
    'D' => ['judul' => 'ASPEK KEDISIPLINAN', 'items' => [18 => 'selalu_hadir|Selalu hadir', 19 => 'datang_tepat_waktu|Datang tepat waktu', 20 => 'tertib_berseragam|Berseragam']],
    'E' => ['judul' => 'ASPEK KELEMBAGAAN', 'items' => [21 => 'koordinasi_kelembagaan|Koordinasi kelembagaan', 22 => 'komitmen_kelembagaan|Komitmen kelembagaan']]
];
$f_pid = (int)($_GET['periode_id'] ?? 0);
$f_jid = (int)($_GET['pejabat_id'] ?? 0);
$f_eid = (int)($_GET['pegawai_id'] ?? 0);
$sng = $_GET['single'] ?? '';
if (!$is_admin) {
    $f_eid = $pg['id'] ?? 0;
}
$wh = [];
if ($f_eid > 0) $wh[] = "fp.pegawai_id=$f_eid";
if ($f_pid > 0) $wh[] = "fp.periode_id=$f_pid";
if ($f_jid > 0) $wh[] = "fp.pejabat_id=$f_jid";
if ($sng) {
    $p = explode('_', $sng);
    if (count($p) >= 2) {
        $wh[] = "fp.pegawai_id=" . (int)$p[0];
        $wh[] = "fp.periode_id=" . (int)$p[1];
    }
}
$ws = count($wh) > 0 ? "WHERE " . implode(" AND ", $wh) : "";
$lpr = [];
$sq = "SELECT fp.*,p.nama np,p.jabatan jp,p.status_kepegawaian sk,pj.nama pn,pj.jabatan pj,p.unit unit,pr.nama_kuartal nk,pr.periode_bulan pb,pr.tahun th FROM form_penilaian fp LEFT JOIN pegawai p ON fp.pegawai_id=p.id LEFT JOIN pejabat_penilai pj ON fp.pejabat_id=pj.id LEFT JOIN periode_penilaian pr ON fp.periode_id=pr.id $ws ORDER BY fp.pegawai_id,pr.tahun DESC";
$rs = $conn->query($sq);
if ($rs) {
    while ($r = $rs->fetch_assoc()) {
        $k = $r['pegawai_id'] . '_' . $r['periode_id'];
        $lpr[$k]['i'] = ['eid' => $r['pegawai_id'], 'np' => $r['np'], 'jp' => $r['jp'], 'sk' => $r['sk'], 'pn' => $r['pn'], 'pj' => $r['pj'], 'nk' => $r['nk'], 'pb' => $r['pb'], 'th' => $r['th'], 'unit' => $r['unit']];
        $lpr[$k]['n'][$r['nama_kolom']] = $r;
        if (!isset($lpr[$k]['_tgl'])) $lpr[$k]['_tgl'] = [];
        if (!empty($r['tanggal_penilaian'])) $lpr[$k]['_tgl'][] = $r['tanggal_penilaian'];
    }
}

    // Sync tanggal_penilaian per group ke MAX
    foreach ($lpr as $_k => $_lp) { if (!empty($_lp['_tgl'])) { sort($_lp['_tgl']); $lpr[$_k]['i']['tp'] = end($_lp['_tgl']); } unset($lpr[$_k]['_tgl']); }
$act = $_GET['action'] ?? 'excel';
// Ambil data Kepala Bidang SDM dan Ketua Yayasan dari tabel pegawai (untuk tanda tangan)
$ttd_kabid = ['nama' => null, 'jabatan' => null];
$ttd_ketum = ['nama' => null, 'jabatan' => null];
$res_kabid = $conn->query("SELECT nama, jabatan FROM pegawai WHERE jabatan LIKE '%Kepala Bidang SDM%' ORDER BY id LIMIT 1");
if ($res_kabid && $r = $res_kabid->fetch_assoc()) { $ttd_kabid = $r; }
$res_ketum = $conn->query("SELECT nama, jabatan FROM pegawai WHERE jabatan LIKE '%Ketua Yayasan%' ORDER BY id LIMIT 1");
if ($res_ketum && $r = $res_ketum->fetch_assoc()) { $ttd_ketum = $r; }
$tanggal_cetak = date('d F Y');

if ($act == 'excel') {
    header('Content-Type:application/vnd.ms-excel');
    header('Content-Disposition:attachment;filename="laporan_penilaian_' . date('Ymd') . '.xls"');
    echo '<?xml version="1.0"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Laporan"><Table>';
    foreach ($lpr as $lp) {
        $i = $lp['i'];
        $tot = 0;
        $jml = 0;
        foreach ($lp['n'] as $n) {
            if ($n['nilai'] > 0) {
                $tot += $n['nilai'];
                $jml++;
            }
        }
        $rt = $jml > 0 ? round($tot / $jml, 2) : 0;
        echo '<Row><Cell><Data ss:Type="String">LAPORAN PENILAIAN KINERJA PEGAWAI</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">Nama: ' . htmlspecialchars($i['np'] ?? '') . '</Data></Cell><Cell><Data ss:Type="String">Status: ' . htmlspecialchars($i['sk'] ?? '') . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">Jabatan: ' . htmlspecialchars($i['jp'] ?? '') . '</Data></Cell><Cell><Data ss:Type="String">Pejabat: ' . htmlspecialchars($i['pn'] ?? '') . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">Periode: ' . htmlspecialchars($i['nk'] ?? '') . ' ' . htmlspecialchars($i['pb'] ?? '') . ' ' . $i['th'] . '</Data></Cell><Cell><Data ss:Type="String">Tanggal: ' . (!empty($i['tp']) ? date('d-m-Y', strtotime($i['tp'])) : (!empty($i['ua']) ? date('d-m-Y', strtotime($i['ua'])) : date('d-m-Y'))) . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">No</Data></Cell><Cell><Data ss:Type="String">Uraian</Data></Cell><Cell><Data ss:Type="String">Nilai</Data></Cell><Cell><Data ss:Type="String">Catatan</Data></Cell></Row>';
        foreach ($uraian as $h => $a) {
            echo '<Row><Cell><Data ss:Type="String">' . $h . '. ' . $a[0] . '</Data></Cell></Row>';
            foreach ($a[1] as $n => $it) {
                $p = explode('|', $it);
                $k = $p[0];
                $l = $p[1];
                $v = $lp['n'][$k]['nilai'] ?? '';
                $c = $lp['n'][$k]['catatan'] ?? '';
                echo '<Row><Cell><Data ss:Type="Number">' . $n . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($l ?? '') . '</Data></Cell><Cell><Data ss:Type="Number">' . ($v ?: 0) . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($c ?? '') . '</Data></Cell></Row>';
            }
        }
        echo '<Row><Cell><Data ss:Type="String">TOTAL & RATA-RATA</Data></Cell><Cell><Data ss:Type="String">' . $tot . ' / ' . $rt . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">Catatan Umum:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars(trim($lp['n']['catatan_umum']['catatan'] ?? '')) . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell></Row>';
        // Tanggal cetak
        echo '<Row><Cell><Data ss:Type="String">Tanggal cetak:</Data></Cell><Cell><Data ss:Type="String">' . $tanggal_cetak . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell></Row>';
        // Baris tanda tangan: Pendidik/Tenaga Kependidikan | Pejabat Penilai
        echo '<Row><Cell><Data ss:Type="String">Pejabat Penilai</Data></Cell><Cell><Data ss:Type="String">Pendidik/Tenaga Kependidikan</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">' . htmlspecialchars($i['pn'] ?? '') . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($i['np'] ?? '-') . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell></Row>';
        // Baris tanda tangan: Kepala Bidang SDM | Ketua Yayasan
        echo '<Row><Cell><Data ss:Type="String">Ketua Yayasan Permata Mojokerto</Data></Cell><Cell><Data ss:Type="String">Kepala Bidang SDM</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String">' . htmlspecialchars($ttd_ketum['nama'] ?? '....................') . '</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($ttd_kabid['nama'] ?? '....................') . '</Data></Cell></Row>';
        echo '<Row><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
    }
    echo '</Table></Worksheet></Workbook>';
    exit();
}

if ($act == 'pdf') {
    header('Content-Type:text/html;charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Laporan PDF</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>
        body { font-size: 11px; margin: 10px; }
        .lp { font-size: 10px; }
        .lp th, .lp td { padding: 3px 6px; }
        .aspek { background: #2c3e50 !important; color: #fff !important; }
        .aspek td { color: #fff !important; }
        .no-col { text-align: center; width: 30px; }
        .nilai-col { text-align: center; width: 50px; }
        .small { font-size: 10px; text-transform: uppercase; }
        @media print { .no-print { display: none !important; } }
        .table { border-collapse: collapse; width: 100%; }
        .table-bordered th, .table-bordered td { border: 1px solid #333; padding: 4px 6px; vertical-align: middle; }
        .table-sm td, .table-sm th { padding: 2px 6px; }
        .table-dark th { background: #212529 !important; color: #fff !important; }
        .table-secondary { background: #e9ecef !important; }
        .bg-success { background: #28a745; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 10px; }
        .bg-primary { background: #007bff; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 10px; }
        .bg-warning { background: #ffc107; color: #000; padding: 2px 8px; border-radius: 3px; font-size: 10px; }
        .text-muted { color: #6c757d !important; }
        .p-3 { padding: 12px; }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 4px; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 16px; }
        .mt-4 { margin-top: 32px; }
        .border-bottom { border-bottom: 2px solid #333; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .row { display: flex; flex-wrap: wrap; }
        .col-6 { flex: 0 0 50%; max-width: 50%; box-sizing: border-box; }
    </style>';
    echo '</head><body>';
    $auto_print = isset($_GET['print']) && $_GET['print'] == '1';
    if ($auto_print) { echo '<script>window.onload = function() { setTimeout(function() { window.print(); }, 400); };</script>'; }
    echo '<div class="text-end mb-3 no-print"><button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print / Save as PDF</button></div>';
    foreach ($lpr as $lp) {
        $i = $lp['i'];
        $tot = 0;
        $jml = 0;
        foreach ($lp['n'] as $n) {
            if ($n['nilai'] > 0) {
                $tot += $n['nilai'];
                $jml++;
            }
        }
        $rt = $jml > 0 ? round($tot / $jml, 2) : 0;
        echo '<div class="text-center mb-2 p-3" style="border-bottom:2px solid #333;">';
        echo '<div style="position:relative;display:flex;align-items:center;justify-content:center;">';
        echo '<img src="logo.png" style="height:60px;width:auto;position:absolute;left:0;top:0;">';
        echo '<div style="text-align:center;">';
        echo '<div style="font-size:18px;font-weight:bold;">PENILAIAN PENDIDIK DAN TENAGA KEPENDIDIKAN</div>';
        echo '<div style="font-size:16px;font-weight:bold;">YAYASAN PERMATA MOJOKERTO</div>';
        echo '</div></div>';
        echo '</div>';
        echo '<div class="p-3 mb-0 lp" style="background:#f8f9fa;">';
        echo '<table class="table table-sm table-borderless mb-0 lp">';
        echo '<tr><td width="120"><strong>Nama</strong></td><td>: ' . htmlspecialchars($i['np'] ?? '-') . '</td><td width="120"><strong>Unit</strong></td><td>: ' . htmlspecialchars($i['unit'] ?? '-') . '</td></tr>';
        echo '<tr><td><strong>Jabatan</strong></td><td>: ' . htmlspecialchars($i['jp'] ?? '-') . '</td><td><strong>Status</strong></td><td>: ' . htmlspecialchars($i['sk'] ?? '-') . '</td></tr>';
        echo '<tr><td><strong>Periode</strong></td><td>: ' . htmlspecialchars($i['nk'] ?? '') . ' ' . htmlspecialchars($i['pb'] ?? '') . ' ' . $i['th'] . '</td><td><strong>Tanggal Penilaian</strong></td><td>: ' . (!empty($i['tp']) ? date('d-m-Y', strtotime($i['tp'])) : (!empty($i['ua']) ? date('d-m-Y', strtotime($i['ua'])) : date('d-m-Y'))) . '</td></tr>';
        echo '</table>';
        echo '</div>';
        echo '<table class="table table-bordered table-sm table-hover mb-0 lp">';
        echo '<thead class="table-dark"><tr><th class="no-col">No</th><th>URAIAN</th><th class="nilai-col">Nilai</th><th>Catatan</th></tr></thead>';
        echo '<tbody>';
        foreach ($uraian as $huruf => $aspek) {
            echo '<tr><td colspan="4" class="aspek">' . $huruf . '. ' . htmlspecialchars($aspek['judul']) . '</td></tr>';
            foreach ($aspek['items'] as $no => $item) {
                $parts = explode('|', $item);
                $k = $parts[0];
                $lbl = $parts[1];
                $val = $lp['n'][$k]['nilai'] ?? '';
                $cat = $lp['n'][$k]['catatan'] ?? '';
                $valDisplay = $val !== '' && $val > 0 ? '<span class="badge bg-success">' . $val . '</span>' : '<span class="text-muted">-</span>';
                echo '<tr><td class="no-col">' . $no . '</td><td>' . htmlspecialchars($lbl) . '</td><td class="nilai-col">' . $valDisplay . '</td><td class="small">' . htmlspecialchars($cat) . '</td></tr>';
            }
        }
        echo '</tbody>';
        echo '</tbody><tfoot class="table-secondary"><tr><td colspan="2"><strong>TOTAL & RATA-RATA</strong></td><td style="text-align:center;"><strong>' . $tot . ' / ' . $rt . '</strong></td><td></td></tr></tfoot></table>';
        $cu = trim($lp['n']['catatan_umum']['catatan'] ?? '');
        if (!empty($cu)) {
            echo '<div class="px-3 pb-3"><div class="alert alert-info mb-0"><strong><i class="bi bi-info-circle"></i> Catatan Umum:</strong><br>' . nl2br(htmlspecialchars($cu)) . '</div></div>';
        }
        echo '<div class="row p-3 mt-1"><div class="col-6 text-center"></div><div class="col-6 text-center small text-muted mb-1" style="padding-right:8px;">Tanggal cetak: ' . $tanggal_cetak . '</div></div>';
        echo '<div class="row p-3 mt-1"><div class="col-6 text-center"><div class="small text-muted mb-3">Pejabat Penilai</div><div style="height:50px;"></div>' . htmlspecialchars($i['pn'] ?? '....................') . '</div><div class="col-6 text-center"><div class="small text-muted mb-3">Pendidik/Tenaga Kependidikan</div><div style="height:50px;"></div>' . htmlspecialchars($i['np'] ?? '-') . '</div></div>';
        echo '<div class="row p-3 mt-4"><div class="col-6 text-center"><div class="small text-muted mb-3">Ketua Yayasan Permata Mojokerto</div><div style="height:50px;"></div>' . htmlspecialchars($ttd_ketum['nama'] ?? '....................') . '</div><div class="col-6 text-center"><div class="small text-muted mb-3">Kepala Bidang SDM</div><div style="height:50px;"></div>' . htmlspecialchars($ttd_kabid['nama'] ?? '....................') . '</div></div>';
        echo '<div style="page-break-after:always;"></div>';
    }
    echo '</body></html>';
    exit();
}
header('Location:laporan_penilaian.php');
exit();
