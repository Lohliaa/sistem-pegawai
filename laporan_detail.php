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

// Handle POST: update tanggal_penilaian untuk semua row (pegawai, periode)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tanggal_penilaian'])) {
    $peg_id = (int)($_POST['pegawai_id'] ?? 0);
    $pr_id = (int)($_POST['periode_id'] ?? 0);
    $new_tgl = trim($_POST['new_tanggal'] ?? '');
    if ($peg_id > 0 && $pr_id > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_tgl)) {
        $new_tgl_esc = mysqli_real_escape_string($conn, $new_tgl);
        $conn->query("UPDATE form_penilaian SET tanggal_penilaian = '$new_tgl_esc' WHERE pegawai_id = $peg_id AND periode_id = $pr_id");
        $_SESSION['msg'] = 'Tanggal Penilaian diupdate menjadi ' . date('d-m-Y', strtotime($new_tgl));
    } else {
        $_SESSION['msg'] = 'Format tanggal tidak valid.';
    }
    $key_post = $_POST['key'] ?? '';
    header('Location: laporan_detail.php?key=' . urlencode($key_post));
    exit();
}
$uraian = ['A' => ['judul' => 'ASPEK PROFESIONAL DAN PEDAGOGIK', 'items' => [1 => 'persiapan_tugas|Mempersiapkan dan mengerjakan dokumen perangkat pembelajaran', 2 => 'pelaksanaan_tugas|Melaksanakan pembelajaran', 3 => 'pelaksanaan_tugas_2|Pencapaian ketuntasan pembelajaran', 4 => 'kompetensi|Memiliki kompetensi yang sesuai', 5 => 'penilaian_hasil|Melakukan penilaian hasil pembelajaran', 6 => 'supervisor_evelator|Mendukung pelaksanaan OKR yang melekat pada tupoksi', 7 => 'layanan_peserta_didik|Memberikan layanan terhadap peserta didik', 8 => 'layanan_orangtua_rekan|Memberikan layanan terhadap wali murid', 9 => 'tugas_tambahan|Melaksanakan tugas tambahan', 10 => 'uraian_tugas|Mempersiapkan kegiatan sesuai dengan uraian tugas']], 'B' => ['judul' => 'ASPEK KOMITMEN KEISLAMAN', 'items' => [11 => 'sholat_berjamaah|Sholat wajib berjamah', 12 => 'baca_quran_harian|Membaca Al Quran 1 juz/hari', 13 => 'hafalan_quran|Hafalan Al Quran min 5 juz', 14 => 'kehadiran_bpi|Hadir BPI pekanan']], 'C' => ['judul' => 'ASPEK KEPRIBADIAN DAN SOSIAL', 'items' => [15 => 'kejujuran|Kejujuran', 16 => 'tanggung_jawab|Tanggungjawab', 17 => 'interaksi_sosial|Interaksi Sosial']], 'D' => ['judul' => 'ASPEK KEDISIPLINAN', 'items' => [18 => 'selalu_hadir|Selalu hadir', 19 => 'datang_tepat_waktu|Datang tepat waktu', 20 => 'tertib_berseragam|Berseragam']], 'E' => ['judul' => 'ASPEK KELEMBAGAAN', 'items' => [21 => 'koordinasi_kelembagaan|Koordinasi kelembagaan', 22 => 'komitmen_kelembagaan|Komitmen kelembagaan']]];
$key = $_GET['key'] ?? '';
$laporan = [];
if (!empty($key)) {
    $extra_where = '';
    if (!$is_admin && $pegawai) {
        // Non-admin hanya boleh akses laporan miliknya sendiri
        $extra_where = " AND fp.pegawai_id = " . (int)$pegawai['id'];
    }
    $sql = "SELECT fp.*, p.nama as nama_pegawai, p.jabatan as jabatan_pegawai, p.status_kepegawaian, p.unit, pj.nama as nama_pejabat, pj.jabatan as jabatan_pejabat, pr.nama_kuartal, pr.periode_bulan, pr.tahun FROM form_penilaian fp LEFT JOIN pegawai p ON fp.pegawai_id = p.id LEFT JOIN pejabat_penilai pj ON fp.pejabat_id = pj.id LEFT JOIN periode_penilaian pr ON fp.periode_id = pr.id WHERE CONCAT(fp.pegawai_id,'_',fp.periode_id) = '" . mysqli_real_escape_string($conn, $key) . "'" . $extra_where . " ORDER BY fp.id ASC";
    $res = $conn->query($sql);
    if ($res) {
        $first = true;
        while ($r = $res->fetch_assoc()) {
            if ($first) {
                $laporan['info'] = ['pegawai_id' => $r['pegawai_id'], 'nama_pegawai' => $r['nama_pegawai'], 'jabatan_pegawai' => $r['jabatan_pegawai'], 'status_kepegawaian' => $r['status_kepegawaian'], 'unit' => $r['unit'], 'nama_pejabat' => $r['nama_pejabat'], 'jabatan_pejabat' => $r['jabatan_pejabat'], 'nama_kuartal' => $r['nama_kuartal'], 'periode_bulan' => $r['periode_bulan'], 'tahun' => $r['tahun'], 'tanggal_penilaian' => $r['tanggal_penilaian'] ?? null, 'updated_at' => $r['updated_at'] ?? null];
                $first = false;
            } else {
                if (empty($laporan['info']['tanggal_penilaian']) && !empty($r['tanggal_penilaian'])) {
                    $laporan['info']['tanggal_penilaian'] = $r['tanggal_penilaian'];
                }
                if (empty($laporan['info']['updated_at']) && !empty($r['updated_at'])) {
                    $laporan['info']['updated_at'] = $r['updated_at'];
                }
            }
            $laporan['nilai'][$r['nama_kolom']] = $r;
        if (!isset($laporan['_tgl'])) $laporan['_tgl'] = [];
        if (!empty($r['tanggal_penilaian'])) $laporan['_tgl'][] = $r['tanggal_penilaian'];
        }
    }
}
if (empty($laporan)) {
    header('Location: laporan_penilaian.php');
    exit();
}
// Sync tanggal_penilaian ke MAX
if (!empty($laporan['_tgl'])) { sort($laporan['_tgl']); $laporan['info']['tanggal_penilaian'] = end($laporan['_tgl']); }
unset($laporan['_tgl']);
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
// Ambil data Kepala Bidang SDM dan Ketua Yayasan dari tabel pegawai (untuk tanda tangan)
$ttd_kabid = ['nama' => null, 'jabatan' => null];
$ttd_ketum = ['nama' => null, 'jabatan' => null];
$res_kabid = $conn->query("SELECT nama, jabatan FROM pegawai WHERE jabatan LIKE '%Kepala Bidang SDM%' ORDER BY id LIMIT 1");
if ($res_kabid && $r = $res_kabid->fetch_assoc()) { $ttd_kabid = $r; }
$res_ketum = $conn->query("SELECT nama, jabatan FROM pegawai WHERE jabatan LIKE '%Ketua Yayasan%' ORDER BY id LIMIT 1");
if ($res_ketum && $r = $res_ketum->fetch_assoc()) { $ttd_ketum = $r; }
$tanggal_cetak = date('d F Y');
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
                <?php if (!empty($_SESSION['msg'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['msg']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['msg']); endif; ?>
                <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                    <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> Detail Laporan Penilaian Kinerja</h2>
                    <a href="laporan_penilaian.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
                </div>

                <div class="card shadow-sm lp mb-3">
                    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center no-print">
                        <h6 class="mb-0"><i class="bi bi-person-vcard"></i> <?= htmlspecialchars($info['nama_pegawai'] ?? '?') ?> - <?= htmlspecialchars($info['nama_kuart\u00e1l'] ?? '') ?> <?= htmlspecialchars($info['periode_bulan'] ?? '') ?> <?= $info['tahun'] ?? '' ?></h6>
                        <div>
                            <a href="laporan_penilaian_export.php?action=excel&single=<?= urlencode($key) ?>" class="btn btn-sm btn-success"><i class="bi bi-file-excel"></i> Excel</a>
                            <a href="laporan_penilaian_export.php?action=pdf&single=<?= urlencode($key) ?>" class="btn btn-sm btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
                            <button onclick="printLaporan()" class="btn btn-sm btn-light"><i class="bi bi-printer"></i> Print</button>
                        </div>
                    </div>
                    <div id="printArea">
                        <div class="text-center mb-2 p-3" style="border-bottom:2px solid #333;">
                            <div style="position:relative;display:flex;align-items:center;justify-content:center;"><img src="logo.png" style="height:60px;width:auto;position:absolute;left:0;top:0;"><div style="text-align:center;"><div style="font-size:18px;font-weight:bold;">PENILAIAN PENDIDIK DAN TENAGA KEPENDIDIKAN</div><div style="font-size:16px;font-weight:bold;">YAYASAN PERMATA MOJOKERTO</div></div></div>
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
                                    <td>: <?= htmlspecialchars($info['nama_kuart\u00e1l'] ?? '') ?> <?= htmlspecialchars($info['periode_bulan'] ?? '') ?> <?= $info['tahun'] ?? '' ?></td>
                                    <td><strong>Tanggal Penilaian</strong></td>
                                    <td>: <span id="tg_display"><?= ($info['tanggal_penilaian'] ? htmlspecialchars(date('d-m-Y', strtotime($info['tanggal_penilaian']))) : (!empty($info['updated_at']) ? htmlspecialchars(date('d-m-Y', strtotime($info['updated_at']))) : '<span class="text-muted">' . date('d-m-Y') . '</span>')) ?></span>
                                        <form method="POST" id="frm_tg" style="display:inline;">
                                            <input type="hidden" name="update_tanggal_penilaian" value="1">
                                            <input type="hidden" name="pegawai_id" value="<?= (int)($info['pegawai_id'] ?? 0) ?>">
                                            <input type="hidden" name="periode_id" value="<?= (int)($info['periode_id'] ?? 0) ?>">
                                            <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                                            <input type="date" name="new_tanggal" id="tg_input" value="<?= $info['tanggal_penilaian'] ? htmlspecialchars($info['tanggal_penilaian']) : '' ?>" style="display:none;width:160px;" required>
                                            <button type="submit" id="tg_save" style="display:none;" class="btn btn-sm btn-success"><i class="bi bi-check"></i></button>
                                        </form>
                                        <button onclick="editTg()" id="tg_edit" class="btn btn-sm btn-link text-primary p-0 ms-1" title="Edit Tanggal"><i class="bi bi-pencil-square"></i></button>
                                        <button onclick="batalTg()" id="tg_cancel" style="display:none;" class="btn btn-sm btn-link text-danger p-0 ms-1"><i class="bi bi-x-circle"></i></button>
                                    </td>
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
                        <div class="row p-3 mt-1">
                            <div class="col-6 text-center"></div>
                            <div class="col-6 text-center small text-muted mb-1">Tanggal cetak: <?= $tanggal_cetak ?></div>
                        </div>
                        <div class="row p-3 mt-1">
                            <div class="col-6 text-center">
                                <div class="small text-muted mb-3">Pejabat Penilai</div>
                                <div style="height:50px;"></div>
                                <div><?= htmlspecialchars($info['nama_pejabat'] ?? '....................') ?></div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="small text-muted mb-3">Pendidik/Tenaga Kependidikan</div>
                                <div style="height:50px;"></div>
                                <div><?= htmlspecialchars($info['nama_pegawai'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="row p-3 mt-4">
                            <div class="col-6 text-center">
                                <div class="small text-muted mb-3">Ketua Yayasan Permata Mojokerto</div>
                                <div style="height:50px;"></div>
                                <div><?= htmlspecialchars($ttd_ketum['nama'] ?? '....................') ?></div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="small text-muted mb-3">Kepala Bidang SDM</div>
                                <div style="height:50px;"></div>
                                <div><?= htmlspecialchars($ttd_kabid['nama'] ?? '....................') ?></div>
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
        <script>
        function editTg() {
            document.getElementById("tg_display").style.display = "none";
            document.getElementById("tg_input").style.display = "inline-block";
            document.getElementById("tg_save").style.display = "inline-block";
            document.getElementById("tg_edit").style.display = "none";
            document.getElementById("tg_cancel").style.display = "inline-block";
        }
        function batalTg() {
            document.getElementById("tg_display").style.display = "inline";
            document.getElementById("tg_input").style.display = "none";
            document.getElementById("tg_save").style.display = "none";
            document.getElementById("tg_edit").style.display = "inline-block";
            document.getElementById("tg_cancel").style.display = "none";
        }
        </script>
</body>

</html>