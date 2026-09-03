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
$uraian = ['A' => ['ASPEK PROFESIONAL DAN PEDAGOGIK', [1 => 'persiapan_tugas|Mengerjakan dokumen perangkat', 2 => 'pelaksanaan_tugas|Melaksanakan pembelajaran', 3 => 'pelaksanaan_tugas_2|Pencapaian ketuntasan', 4 => 'kompetensi|Memiliki kompetensi yang sesuai', 5 => 'penilaian_hasil|Melakukan penilaian hasil', 6 => 'supervisor_evelator|Mendukung OKR', 7 => 'layanan_peserta_didik|Layanan terhadap peserta didik', 8 => 'layanan_orangtua_rekan|Layanan terhadap wali murid', 9 => 'tugas_tambahan|Melaksanakan tugas tambahan', 10 => 'uraian_tugas|Mempersiapkan kegiatan sesuai uraian']], 'B' => ['ASPEK KOMITMEN KEISLAMAN', [11 => 'sholat_berjamaah|Sholat wajib berjamah', 12 => 'baca_quran_harian|Membaca Al Quran 1 juz', 13 => 'hafalan_quran|Hafalan Al Quran 5 juz', 14 => 'kehadiran_bpi|Hadir BPI pekanan']], 'C' => ['ASPEK KEPRIBADIAN DAN SOSIAL', [15 => 'kejujuran|Kejujuran', 16 => 'tanggung_jawab|Tanggungjawab', 17 => 'interaksi_sosial|Interaksi Sosial']], 'D' => ['ASPEK KEDISIPLINAN', [18 => 'selalu_hadir|Selalu hadir', 19 => 'datang_tepat_waktu|Datang tepat waktu', 20 => 'tertib_berseragam|Berseragam']], 'E' => ['ASPEK KELEMBAGAAN', [21 => 'koordinasi_kelembagaan|Koordinasi kelembagaan', 22 => 'komitmen_kelembagaan|Komitmen kelembagaan']]];
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
$sq = "SELECT fp.*,p.nama np,p.jabatan jp,p.status_kepegawaian sk,pj.nama pn,pj.jabatan pj,pr.nama_kuartal nk,pr.periode_bulan pb,pr.tahun th FROM form_penilaian fp LEFT JOIN pegawai p ON fp.pegawai_id=p.id LEFT JOIN pejabat_penilai pj ON fp.pejabat_id=pj.id LEFT JOIN periode_penilaian pr ON fp.periode_id=pr.id $ws ORDER BY fp.pegawai_id,pr.tahun DESC";
$rs = $conn->query($sq);
if ($rs) {
    while ($r = $rs->fetch_assoc()) {
        $k = $r['pegawai_id'] . '_' . $r['periode_id'];
        $lpr[$k]['i'] = ['eid' => $r['pegawai_id'], 'np' => $r['np'], 'jp' => $r['jp'], 'sk' => $r['sk'], 'pn' => $r['pn'], 'pj' => $r['pj'], 'nk' => $r['nk'], 'pb' => $r['pb'], 'th' => $r['th']];
        $lpr[$k]['n'][$r['nama_kolom']] = $r;
    }
}
$act = $_GET['action'] ?? 'excel';
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
        echo '<Row><Cell><Data ss:Type="String">Periode: ' . htmlspecialchars($i['nk'] ?? '') . ' ' . htmlspecialchars($i['pb'] ?? '') . ' ' . $i['th'] . '</Data></Cell><Cell><Data ss:Type="String">Tanggal: ' . date('d-m-Y') . '</Data></Cell></Row>';
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
    }
    echo '</Table></Worksheet></Workbook>';
    exit();
}

if ($act == 'pdf') {
    header('Content-Type:text/html;charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Laporan PDF</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>body{font-size:12px;}.aspek{background:#2c3e50;color:#fff;padding:4px 8px;font-weight:bold;}td,th{padding:3px 6px;vertical-align:middle;}</style>';
    echo '</head><body class="p-4">';
    echo '<div class="text-end mb-3"><button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print / Save as PDF</button></div>';
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
        echo '<div class="text-center mb-3" style="border-bottom:2px solid #333;padding-bottom:8px;margin-bottom:12px;">';
        echo '<h5 class="mb-0">LAPORAN PENILAIAN KINERJA PEGAWAI</h5><small>Lembaga Penjaminan Mutu</small>';
        echo '</div>';
        echo '<table class="table table-sm table-borderless mb-2"><tr><td width="80"><strong>Nama</strong></td><td>: ' . htmlspecialchars($i['np'] ?? '') . '</td><td width="80"><strong>Status</strong></td><td>: ' . htmlspecialchars($i['sk'] ?? '') . '</td></tr><tr><td><strong>Jabatan</strong></td><td>: ' . htmlspecialchars($i['jp'] ?? '') . '</td><td><strong>Pejabat</strong></td><td>: ' . htmlspecialchars($i['pn'] ?? '') . '</td></tr><tr><td><strong>Periode</strong></td><td>: ' . htmlspecialchars($i['nk'] ?? '') . ' ' . htmlspecialchars($i['pb'] ?? '') . ' ' . $i['th'] . '</td><td><strong>Tanggal</strong></td><td>: ' . date('d-m-Y') . '</td></tr></table>';
        echo '<table class="table table-bordered"><thead class="table-dark"><tr><th style="width:30px;">No</th><th>URAIAN</th><th style="width:50px;">Nilai</th><th>Catatan</th></tr></thead><tbody>';
        foreach ($uraian as $h => $a) {
            echo '<tr><td colspan="4" class="aspek">' . $h . '. ' . $a[0] . '</td></tr>';
            foreach ($a[1] as $n => $it) {
                $p = explode('|', $it);
                $k = $p[0];
                $l = $p[1];
                $v = $lp['n'][$k]['nilai'] ?? '';
                $c = $lp['n'][$k]['catatan'] ?? '';
                echo '<tr><td style="text-align:center;">' . $n . '</td><td>' . htmlspecialchars($l ?? '') . '</td><td style="text-align:center;">' . ($v ?: '-') . '</td><td>' . htmlspecialchars($c ?? '') . '</td></tr>';
            }
        }
        echo '</tbody><tfoot class="table-secondary"><tr><td colspan="2"><strong>TOTAL & RATA-RATA</strong></td><td style="text-align:center;"><strong>' . $tot . ' / ' . $rt . '</strong></td><td></td></tr></tfoot></table>';
        $cu = trim($lp['n']['catatan_umum']['catatan'] ?? '');
        echo '<table class="table table-bordered mt-2"><tr style="background:#e7f3ff;"><td colspan="2"><strong>Catatan Umum:</strong></td></tr><tr><td width="100">Catatan</td><td>' . (htmlspecialchars($cu) ?: '<em class="text-muted">-</em>') . '</td></tr></table>';
        echo '<div class="row mt-4"><div class="col-6 text-center"><div style="height:50px;"></div>Pejabat Penilai,<br><strong>' . htmlspecialchars($i['pn'] ?? '') . '</strong><br><small>' . htmlspecialchars($i['pj'] ?? '') . '</small></div><div class="col-6 text-center"><div style="height:50px;"></div>Pegawai,<br><strong>' . htmlspecialchars($i['np'] ?? '') . '</strong><br><small>' . htmlspecialchars($i['jp'] ?? '') . '</small></div></div>';
        echo '<div style="page-break-after:always;"></div>';
    }
    echo '</body></html>';
    exit();
}
header('Location:laporan_penilaian.php');
exit();
