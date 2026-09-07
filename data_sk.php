<?php
require_once 'config/database.php';
$msg = '';
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $conn->query("DELETE FROM data_sk WHERE id=" . (int)$_GET['hapus']);
    header("Location: data_sk.php?msg=" . urlencode("Data berhasil dihapus"));
    exit;
}
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    header('Content-Type: application/json');
    $gr = $conn->query("SELECT * FROM data_sk WHERE id=" . (int)$_GET['edit']);
    echo json_encode($gr->fetch_assoc());
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['no_sk', 'no_tambahan', 'nama', 'gelar', 'tempat_lahir', 'tanggal_lahir', 'nipy', 'gol_ruang', 'status_kepegawaian', 'unit_kerja', 'tmt', 'tanggal_mulai', 'berlaku', 'tanggal_akhir', 'tanggal_ditetapkan'];
    $sets = [];
    foreach ($fields as $fld) {
        $val = isset($_POST[$fld]) ? trim($_POST[$fld]) : '';
        $sets[] = "$fld=" . ($val === '' ? "NULL" : "'" . $conn->real_escape_string($val) . "'");
    }
    $set_str = implode(',', $sets);
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $conn->query("UPDATE data_sk SET $set_str WHERE id=" . (int)$_POST['id']);
        $msg = "Data berhasil diperbarui";
    } else {
        $conn->query("INSERT INTO data_sk SET $set_str");
        $msg = "Data berhasil disimpan";
    }
    header("Location: data_sk.php?msg=" . urlencode($msg));
    exit;
}
$res = $conn->query('SELECT * FROM data_sk ORDER BY id ASC');
$list = [];
while ($row = $res->fetch_assoc()) $list[] = $row;
$statusList = [];
$rs = $conn->query("SELECT * FROM status_kepegawaian ORDER BY nama_status");
while ($sr = $rs->fetch_assoc()) $statusList[] = $sr;
if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Data SK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            min-height: 100vh;
            padding: 20px;
            color: #fff;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
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
        }

        .sidebar .section-divider {
            border-color: #7f8c8d;
            margin: 5px 15px;
            opacity: .4;
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

        .sidebar a i {
            margin-right: 8px;
        }

        .main {
            background: #f4f6f9;
            min-height: 100vh;
            padding: 20px;
        }

        @media(max-width:785px) {
            .sidebar a span {
                display: none;
            }

            .sidebar a {
                text-align: center;
                padding: 12px;
            }
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
            margin-bottom: 20px;
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 12px 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .card-body {
            padding: 20px;
        }

        .form-label {
            font-size: 11px;
            font-weight: 600;
            color: #555;
            margin-bottom: 3px;
        }

        .form-control,
        .form-select {
            font-size: 12px;
            border-radius: 6px;
        }

        .btn {
            font-size: 12px;
            border-radius: 6px;
        }

        #modalForm .modal-body {
            max-height: 65vh;
            overflow-y: auto;
            padding: 15px 20px;
        }

        #modalForm .modal-footer {
            position: sticky;
            bottom: 0;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 12px 20px;
        }

        #tblSK {
            font-size: 0.82rem;
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        #tblSK thead th {
            font-size: 0.82rem;
            font-weight: 600;
            text-transform: uppercase;
            padding: 4px 8px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #2c3e50;
            background: #2c3e50;
            color: #fff;
            box-sizing: border-box;
        }

        #tblSK tbody td {
            padding: 4px 8px;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            font-size: 0.82rem;
            box-sizing: border-box;
        }

        #tblSK tbody tr:nth-child(odd)>td {
            background: #fafbfc;
        }

        #tblSK tbody tr:nth-child(even)>td {
            background: #ffffff;
        }

        #tblSK tbody tr:hover>td {
            background: #fff8e1 !important;
        }

        #tblSK .aksi-btns a {
            margin: 0 1px;
            display: inline-block;
        }

        #tblSK th,
        #tblSK td {
            box-sizing: border-box;
        }

        #tblSK select {
            font-size: 0.8rem;
            padding: 2px 4px;
        }

        .dataTables_wrapper {
            font-size: 11px;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            font-size: 11px;
            padding: 2px 6px;
        }

        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length {
            margin-bottom: 10px;
        }

        .btn-sm {
            font-size: 11px;
            padding: 2px 5px;
        }

        @media(max-width:785px) {
            .sidebar a span {
                display: none;
            }

            .sidebar a {
                text-align: center;
                padding: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <div class="brand">
                        <h4><i class="bi bi-building"></i> SIPS</h4><small>Sistem Informasi Pegawai</small>
                    </div>
                    <a href="index.php"><i class="bi bi-house"></i> Dashboard</a>
                    <div class="section-title">Pengajuan</div>
                    <hr class="section-divider">
                    <a href="pengajuan_admin.php"><i class="bi bi-file-earmark-text"></i> Manajemen Pengajuan</a>
                    <div class="section-title">Admin</div>
                    <hr class="section-divider">
                    <a href="profile_pegawai.php"><i class="bi bi-person-badge"></i> Profile Pegawai</a>
                    <a href="setup_users.php"><i class="bi bi-file-earmark-spreadsheet"></i> Manajemen User</a>
                    <a href="data_mou.php"><i class="bi bi-file-earmark-ruled"></i> Data MOU</a>
                    <a href="data_sk.php" class="active"><i class="bi bi-file-earmark-text"></i> Data SK</a>
                    <div class="section-title">Penilaian Kinerja</div>
                    <hr class="section-divider">
                    <a href="kinerja_status.php"><i class="bi bi-person-check"></i> Status</a>
                    <a href="kinerja_periode.php"><i class="bi bi-calendar3"></i> Periode</a>
                    <a href="kinerja_pejabat.php"><i class="bi bi-award"></i> Pejabat</a>
                    <a href="kinerja_bahan.php"><i class="bi bi-file-earmark-text"></i> Bahan Penilaian</a>
                    <a href="form_penilaian.php"><i class="bi bi-clipboard-check"></i> Form Penilaian</a>
                    <a href="laporan_penilaian.php"><i class="bi bi-bar-chart"></i> Laporan</a>
                    <a href="login.php" style="color:#e74c3c;margin-top:30px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
            <div class="col-md-10 p-4" style="background:#f4f6f9;min-height:100vh;">

                <h2><i class="bi bi-file-earmark-text"></i> Manajemen Data SK</h2>
                <p class="text-muted">Kelola data Surat Keputusan (SK) pegawai.</p>
                <hr>

                <?php if ($msg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= $msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list"></i> Daftar Data SK</h5>
                        <div class="d-flex gap-2">
                            <a href="export_sk.php" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Export Excel</a>
                            <a href="import_sk.php" class="btn btn-info btn-sm"><i class="bi bi-upload"></i> Import Excel</a>
                            <button type="button" class="btn btn-primary btn-sm" id="btnTambah"><i class="bi bi-plus-circle"></i> Tambah</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tblSK" class="table table-hover table-sm" style="width:100%;table-layout:fixed;border-collapse:collapse;">

                                <thead>
                                    <tr>
                                        <th style="width:30px;text-align:center;">No</th>
                                        <th style="width:30px;text-align:center;">No. SK</th>
                                        <th style="width:30px;text-align:center;">No. Tambahan</th>
                                        <th style="width:130px;text-align:center;">Nama</th>
                                        <th style="width:50px;text-align:center;">Gelar</th>
                                        <th style="width:70px;text-align:center;">NIPY</th>
                                        <th style="width:75px;text-align:center;">Gol/Ruang</th>
                                        <th style="width:75px;text-align:center;">Status Kepegawaian</th>
                                        <th style="width:50px;text-align:center;">Unit Kerja</th>
                                        <th style="width:80px;text-align:center;">TMT</th>
                                        <th style="width:85px;text-align:center;">Tanggal Mulai</th>
                                        <th style="width:85px;text-align:center;">Berlaku</th>
                                        <th style="width:85px;text-align:center;">Tanggal Akhir</th>
                                        <th style="width:85px;text-align:center;">Tanggal Ditetapkan</th>
                                        <th style="width:85px;text-align:center;">Tempat Lahir</th>
                                        <th style="width:75px;text-align:center;">Tanggal Lahir</th>
                                        <th style="width:65px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($list as $i => $row): ?>
                                        <tr data-id="<?= $row['id'] ?>">
                                            <td style="width:30px;text-align:center;"><?= $i + 1 ?></td>
                                            <td style="width:30px;text-align:center;"><?= htmlspecialchars($row['no_sk']) ?></td>
                                            <td style="width:85px;text-align:center;"><?= htmlspecialchars($row['no_tambahan']) ?></td>
                                            <td style="width:130px;text-align:center;"><?= htmlspecialchars($row['nama']) ?></td>
                                            <td style="width:70px;text-align:center;"><?= htmlspecialchars($row['gelar']) ?></td>
                                            <td style="width:70px;text-align:center;"><?= htmlspecialchars($row['nipy']) ?></td>
                                            <td style="width:35px;text-align:center;"><?= htmlspecialchars($row['gol_ruang']) ?></td>
                                            <td style="width:75px;text-align:center;"><?= htmlspecialchars($row['status_kepegawaian']) ?></td>
                                            <td style="width:75px;text-align:center;"><?= htmlspecialchars($row['unit_kerja']) ?></td>
                                            <td style="width:35px;text-align:center;"><?= htmlspecialchars($row['tmt']) ?></td>
                                            <td style="width:35px;text-align:center;"><?= htmlspecialchars($row['tanggal_mulai']) ?></td>
                                            <td style="width:35px;text-align:center;"><?= htmlspecialchars($row['berlaku']) ?></td>
                                            <td style="width:35px;text-align:center;"><?= htmlspecialchars($row['tanggal_akhir']) ?></td>
                                            <td style="width:35px;text-align:center;"><?= htmlspecialchars($row['tanggal_ditetapkan']) ?></td>
                                            <td style="width:70px;text-align:center;"><?= htmlspecialchars($row['tempat_lahir']) ?></td>
                                            <td style="width:35px;text-align:center;"><?= htmlspecialchars($row['tanggal_lahir']) ?></td>
                                            <td style="width:65px;text-align:center;">
                                                <a href="javascript:void(0)" class="btn btn-sm btn-warning" onclick="openEdit(<?= $row['id'] ?>)" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')" title="Hapus"><i class="bi bi-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalForm" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-circle"></i> Tambah Data SK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="frmSK">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="f_id">
                        <div class="row g-2">
                            <div class="col-md-3"><label class="form-label required">No. SK</label><input type="text" name="no_sk" id="f_no_sk" class="form-control form-control-sm" required></div>
                            <div class="col-md-3"><label class="form-label">No. Tambahan</label><input type="text" name="no_tambahan" id="f_no_tambahan" class="form-control form-control-sm"></div>
                            <div class="col-md-4"><label class="form-label required">Nama</label><input type="text" name="nama" id="f_nama" class="form-control form-control-sm" required></div>
                            <div class="col-md-2"><label class="form-label">Gelar</label><input type="text" name="gelar" id="f_gelar" class="form-control form-control-sm" placeholder="S.Pd., M.Pd."></div>
                            <div class="col-md-3"><label class="form-label">NIPY</label><input type="text" name="nipy" id="f_nipy" class="form-control form-control-sm"></div>
                            <div class="col-md-2"><label class="form-label">Gol/Ruang</label><input type="text" name="gol_ruang" id="f_gol_ruang" class="form-control form-control-sm" placeholder="III/A"></div>
                            <div class="col-md-3"><label class="form-label">Status Kepegawaian</label>
                                <select name="status_kepegawaian" id="f_status_kepegawaian" class="form-select form-select-sm">
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ($statusList as $s): ?>
                                        <option value="<?= htmlspecialchars($s['nama_status']) ?>"><?= htmlspecialchars($s['nama_status']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">Unit Kerja</label><input type="text" name="unit_kerja" id="f_unit_kerja" class="form-control form-control-sm"></div>
                            <div class="col-md-2"><label class="form-label">TMT</label><input type="date" name="tmt" id="f_tmt" class="form-control form-control-sm"></div>
                            <div class="col-md-2"><label class="form-label">Tanggal Mulai</label><input type="date" name="tanggal_mulai" id="f_tanggal_mulai" class="form-control form-control-sm"></div>
                            <div class="col-md-2"><label class="form-label">Berlaku</label><input type="text" name="berlaku" id="f_berlaku" class="form-control form-control-sm" placeholder="Selama hubungan kerja"></div>
                            <div class="col-md-2"><label class="form-label">Tanggal Akhir</label><input type="date" name="tanggal_akhir" id="f_tanggal_akhir" class="form-control form-control-sm"></div>
                            <div class="col-md-2"><label class="form-label">Tanggal Ditetapkan</label><input type="date" name="tanggal_ditetapkan" id="f_tanggal_ditetapkan" class="form-control form-control-sm"></div>
                            <div class="col-md-3"><label class="form-label">Tempat Lahir</label><input type="text" name="tempat_lahir" id="f_tempat_lahir" class="form-control form-control-sm"></div>
                            <div class="col-md-3"><label class="form-label">Tanggal Lahir</label><input type="date" name="tanggal_lahir" id="f_tanggal_lahir" class="form-control form-control-sm"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Simpan</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
            $('#tblSK').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                pageLength: 10,
                scrollX: true,
                order: [],
                columnDefs: [{
                    targets: '_all',
                    orderable: true
                }]
            });
            var modal = new bootstrap.Modal(document.getElementById('modalForm'));
            $('#btnTambah').click(function() {
                $('#frmSK')[0].reset();
                $('#f_id').val('');
                $('#modalTitle').html('<i class="bi bi-plus-circle"></i> Tambah Data SK');
                modal.show();
            });
            window.openEdit = function(id) {
                $('#frmSK')[0].reset();
                $('#modalTitle').html('<i class="bi bi-pencil"></i> Edit Data SK');
                $.get('?edit=' + id, function(d) {
                    var fields = ['id', 'no_sk', 'no_tambahan', 'nama', 'gelar', 'nipy', 'gol_ruang', 'status_kepegawaian', 'unit_kerja', 'tmt', 'tanggal_mulai', 'berlaku', 'tanggal_akhir', 'tanggal_ditetapkan', 'tempat_lahir', 'tanggal_lahir'];
                    fields.forEach(function(k) {
                        var el = document.getElementById('f_' + k);
                        if (el) el.value = (d[k] || '');
                    });
                    modal.show();
                }, 'json');
            };
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 3000);
        });
    </script>
</body>

</html>