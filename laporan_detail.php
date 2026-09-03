<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$is_admin = ($_SESSION['role'] == 'admin');
$user_id = $_SESSION['user_id'];
$res_pegawai = $conn->query("SELECT * FROM pegawai WHERE user_id = $user_id LIMIT 1");
$pegawai = $res_pegawai && $res_pegawai->num_rows > 0 ? $res_pegawai->fetch_assoc() : null;
$uraian = ['A' => ['judul' => 'ASPEK PROFESIONAL DAN PEDAGOGIK', 'items' => [1 => 'persiapan_tugas|Mempersiapkan dan mengerjakan dokumen perangkat pembelajaran', 2 => 'pelaksanaan_tugas|Melaksanakan pembelajaran', 3 => 'pelaksanaan_tugas_2|Pencapaian ketuntasan pembelajaran', 4 => 'kompetensi|Memiliki kompetensi yang sesuai', 5 => 'penilaian_hasil|Melakukan penilaian hasil pembelajaran', 6 => 'supervisor_evelator|Mendukung pelaksanaan OKR yang melekat pada tupoksi', 7 => 'layanan_peserta_didik|Memberikan layanan terhadap peserta didik', 8 => 'layanan_orangtua_rekan|Memberikan layanan terhadap wali murid', 9 => 'tugas_tambahan|Melaksanakan tugas tambahan', 10 => 'uraian_tugas|Mempersiapkan kegiatan sesuai dengan uraian tugas']], 'B' => ['judul' => 'ASPEK KOMITMEN KEISLAMAN', 'items' => [11 => 'sholat_berjamaah|Sholat wajib berjamah', 12 => 'baca_quran_harian|Membaca Al Quran 1 juz/hari', 13 => 'hafalan_quran|Hafalan Al Quran min 5 juz', 14 => 'kehadiran_bpi|Hadir BPI pekanan']], 'C' => ['judul' => 'ASPEK KEPRIBADIAN DAN SOSIAL', 'items' => [15 => 'kejujuran|Kejujuran', 16 => 'tanggung_jawab|Tanggungjawab', 17 => 'interaksi_sosial|Interaksi Sosial']], 'D' => ['judul' => 'ASPEK KEDISIPLINAN', 'items' => [18 => 'selalu_hadir|Selalu hadir', 19 => 'datang_tepat_waktu|Datang tepat waktu', 20 => 'tertib_berseragam|Berseragam']], 'E' => ['judul' => 'ASPEK KELEMBAGAAN', 'items' => [21 => 'koordinasi_kelembagaan|Koordinasi kelembagaan', 22 => 'komitmen_kelembagaan|Komitmen kelembagaan']]];
$key = $_GET['key'] ?? '';
$laporan = [];
if (!empty($key)) {
    $extra_where = '';
    if (!$is_admin && $pegawai) {
        // Non-admin hanya boleh akses laporan miliknya sendiri
        $extra_where = " AND fp.pegawai_id = " . (int)$pegawai['id'];
    }
    $sql = "SELECT fp.*, p.nama as nama_pegawai, p.jabatan as jabatan_pegawai, p.status_kepegawaian, p.unit, pj.nama as nama_pejabat, pj.jabatan as jabatan_pejabat, pr.nama_kuartal, pr.periode_bulan, pr.tahun FROM form_penilaian fp LEFT JOIN pegawai p ON fp.pegawai_id = p.id LEFT JOIN pejabat_penilai pj ON fp.pejabat_id = pj.id LEFT JOIN periode_penilaian pr ON fp.periode_id = pr.id WHERE CONCAT(fp.pegawai_id,'_',fp.periode_id) = '" . mysqli_real_escape_string($conn, $key) . "'" . $extra_where . " ORDER BY fp.pegawai_id, pr.tahun DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $laporan['info'] = ['pegawai_id' => $r['pegawai_id'], 'nama_pegawai' => $r['nama_pegawai'], 'jabatan_pegawai' => $r['jabatan_pegawai'], 'status_kepegawaian' => $r['status_kepegawaian'], 'unit' => $r['unit'], 'nama_pejabat' => $r['nama_pejabat'], 'jabatan_pejabat' => $r['jabatan_pejabat'], 'nama_kuartal' => $r['nama_kuartal'], 'periode_bulan' => $r['periode_bulan'], 'tahun' => $r['tahun']];
            $laporan['nilai'][$r['nama_kolom']] = $r;
        }
    }
}
if (empty($laporan)) {
    header('Location: laporan_penilaian.php');
    exit();
}
$info = $laporan['info'];
$total = 0;
$jml = 0;
foreach ($laporan['nilai'] as $n) {
    if ($n['nilai'] > 0) {
        $total += $n['nilai'];
        $jml++;
    }
}
$rata = $jml > 0 ? round($total / $jml, 2) : 0;
$current_page = 'laporan_penilaian.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Laporan Penilaian - <?= htmlspecialchars($info['nama_pegawai'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }
        }

        .aspek {
            background: #2c3e50 !important;
            color: #fff !important;
            font-weight: bold;
        }

        .lp th,
        .lp td {
            padding: 6px 8px;
            font-size: 11px;
        }

        .no-col {
            width: 40px;
            text-align: center;
        }

        .nilai-col {
            width: 70px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0"><?php $current_page = 'laporan_detail.php';
                                        include 'includes/sidebar.php'; ?></div>
            <div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">
                <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                    <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> Detail Laporan Penilaian Kinerja</h2>
                    <a href="laporan_penilaian.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
                </div>

                <div class="card shadow-sm lp mb-3">
                    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center no-print">
                        <h6 class="mb-0"><i class="bi bi-person-vcard"></i> <?= htmlspecialchars($info['nama_pegawai'] ?? '?') ?> - <?= htmlspecialchars($info['nama_kuerto'] ?? '') ?> <?= htmlspecialchars($info['periode_bulan'] ?? '') ?> <?= $info['tahun'] ?? '' ?></h6>
                        <div>
                            <a href="laporan_penilaian_export.php?action=excel&single=<?= urlencode($key) ?>" class="btn btn-sm btn-success"><i class="bi bi-file-excel"></i> Excel</a>
                            <a href="laporan_penilaian_export.php?action=pdf&single=<?= urlencode($key) ?>" class="btn btn-sm btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
                            <button onclick="printLaporan()" class="btn btn-sm btn-light"><i class="bi bi-printer"></i> Print</button>
                        </div>
                    </div>
                    <div id="printArea">
                        <div class="text-center mb-2 p-3" style="border-bottom:2px solid #333;">
                            <h6 class="mb-0 fw-bold">LAPORAN PENILAIAN KINERJA PEGAWAI</h6><small>Lembaga Penjaminan Mutu</small>
                        </div>
                        <div class="p-3" style="background:#f8f9fa;">
                            <table class="table table-sm table-borderless mb-0 lp">
                                <tr>
                                    <td width="120"><strong>Nama</strong></td>
                                    <td>: <?= htmlspecialchars($info['nama_pegawai'] ?? '-') ?></td>
                                    <td width="120"><strong>Unit</strong></td>
                                    <td>: <?= htmlspecialchars($info['unit'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Jabatan</strong></td>
                                    <td>: <?= htmlspecialchars($info['jabatan_pegawai'] ?? '-') ?></td>
                                    <td><strong>Status</strong></td>
                                    <td>: <?= htmlspecialchars($info['status_kepegawaian'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Periode</strong></td>
                                    <td>: <?= htmlspecialchars($info['nama_kuerto'] ?? '') ?> <?= htmlspecialchars($info['periode_bulan'] ?? '') ?> <?= $info['tahun'] ?? '' ?></td>
                                    <td><strong>Tanggal Cetak</strong></td>
                                    <td>: <?= date('d-m-Y') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Pejabat Penilai</strong></td>
                                    <td>: <?= htmlspecialchars($info['nama_pejabat'] ?? '-') ?></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </table>
                        </div>

                        <table class="table table-bordered table-sm table-hover mb-0 lp">
                            <thead class="table-dark">
                                <tr>
                                    <th class="no-col">No</th>
                                    <th>URAIAN</th>
                                    <th class="nilai-col">Nilai</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uraian as $huruf => $aspek): ?>
                                    <tr>
                                        <td colspan="4" class="aspek"><?= $huruf ?>. <?= htmlspecialchars($aspek['judul']) ?></td>
                                    </tr>
                                    <?php foreach ($aspek['items'] as $no => $item):
                                        $parts = explode('|', $item);
                                        $k = $parts[0];
                                        $lbl = $parts[1];
                                        $val = $laporan['nilai'][$k]['nilai'] ?? '';
                                        $cat = $laporan['nilai'][$k]['catatan'] ?? '';
                                    ?>
                                        <tr>
                                            <td class="no-col"><?= $no ?></td>
                                            <td><?= htmlspecialchars($lbl) ?></td>
                                            <td class="nilai-col"><?= $val !== '' && $val > 0 ? '<span class="badge bg-success">' . $val . '</span>' : '<span class="text-muted">-</span>' ?></td>
                                            <td class="small"><?= htmlspecialchars($cat) ?></td>
                                        </tr>
                                <?php endforeach;
                                endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="2" class="text-end"><strong>TOTAL & RATA-RATA</strong></td>
                                    <td class="nilai-col"><span class="badge bg-primary"><?= $total ?></span> / <span class="badge bg-warning text-dark"><?= $rata ?></span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <?php if (!empty(trim($laporan['nilai']['catatan_umum']['catatan'] ?? ''))): ?>
                            <div class="px-3 pb-3">
                                <div class="alert alert-info mb-0">
                                    <strong><i class="bi bi-info-circle"></i> Catatan Umum:</strong><br>
                                    <?= nl2br(htmlspecialchars($laporan['nilai']['catatan_umum']['catatan'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="row p-3 mt-1 no-print">
                            <div class="col-6 text-center">
                                <div class="mb-5">Pejabat Penilai,</div>
                                <div><strong><?= htmlspecialchars($info['nama_pejabat'] ?? '....................') ?></strong></div>
                                <div class="small text-muted"><?= htmlspecialchars($info['jabatan_pejabat'] ?? '') ?></div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="mb-5">Pegawai yang Dinilai,</div>
                                <div><strong><?= htmlspecialchars($info['nama_pegawai'] ?? '-') ?></strong></div>
                                <div class="small text-muted"><?= htmlspecialchars($info['jabatan_pegawai'] ?? '') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function printLaporan() {
                var content = document.getElementById('printArea').innerHTML;
                var win = window.open('', '_blank');
                win.document.write('<html><head><title>Cetak Laporan</title>');
                win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
                win.document.write('<style>body{font-size:11px;} .lp{font-size:10px;} .lp th,.lp td{padding:3px 6px;} .aspek{background:#2c3e50!important;color:#fff!important;}</style>');
                win.document.write('</head><body>');
                win.document.write(content);
                win.document.write('</body></html>');
                win.document.close();
                setTimeout(function() {
                    win.print();
                }, 300);
            }
        </script>
</body>

</html>