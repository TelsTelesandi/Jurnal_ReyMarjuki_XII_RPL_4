<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['siswa_pkl']);

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$stats = [];
$stats['total_pm'] = $conn->query("SELECT COUNT(*) as total FROM preventive_maintenance WHERE siswa_pkl_id = $user_id")->fetch_assoc()['total'];
$stats['pm_bulan_ini'] = $conn->query("SELECT COUNT(*) as total FROM preventive_maintenance WHERE siswa_pkl_id = $user_id AND MONTH(tanggal_pm) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_pm) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];
$stats['jadwal_pending'] = $conn->query("SELECT COUNT(*) as total FROM jadwal_pm WHERE siswa_pkl_id = $user_id AND status_jadwal = 'pending'")->fetch_assoc()['total'];
$stats['ticket_aktif'] = $conn->query("SELECT COUNT(*) as total FROM laporan_masalah WHERE siswa_pkl_id = $user_id AND status_laporan = 'ditindaklanjuti'")->fetch_assoc()['total'];
$stats['laporan_baru'] = $conn->query("SELECT COUNT(*) as total FROM laporan_masalah WHERE status_laporan = 'menunggu'")->fetch_assoc()['total'];

$jadwal_list = $conn->query("SELECT jp.*, k.kode_komputer, k.nama_komputer, k.lokasi, k.divisi FROM jadwal_pm jp JOIN komputer k ON jp.komputer_id = k.komputer_id WHERE jp.siswa_pkl_id = $user_id AND jp.status_jadwal = 'pending' AND jp.tanggal_jadwal BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) ORDER BY jp.tanggal_jadwal ASC LIMIT 5");
$laporan_baru = $conn->query("SELECT lm.*, k.kode_komputer, k.nama_komputer, k.lokasi, u.nama_lengkap as nama_karyawan FROM laporan_masalah lm JOIN komputer k ON lm.komputer_id = k.komputer_id JOIN users u ON lm.karyawan_id = u.user_id WHERE lm.status_laporan = 'menunggu' ORDER BY CASE lm.tingkat_urgensi WHEN 'kritis' THEN 1 WHEN 'tinggi' THEN 2 WHEN 'sedang' THEN 3 WHEN 'rendah' THEN 4 END, lm.tanggal_laporan DESC LIMIT 5");
$pm_terakhir = $conn->query("SELECT pm.*, k.kode_komputer, k.nama_komputer FROM preventive_maintenance pm JOIN komputer k ON pm.komputer_id = k.komputer_id WHERE pm.siswa_pkl_id = $user_id ORDER BY pm.tanggal_pm DESC LIMIT 5");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa PKL - PM Kayaba</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .stat-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
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
        .urgent-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Siswa PKL Dashboard</small>
        </div>     
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="form_pm.php">
                <i class="fas fa-clipboard-check me-2"></i>Input PM
            </a>
            <a class="nav-link" href="riwayat_pm.php">
                <i class="fas fa-history me-2"></i>Riwayat PM
            </a>
            <a class="nav-link" href="tindak_lanjut.php">
                <i class="fas fa-tasks me-2"></i>Tindak Lanjut Laporan
            </a>
        </nav>     
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <div><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></div>
                    <small>Siswa PKL / Teknisi</small>
                </div>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-light" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Dashboard Siswa PKL</h2>
                <p class="text-muted">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>! 👋</p>
            </div>
            <div>
                <span class="text-muted"><i class="far fa-calendar me-2"></i><?php echo formatTanggal(date('Y-m-d')); ?></span>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success text-white me-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">PM Bulan Ini</h6>
                                <h3 class="mb-0"><?php echo $stats['pm_bulan_ini']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>        
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary text-white me-3">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Jadwal Pending</h6>
                                <h3 class="mb-0"><?php echo $stats['jadwal_pending']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>         
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning text-white me-3">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Ticket Aktif</h6>
                                <h3 class="mb-0"><?php echo $stats['ticket_aktif']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>           
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger text-white me-3">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Laporan Baru</h6>
                                <h3 class="mb-0"><?php echo $stats['laporan_baru']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Jadwal PM (7 Hari Ke Depan)</h5>
                        <a href="form_pm.php" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i>Input PM
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($jadwal_list->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($jadwal = $jadwal_list->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo $jadwal['kode_komputer']; ?> - <?php echo $jadwal['nama_komputer']; ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i><?php echo $jadwal['lokasi']; ?> 
                                                | <span class="badge bg-secondary"><?php echo $jadwal['divisi']; ?></span>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-info">
                                                <?php echo date('d/m/Y', strtotime($jadwal['tanggal_jadwal'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-calendar-check fa-3x mb-3 opacity-25"></i>
                                <p>Tidak ada jadwal PM dalam 7 hari ke depan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Laporan Masalah Baru</h5>
                        <a href="tindak_lanjut.php" class="btn btn-sm btn-primary">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($laporan_baru->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($laporan = $laporan_baru->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h6 class="mb-0"><?php echo $laporan['judul_masalah']; ?></h6>
                                                <?php if ($laporan['tingkat_urgensi'] == 'kritis' || $laporan['tingkat_urgensi'] == 'tinggi'): ?>
                                                    <span class="badge bg-danger urgent-badge">
                                                        <?php echo strtoupper($laporan['tingkat_urgensi']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <strong><?php echo $laporan['kode_komputer']; ?></strong> - <?php echo $laporan['nama_karyawan']; ?><br>
                                                <i class="far fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($laporan['tanggal_laporan'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3 opacity-25"></i>
                                <p>Tidak ada laporan masalah baru</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>PM Terakhir Dikerjakan</h5>
                        <a href="riwayat_pm.php" class="btn btn-sm btn-outline-primary">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Komputer</th>
                                        <th>Status Hasil</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($pm_terakhir->num_rows > 0): ?>
                                        <?php while($pm = $pm_terakhir->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($pm['tanggal_pm'])); ?><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($pm['tanggal_pm'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo $pm['kode_komputer']; ?></strong><br>
                                                <small><?php echo $pm['nama_komputer']; ?></small>
                                            </td>
                                            <td><?php echo getStatusBadge($pm['status_hasil']); ?></td>
                                            <td><small><?php echo $pm['catatan'] ? substr($pm['catatan'], 0, 50) . '...' : '-'; ?></small></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada data PM</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>