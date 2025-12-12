<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['admin']);

$conn = connectDB();
$filter_status = $_GET['status'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

$where = "WHERE 1=1";
if ($filter_status) $where .= " AND pm.status_hasil = '" . escapeString($filter_status) . "'";
if ($filter_bulan) $where .= " AND DATE_FORMAT(pm.tanggal_pm, '%Y-%m') = '" . escapeString($filter_bulan) . "'";

$pm_list = $conn->query("SELECT pm.*, k.kode_komputer, k.nama_komputer, k.lokasi, k.divisi, u.nama_lengkap as nama_teknisi FROM preventive_maintenance pm JOIN komputer k ON pm.komputer_id = k.komputer_id JOIN users u ON pm.siswa_pkl_id = u.user_id $where ORDER BY pm.tanggal_pm DESC");

$stats = [];
$result = $conn->query("SELECT status_hasil, COUNT(*) as total FROM preventive_maintenance GROUP BY status_hasil");
while ($row = $result->fetch_assoc()) $stats[$row['status_hasil']] = $row['total'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring PM - PM Kayaba</title>
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
        .stat-card-mini {
            border-radius: 8px;
            padding: 15px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Admin Dashboard</small>
        </div>       
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="users.php">
                <i class="fas fa-users me-2"></i>Manajemen User
            </a>
            <a class="nav-link" href="komputer.php">
                <i class="fas fa-desktop me-2"></i>Data Komputer
            </a>
            <a class="nav-link" href="jadwal_pm.php">
                <i class="fas fa-calendar-alt me-2"></i>Jadwal PM
            </a>
            <a class="nav-link" href="monitoring_pm.php">
                <i class="fas fa-clipboard-check me-2"></i>Monitoring PM
            </a>
            <a class="nav-link" href="monitoring_ticket.php">
                <i class="fas fa-ticket-alt me-2"></i>Monitoring Ticket
            </a>
            <a class="nav-link" href="laporan.php">
                <i class="fas fa-chart-bar me-2"></i>Laporan
            </a>
            <a class="nav-link" href="master_data.php">
                <i class="fas fa-database me-2"></i>Master Data
            </a>
        </nav>  
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></div>
                    <small>Administrator</small>
                </div>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="mb-4">
            <h2><i class="fas fa-clipboard-check me-2"></i>Monitoring Preventive Maintenance</h2>
            <p class="text-muted">Pantau hasil pemeliharaan komputer yang dilakukan siswa PKL</p>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card-mini bg-success">
                    <h6 class="mb-0">Kondisi Baik</h6>
                    <h3 class="mb-0"><?php echo $stats['baik'] ?? 0; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-mini bg-warning">
                    <h6 class="mb-0">Perlu Perbaikan</h6>
                    <h3 class="mb-0"><?php echo $stats['perlu_perbaikan'] ?? 0; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-mini bg-danger">
                    <h6 class="mb-0">Rusak</h6>
                    <h3 class="mb-0"><?php echo $stats['rusak'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Bulan</label>
                        <input type="month" name="bulan" class="form-control" value="<?php echo $filter_bulan; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filter Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="baik" <?php echo $filter_status == 'baik' ? 'selected' : ''; ?>>Baik</option>
                            <option value="perlu_perbaikan" <?php echo $filter_status == 'perlu_perbaikan' ? 'selected' : ''; ?>>Perlu Perbaikan</option>
                            <option value="rusak" <?php echo $filter_status == 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="monitoring_pm.php" class="btn btn-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal PM</th>
                                <th>Komputer</th>
                                <th>Lokasi</th>
                                <th>Teknisi</th>
                                <th>Status Hasil</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pm_list->num_rows > 0): ?>
                                <?php while($pm = $pm_list->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($pm['tanggal_pm'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($pm['tanggal_pm'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $pm['kode_komputer']; ?></strong><br>
                                        <small><?php echo $pm['nama_komputer']; ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo $pm['lokasi']; ?></small><br>
                                        <span class="badge bg-secondary"><?php echo $pm['divisi']; ?></span>
                                    </td>
                                    <td><?php echo $pm['nama_teknisi']; ?></td>
                                    <td><?php echo getStatusBadge($pm['status_hasil']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewDetailPM(<?php echo htmlspecialchars(json_encode($pm)); ?>)">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada data PM</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="detailPMModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-check me-2"></i>Detail Preventive Maintenance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailPMContent">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetailPM(pm) {
            const statusMap = {
                'baik': '<span class="badge bg-success">Baik</span>',
                'perlu_perbaikan': '<span class="badge bg-warning">Perlu Perbaikan</span>',
                'rusak': '<span class="badge bg-danger">Rusak</span>',
                'perlu_update': '<span class="badge bg-warning">Perlu Update</span>',
                'bermasalah': '<span class="badge bg-danger">Bermasalah</span>',
                'aktif': '<span class="badge bg-success">Aktif</span>',
                'tidak_aktif': '<span class="badge bg-danger">Tidak Aktif</span>',
                'terupdate': '<span class="badge bg-success">Terupdate</span>',
                'bersih': '<span class="badge bg-success">Bersih</span>',
                'sedikit_berdebu': '<span class="badge bg-warning">Sedikit Berdebu</span>',
                'sangat_berdebu': '<span class="badge bg-danger">Sangat Berdebu</span>',
                'rapi': '<span class="badge bg-success">Rapi</span>',
                'perlu_rapikan': '<span class="badge bg-warning">Perlu Rapikan</span>',
                'berantakan': '<span class="badge bg-danger">Berantakan</span>'
            };
            
            const content = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informasi Komputer</h6>
                        <table class="table table-sm">
                            <tr><th width="40%">Kode</th><td>${pm.kode_komputer}</td></tr>
                            <tr><th>Nama</th><td>${pm.nama_komputer}</td></tr>
                            <tr><th>Lokasi</th><td>${pm.lokasi}</td></tr>
                            <tr><th>Divisi</th><td>${pm.divisi}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi PM</h6>
                        <table class="table table-sm">
                            <tr><th width="40%">Tanggal</th><td>${new Date(pm.tanggal_pm).toLocaleString('id-ID')}</td></tr>
                            <tr><th>Teknisi</th><td>${pm.nama_teknisi}</td></tr>
                            <tr><th>Status Hasil</th><td>${statusMap[pm.status_hasil]}</td></tr>
                        </table>
                    </div>
                </div>
                
                <h6>Pengecekan Hardware</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered">
                            <tr><th width="50%">CPU</th><td>${statusMap[pm.cek_cpu]}</td></tr>
                            <tr><th>RAM</th><td>${statusMap[pm.cek_ram]}</td></tr>
                            <tr><th>Harddisk</th><td>${statusMap[pm.cek_harddisk]}</td></tr>
                            <tr><th>Motherboard</th><td>${statusMap[pm.cek_motherboard]}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered">
                            <tr><th width="50%">Power Supply</th><td>${statusMap[pm.cek_power_supply]}</td></tr>
                            <tr><th>Monitor</th><td>${statusMap[pm.cek_monitor]}</td></tr>
                            <tr><th>Keyboard</th><td>${statusMap[pm.cek_keyboard]}</td></tr>
                            <tr><th>Mouse</th><td>${statusMap[pm.cek_mouse]}</td></tr>
                        </table>
                    </div>
                </div>
                
                <h6>Pengecekan Software</h6>
                <table class="table table-sm table-bordered mb-3">
                    <tr><th width="25%">Operating System</th><td>${statusMap[pm.cek_os]}</td></tr>
                    <tr><th>Antivirus</th><td>${statusMap[pm.cek_antivirus]}</td></tr>
                    <tr><th>Software Update</th><td>${statusMap[pm.cek_software_update]}</td></tr>
                </table>
                
                <h6>Pengecekan Kebersihan</h6>
                <table class="table table-sm table-bordered mb-3">
                    <tr><th width="25%">Debu</th><td>${statusMap[pm.cek_debu]}</td></tr>
                    <tr><th>Kabel</th><td>${statusMap[pm.cek_kabel]}</td></tr>
                </table>
                
                ${pm.tindakan_perbaikan ? `
                    <h6>Tindakan Perbaikan</h6>
                    <div class="alert alert-info">${pm.tindakan_perbaikan}</div>
                ` : ''}
                
                ${pm.catatan ? `
                    <h6>Catatan</h6>
                    <div class="alert alert-secondary">${pm.catatan}</div>
                ` : ''}
                
                ${pm.foto_kondisi ? `
                    <h6>Foto Kondisi</h6>
                    <img src="../assets/uploads/${pm.foto_kondisi}" class="img-fluid" alt="Foto Kondisi">
                ` : ''}
            `;
            
            document.getElementById('detailPMContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailPMModal')).show();
        }
    </script>
</body>
</html>