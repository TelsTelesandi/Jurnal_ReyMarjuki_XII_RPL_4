<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

checkRole(['karyawan']);

$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Filter
$filter_status = $_GET['status'] ?? '';

// Query Laporan
$where = "WHERE lm.karyawan_id = $user_id";
if ($filter_status) {
    $where .= " AND lm.status_laporan = '" . escapeString($filter_status) . "'";
}

$laporan_list = $conn->query("
    SELECT lm.*, k.kode_komputer, k.nama_komputer, k.lokasi, u.nama_lengkap as nama_teknisi
    FROM laporan_masalah lm
    JOIN komputer k ON lm.komputer_id = k.komputer_id
    LEFT JOIN users u ON lm.siswa_pkl_id = u.user_id
    $where
    ORDER BY lm.tanggal_laporan DESC
");

// Statistik
$stats = [];
$result = $conn->query("SELECT status_laporan, COUNT(*) as total FROM laporan_masalah WHERE karyawan_id = $user_id GROUP BY status_laporan");
while ($row = $result->fetch_assoc()) {
    $stats[$row['status_laporan']] = $row['total'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - PM Kayaba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            width: 250px;
            padding: 0;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .user-info {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
        }
    @media print { .no-print{display:none!important} .sidebar{display:none} .main-content{margin-left:0} @page { size: A4; margin: 15mm; } .table-bordered > :not(caption) > * > * { border-color: #000 !important; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Dashboard Karyawan</small>
        </div>       
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="laporan_masalah.php">
                <i class="fas fa-exclamation-circle me-2"></i>Laporkan Masalah
            </a>
            <a class="nav-link active" href="riwayat_laporan.php">
                <i class="fas fa-history me-2"></i>Riwayat Laporan
            </a>
        </nav>        
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></div>
                    <small>Karyawan</small>
                </div>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="mb-4">
            <h2><i class="fas fa-history me-2"></i>Riwayat Laporan Masalah</h2>
            <p class="text-muted">Pantau status laporan yang telah Anda kirimkan</p>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h6>Menunggu</h6>
                        <h2><?php echo $stats['menunggu'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6>Ditindaklanjuti</h6>
                        <h2><?php echo $stats['ditindaklanjuti'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>Selesai</h6>
                        <h2><?php echo $stats['selesai'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?php echo $filter_status == 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="ditindaklanjuti" <?php echo $filter_status == 'ditindaklanjuti' ? 'selected' : ''; ?>>Ditindaklanjuti</option>
                            <option value="selesai" <?php echo $filter_status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="riwayat_laporan.php" class="btn btn-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                            <button type="button" onclick="window.print()" class="btn btn-success no-print">
                                <i class="fas fa-print me-2"></i>Print / PDF
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="d-none d-print-block mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <img src="../assets/logo_kyb.png" alt="KYB" style="height:48px;">
                <div class="text-end">
                    <div class="small">Tanggal Export:</div>
                    <div class="fw-bold"><?php echo date('d/m/Y H:i'); ?></div>
                </div>
            </div>
            <div class="text-center mt-2">
                <h5 class="mb-0">RIWAYAT LAPORAN MASALAH</h5>
                <small>Pelapor: <?php echo $_SESSION['nama_lengkap']; ?></small>
            </div>
            <hr>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Komputer</th>
                                <th>Judul Masalah</th>
                                <th>Urgensi</th>
                                <th>Status</th>
                                <th>Teknisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($laporan_list->num_rows > 0): ?>
                                <?php while($lap = $laporan_list->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($lap['tanggal_laporan'])); ?><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($lap['tanggal_laporan'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $lap['kode_komputer']; ?></strong><br>
                                        <small><?php echo $lap['nama_komputer']; ?></small>
                                    </td>
                                    <td><?php echo $lap['judul_masalah']; ?></td>
                                    <td>
                                        <?php
                                        $urgensi_colors = [
                                            'kritis' => 'danger',
                                            'tinggi' => 'warning',
                                            'sedang' => 'info',
                                            'rendah' => 'success'
                                        ];
                                        echo '<span class="badge bg-' . $urgensi_colors[$lap['tingkat_urgensi']] . '">' . ucfirst($lap['tingkat_urgensi']) . '</span>';
                                        ?>
                                    </td>
                                    <td><?php echo getStatusBadge($lap['status_laporan']); ?></td>
                                    <td><?php echo $lap['nama_teknisi'] ?: '-'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewDetail(<?php echo htmlspecialchars(json_encode($lap)); ?>)">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada riwayat laporan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent"></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetail(lap) {
            const statusMap = {
                'menunggu': '<span class="badge bg-secondary">Menunggu</span>',
                'ditindaklanjuti': '<span class="badge bg-info">Ditindaklanjuti</span>',
                'selesai': '<span class="badge bg-success">Selesai</span>'
            };
            
            const urgensiMap = {
                'kritis': '<span class="badge bg-danger">KRITIS</span>',
                'tinggi': '<span class="badge bg-warning text-dark">TINGGI</span>',
                'sedang': '<span class="badge bg-info">SEDANG</span>',
                'rendah': '<span class="badge bg-success">RENDAH</span>'
            };
            
            let content = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informasi Laporan</h6>
                        <table class="table table-sm">
                            <tr><th width="40%">Judul</th><td><strong>${lap.judul_masalah}</strong></td></tr>
                            <tr><th>Kategori</th><td><span class="badge bg-dark">${lap.kategori}</span></td></tr>
                            <tr><th>Urgensi</th><td>${urgensiMap[lap.tingkat_urgensi]}</td></tr>
                            <tr><th>Status</th><td>${statusMap[lap.status_laporan]}</td></tr>
                            <tr><th>Tanggal Laporan</th><td>${new Date(lap.tanggal_laporan).toLocaleString('id-ID')}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Komputer</h6>
                        <table class="table table-sm">
                            <tr><th width="40%">Kode</th><td>${lap.kode_komputer}</td></tr>
                            <tr><th>Nama</th><td>${lap.nama_komputer}</td></tr>
                            <tr><th>Lokasi</th><td>${lap.lokasi}</td></tr>
                        </table>
                    </div>
                </div>
                
                <h6>Deskripsi Masalah</h6>
                <div class="alert alert-light">${lap.deskripsi_masalah}</div>
                
                ${lap.foto_masalah ? `
                    <h6>Foto Masalah</h6>
                    <img src="../assets/uploads/${lap.foto_masalah}" class="img-fluid mb-3" alt="Foto Masalah">
                ` : ''}
            `;
            
            if (lap.status_laporan !== 'menunggu') {
                content += `
                    <h6>Informasi Penanganan</h6>
                    <table class="table table-sm table-bordered">
                        <tr><th width="30%">Teknisi</th><td>${lap.nama_teknisi || '-'}</td></tr>
                        <tr><th>Tanggal Ditangani</th><td>${lap.tanggal_ditangani ? new Date(lap.tanggal_ditangani).toLocaleString('id-ID') : '-'}</td></tr>
                `;
                
                if (lap.status_laporan === 'selesai') {
                    content += `
                        <tr><th>Tanggal Selesai</th><td>${lap.tanggal_selesai ? new Date(lap.tanggal_selesai).toLocaleString('id-ID') : '-'}</td></tr>
                        <tr><th>Solusi</th><td>${lap.solusi || '-'}</td></tr>
                    `;
                }
                
                if (lap.catatan_teknisi) {
                    content += `<tr><th>Catatan Teknisi</th><td>${lap.catatan_teknisi}</td></tr>`;
                }
                
                content += `</table>`;
            }
            
            document.getElementById('detailContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }
    </script>
</body>
</html>