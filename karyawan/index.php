<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['karyawan']);

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$stats = [];
$stats['komputer_saya'] = $conn->query("SELECT COUNT(*) as total FROM komputer WHERE user_id = $user_id")->fetch_assoc()['total'];
$stats['total_laporan'] = $conn->query("SELECT COUNT(*) as total FROM laporan_masalah WHERE karyawan_id = $user_id")->fetch_assoc()['total'];
$stats['laporan_pending'] = $conn->query("SELECT COUNT(*) as total FROM laporan_masalah WHERE karyawan_id = $user_id AND status_laporan = 'menunggu'")->fetch_assoc()['total'];
$stats['laporan_selesai'] = $conn->query("SELECT COUNT(*) as total FROM laporan_masalah WHERE karyawan_id = $user_id AND status_laporan = 'selesai'")->fetch_assoc()['total'];

$komputer_saya = $conn->query("SELECT k.*, (SELECT COUNT(*) FROM laporan_masalah WHERE komputer_id = k.komputer_id AND status_laporan != 'selesai') as masalah_aktif FROM komputer k WHERE k.user_id = $user_id ORDER BY k.kode_komputer");
$laporan_terbaru = $conn->query("SELECT lm.*, k.kode_komputer, k.nama_komputer, u.nama_lengkap as nama_teknisi FROM laporan_masalah lm JOIN komputer k ON lm.komputer_id = k.komputer_id LEFT JOIN users u ON lm.siswa_pkl_id = u.user_id WHERE lm.karyawan_id = $user_id ORDER BY lm.tanggal_laporan DESC LIMIT 5");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - PM Kayaba</title>
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
        .komputer-card {
            border-left: 4px solid #28a745;
            transition: all 0.3s;
        }
        .komputer-card.warning {
            border-left-color: #ffc107;
        }
        .komputer-card.danger {
            border-left-color: #dc3545;
        }
        .komputer-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Dashboard Karyawan</small>
        </div>       
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="laporan_masalah.php">
                <i class="fas fa-exclamation-circle me-2"></i>Laporkan Masalah
            </a>
            <a class="nav-link" href="riwayat_laporan.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Dashboard Karyawan</h2>
                <p class="text-muted">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>! 👋</p>
            </div>
            <div>
                <a href="laporan_masalah.php" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Laporkan Masalah
                </a>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary text-white me-3">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Komputer Saya</h6>
                                <h3 class="mb-0"><?php echo $stats['komputer_saya']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>         
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info text-white me-3">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Total Laporan</h6>
                                <h3 class="mb-0"><?php echo $stats['total_laporan']; ?></h3>
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
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Pending</h6>
                                <h3 class="mb-0"><?php echo $stats['laporan_pending']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>           
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success text-white me-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Selesai</h6>
                                <h3 class="mb-0"><?php echo $stats['laporan_selesai']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-desktop me-2"></i>Komputer Saya</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($komputer_saya->num_rows > 0): ?>
                            <?php while($pc = $komputer_saya->fetch_assoc()): ?>
                            <div class="card komputer-card <?php echo $pc['masalah_aktif'] > 0 ? 'warning' : ''; ?> mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo $pc['kode_komputer']; ?> - <?php echo $pc['nama_komputer']; ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i><?php echo $pc['lokasi']; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php echo getStatusBadge($pc['status_komputer']); ?>
                                            <?php if ($pc['masalah_aktif'] > 0): ?>
                                                <span class="badge bg-warning text-dark ms-1">
                                                    <?php echo $pc['masalah_aktif']; ?> Masalah Aktif
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-desktop fa-3x mb-3 opacity-25"></i>
                                <p>Belum ada komputer yang ditugaskan kepada Anda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Laporan Terbaru</h5>
                        <a href="riwayat_laporan.php" class="btn btn-sm btn-outline-primary">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($laporan_terbaru->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while($lap = $laporan_terbaru->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo $lap['judul_masalah']; ?></h6>
                                            <small class="text-muted">
                                                <strong><?php echo $lap['kode_komputer']; ?></strong><br>
                                                <i class="far fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($lap['tanggal_laporan'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php echo getStatusBadge($lap['status_laporan']); ?>
                                            <?php if ($lap['nama_teknisi']): ?>
                                                <br><small class="text-muted"><?php echo $lap['nama_teknisi']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                                <p>Belum ada laporan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-info-circle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Butuh Bantuan?</h5>
                            <p class="mb-0">Jika komputer Anda mengalami masalah, segera laporkan melalui menu <strong>"Laporkan Masalah"</strong>. Teknisi kami akan segera menindaklanjuti laporan Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>