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
$filter_pegawai_id = (int)($_GET['pegawai_id'] ?? 0);
$filter_periode_id = (int)($_GET['periode_id'] ?? 0);
$filter_pejabat_id = (int)($_GET['pejabat_id'] ?? 0);
if (!$is_admin) {
    $filter_pegawai_id = $pegawai['id'] ?? 0;
}
$list_pegawai = [];
if ($is_admin) {
    $r = $conn->query("SELECT * FROM pegawai ORDER BY nama");
    if ($r) while ($row = $r->fetch_assoc()) $list_pegawai[] = $row;
}
$list_pejabat = [];
$r = $conn->query("SELECT * FROM pejabat_penilai ORDER BY nama");
if ($r) while ($row = $r->fetch_assoc()) $list_pejabat[] = $row;
$list_periode = [];
$r = $conn->query("SELECT * FROM periode_penilaian ORDER BY tahun DESC");
if ($r) while ($row = $r->fetch_assoc()) $list_periode[] = $row;
$where = [];
if ($filter_pegawai_id > 0) $where[] = "fp.pegawai_id = $filter_pegawai_id";
if ($filter_periode_id > 0) $where[] = "fp.periode_id = $filter_periode_id";
if ($filter_pejabat_id > 0) $where[] = "fp.pejabat_id = $filter_pejabat_id";
$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$laporan = [];
$sql = "SELECT fp.*, p.nama as nama_pegawai, p.jabatan as jabatan_pegawai, p.status_kepegawaian, pj.nama as nama_pejabat, pj.jabatan as jabatan_pejabat, pr.nama_kuartal, pr.periode_bulan, pr.tahun FROM form_penilaian fp LEFT JOIN pegawai p ON fp.pegawai_id = p.id LEFT JOIN pejabat_penilai pj ON fp.pejabat_id = pj.id LEFT JOIN periode_penilaian pr ON fp.periode_id = pr.id $where_sql ORDER BY fp.pegawai_id, pr.tahun DESC";
$res = $conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $key = $r['pegawai_id'] . '_' . $r['periode_id'];
        $laporan[$key]['info'] = ['pegawai_id' => $r['pegawai_id'], 'nama_pegawai' => $r['nama_pegawai'], 'jabatan_pegawai' => $r['jabatan_pegawai'], 'status_kepegawaian' => $r['status_kepegawaian'], 'nama_pejabat' => $r['nama_pejabat'], 'jabatan_pejabat' => $r['jabatan_pejabat'], 'nama_kuartal' => $r['nama_kuartal'], 'periode_bulan' => $r['periode_bulan'], 'tahun' => $r['tahun']];
        $laporan[$key]['nilai'][$r['nama_kolom']] = $r;
    }
}
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
        body {
            font-size: 0.85rem;
        }

        .lp {
            font-size: 0.82rem;
        }

        .lp th,
        .lp td {
            padding: 4px 8px;
            vertical-align: middle;
        }

        .lp .aspek {
            background: #2c3e50;
            color: #fff;
            font-weight: bold;
            padding: 6px 10px;
        }

        .lp .no-col {
            width: 30px;
            text-align: center;
        }

        .lp .nilai-col {
            width: 50px;
            text-align: center;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }

            body {
                background: #fff !important;
            }

            .col-md-10 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0"><?php include 'includes/sidebar.php'; ?></div>
            <div class="col-md-10 p-3" style="background:#f4f6f9;min-height:100vh;">
                <h5 class="mb-1"><i class="bi bi-file-earmark-bar-graph"></i> Laporan Penilaian Kinerja</h5>
                <p class="text-muted small mb-2"><?= $is_admin ? 'Semua laporan penilaian dari seluruh akun.' : 'Laporan penilaian Anda.' ?></p>
                <div class="card shadow-sm lp mb-2 no-print">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h6>
                    </div>
                    <div class="card-body p-2">
                        <form method="GET" class="row g-2 align-items-end">
                            <?php if ($is_admin): ?>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Pegawai</label>
                                    <select name="pegawai_id" class="form-select form-select-sm">
                                        <option value="">-- Semua Pegawai --</option>
                                        <?php foreach ($list_pegawai as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= $filter_pegawai_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['jabatan']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Periode</label>
                                <select name="periode_id" class="form-select form-select-sm">
                                    <option value="">-- Semua Periode --</option>
                                    <?php foreach ($list_periode as $pr): ?>
                                        <option value="<?= $pr['id'] ?>" <?= $filter_periode_id == $pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['nama_kuartal'] ?? '') ?> - <?= htmlspecialchars($pr['periode_bulan'] ?? '') ?> <?= $pr['tahun'] ?? '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($is_admin): ?>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Pejabat Penilai</label>
                                    <select name="pejabat_id" class="form-select form-select-sm">
                                        <option value="">-- Semua Pejabat --</option>
                                        <?php foreach ($list_pejabat as $pj): ?>
                                            <option value="<?= $pj['id'] ?>" <?= $filter_pejabat_id == $pj['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pj['nama']) ?></option>
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
                        <div class="card shadow-sm lp">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2">Tidak ada data laporan<?= $is_admin ? '' : ' Anda' ?>.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm lp">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0"><i class="bi bi-list-ul"></i> Daftar Laporan Anda</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-sm mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-center" width="50">No</th>
                                                <th>Pegawai</th>
                                                <th>Jabatan</th>
                                                <th>Periode</th>
                                                <th>Pejabat Penilai</th>
                                                <th class="text-center" width="80">Total</th>
                                                <th class="text-center" width="80">Rata-rata</th>
                                                <th class="text-center" width="100">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 1;
                                            foreach ($laporan as $key => $lap):
                                                $info = $lap['info'];
                                                $total = 0;
                                                $jml = 0;
                                                foreach ($lap['nilai'] as $n) {
                                                    if ($n['nilai'] > 0) {
                                                        $total += $n['nilai'];
                                                        $jml++;
                                                    }
                                                }
                                                $rata = $jml > 0 ? round($total / $jml, 2) : 0;
                                            ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++ ?></td>
                                                    <td><strong><?= htmlspecialchars($info['nama_pegawai'] ?? '-') ?></strong><br><small class="text-muted"><?= htmlspecialchars($info['status_kepegawaian'] ?? '-') ?></small></td>
                                                    <td><?= htmlspecialchars($info['jabatan_pegawai'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($info['nama_kuartal'] ?? '') ?> <?= htmlspecialchars($info['periode_bulan'] ?? '') ?> <?= $info['tahun'] ?? '' ?></td>
                                                    <td><?= htmlspecialchars($info['nama_pejabat'] ?? '-') ?><br><small class="text-muted"><?= htmlspecialchars($info['jabatan_pejabat'] ?? '') ?></small></td>
                                                    <td class="text-center"><span class="badge bg-primary"><?= $total ?></span></td>
                                                    <td class="text-center"><span class="badge bg-warning text-dark"><?= $rata ?></span></td>
                                                    <td class="text-center">
                                                        <a href="laporan_detail.php?key=<?= urlencode($key) ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> Read</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>
</div>