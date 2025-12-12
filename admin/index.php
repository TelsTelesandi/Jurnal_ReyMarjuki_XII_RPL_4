    <?php
    require_once '../config/database.php';
    require_once '../includes/functions.php';
    checkRole(['admin']);
    $conn = connectDB();
    $stats = [];
    $stats['total_komputer'] = $conn->query("SELECT COUNT(*) as total FROM komputer")->fetch_assoc()['total'];
    $stats['komputer_bermasalah'] = $conn->query("SELECT COUNT(*) as total FROM komputer WHERE status_komputer IN ('perlu_perbaikan', 'rusak')")->fetch_assoc()['total'];
    $stats['pm_bulan_ini'] = $conn->query("SELECT COUNT(*) as total FROM preventive_maintenance WHERE MONTH(tanggal_pm) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_pm) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];
    $stats['laporan_pending'] = $conn->query("SELECT COUNT(*) as total FROM laporan_masalah WHERE status_laporan = 'menunggu'")->fetch_assoc()['total'];
    $result = $conn->query("SELECT role, COUNT(*) as total FROM users WHERE status = 'aktif' GROUP BY role");
    while ($row = $result->fetch_assoc()) $stats['user_' . $row['role']] = $row['total'];
    $chart_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M Y', strtotime("-$i months"));
        $row = $conn->query("SELECT COUNT(*) as total FROM preventive_maintenance WHERE DATE_FORMAT(tanggal_pm, '%Y-%m') = '$month'")->fetch_assoc();
        $chart_data[] = ['month' => $month_name, 'total' => $row['total']];
    }
    $laporan_terbaru = $conn->query("SELECT lm.*, k.kode_komputer, k.nama_komputer, u.nama_lengkap as nama_karyawan FROM laporan_masalah lm JOIN komputer k ON lm.komputer_id = k.komputer_id JOIN users u ON lm.karyawan_id = u.user_id ORDER BY lm.tanggal_laporan DESC LIMIT 5");
    $pm_terbaru = $conn->query("SELECT pm.*, k.kode_komputer, k.nama_komputer, u.nama_lengkap as nama_teknisi FROM preventive_maintenance pm JOIN komputer k ON pm.komputer_id = k.komputer_id JOIN users u ON pm.siswa_pkl_id = u.user_id ORDER BY pm.tanggal_pm DESC LIMIT 5");
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin - PM Kayaba</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body class="bg-gray-100">
        <!-- Sidebar -->
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Dashboard</h2>
                    <p class="text-muted">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>!</p>
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
                                <div class="stat-icon bg-gradient-primary text-white me-3">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0">Total Komputer</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_komputer']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-gradient-success text-white me-3">
                                    <i class="fas fa-clipboard-check"></i>
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
                                <div class="stat-icon bg-gradient-warning text-white me-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0">Komputer Bermasalah</h6>
                                    <h3 class="mb-0"><?php echo $stats['komputer_bermasalah']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>           
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-gradient-danger text-white me-3">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-0">Laporan Pending</h6>
                                    <h3 class="mb-0"><?php echo $stats['laporan_pending']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Statistik PM (6 Bulan Terakhir)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="pmChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Statistik User</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-user-shield text-primary me-2"></i>
                                    <strong>Admin</strong>
                                </div>
                                <span class="badge bg-primary"><?php echo $stats['user_admin'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-user-cog text-success me-2"></i>
                                    <strong>Siswa PKL</strong>
                                </div>
                                <span class="badge bg-success"><?php echo $stats['user_siswa_pkl'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-user text-info me-2"></i>
                                    <strong>Karyawan</strong>
                                </div>
                                <span class="badge bg-info"><?php echo $stats['user_karyawan'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Laporan Masalah Terbaru</h5>
                            <a href="monitoring_ticket.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Komputer</th>
                                            <th>Pelapor</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($laporan_terbaru->num_rows > 0): ?>
                                            <?php while($row = $laporan_terbaru->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $row['kode_komputer']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $row['judul_masalah']; ?></small>
                                                </td>
                                                <td><?php echo $row['nama_karyawan']; ?></td>
                                                <td><?php echo getStatusBadge($row['status_laporan']); ?></td>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($row['tanggal_laporan'])); ?></small></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">Belum ada laporan</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>PM Terbaru</h5>
                            <a href="monitoring_pm.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Komputer</th>
                                            <th>Teknisi</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($pm_terbaru->num_rows > 0): ?>
                                            <?php while($row = $pm_terbaru->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $row['kode_komputer']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $row['nama_komputer']; ?></small>
                                                </td>
                                                <td><?php echo $row['nama_teknisi']; ?></td>
                                                <td><?php echo getStatusBadge($row['status_hasil']); ?></td>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pm'])); ?></small></td>
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
        <script>
            const ctx = document.getElementById('pmChart').getContext('2d');
            const chartData = <?php echo json_encode($chart_data); ?>;   
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => d.month),
                    datasets: [{
                        label: 'Jumlah PM',
                        data: chartData.map(d => d.total),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        </script>
    </body>
    </html>