<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['admin']);

if (!isset($_SESSION['nama_lengkap'])) $_SESSION['nama_lengkap'] = 'Administrator';

$conn = connectDB();
$periode_dari = $_GET['dari'] ?? date('Y-m-01');
$periode_sampai = $_GET['sampai'] ?? date('Y-m-t');
$tipe_laporan = $_GET['tipe'] ?? 'pm';

$laporan_pm = $conn->query("SELECT pm.*, k.kode_komputer, k.nama_komputer, k.lokasi, k.divisi, u.nama_lengkap as nama_teknisi FROM preventive_maintenance pm JOIN komputer k ON pm.komputer_id = k.komputer_id JOIN users u ON pm.siswa_pkl_id = u.user_id WHERE DATE(pm.tanggal_pm) BETWEEN '$periode_dari' AND '$periode_sampai' ORDER BY pm.tanggal_pm DESC");

$stats_pm = [];
$result = $conn->query("SELECT status_hasil, COUNT(*) as total FROM preventive_maintenance WHERE DATE(tanggal_pm) BETWEEN '$periode_dari' AND '$periode_sampai' GROUP BY status_hasil");
while ($row = $result->fetch_assoc()) $stats_pm[$row['status_hasil']] = $row['total'];

$laporan_masalah = $conn->query("SELECT lm.*, k.kode_komputer, k.nama_komputer, k.lokasi, u1.nama_lengkap as nama_karyawan, u2.nama_lengkap as nama_teknisi FROM laporan_masalah lm JOIN komputer k ON lm.komputer_id = k.komputer_id JOIN users u1 ON lm.karyawan_id = u1.user_id LEFT JOIN users u2 ON lm.siswa_pkl_id = u2.user_id WHERE DATE(lm.tanggal_laporan) BETWEEN '$periode_dari' AND '$periode_sampai' ORDER BY lm.tanggal_laporan DESC");

$stats_masalah = [];
$result = $conn->query("SELECT status_laporan, COUNT(*) as total FROM laporan_masalah WHERE DATE(tanggal_laporan) BETWEEN '$periode_dari' AND '$periode_sampai' GROUP BY status_laporan");
while ($row = $result->fetch_assoc()) $stats_masalah[$row['status_laporan']] = $row['total'];

$laporan_ticket = $conn->query("SELECT lm.*, k.kode_komputer, k.nama_komputer, k.lokasi, u1.nama_lengkap as nama_karyawan, u2.nama_lengkap as nama_teknisi FROM laporan_masalah lm JOIN komputer k ON lm.komputer_id = k.komputer_id JOIN users u1 ON lm.karyawan_id = u1.user_id LEFT JOIN users u2 ON lm.siswa_pkl_id = u2.user_id WHERE lm.status_laporan = 'selesai' AND DATE(lm.tanggal_selesai) BETWEEN '$periode_dari' AND '$periode_sampai' ORDER BY lm.tanggal_selesai DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PM Kayaba</title>
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
        @media print {
            .no-print {
                display: none !important;
            }
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
            @page { size: A4; margin: 15mm; }
            .card, .table { box-shadow: none !important; }
            .table-bordered > :not(caption) > * > * { border-color: #000 !important; }
            .stat-summary { display: none !important; }
            .print-header { display: flex; justify-content: space-between; align-items: center; }
            .print-title { text-align: center; font-weight: bold; }
        }
        .stat-summary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar no-print">
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
        <div class="mb-4 no-print">
            <h2><i class="fas fa-chart-bar me-2"></i>Laporan Aktivitas</h2>
            <p class="text-muted">Generate dan export laporan PM & Ticket</p>
        </div>
        <div class="card shadow-sm mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Periode Dari</label>
                        <input type="date" name="dari" class="form-control" value="<?php echo $periode_dari; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Periode Sampai</label>
                        <input type="date" name="sampai" class="form-control" value="<?php echo $periode_sampai; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipe Laporan</label>
                        <select name="tipe" class="form-select">
                            <option value="pm" <?php echo $tipe_laporan == 'pm' ? 'selected' : ''; ?>>Preventive Maintenance</option>
                            <option value="masalah" <?php echo $tipe_laporan == 'masalah' ? 'selected' : ''; ?>>Laporan Masalah</option>
                            <option value="ticket" <?php echo $tipe_laporan == 'ticket' ? 'selected' : ''; ?>>Monitoring Ticket (Selesai)</option>
                            <option value="semua" <?php echo $tipe_laporan == 'semua' ? 'selected' : ''; ?>>Semua Laporan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Tampilkan
                            </button>
                        </div>
                    </div>
                </form>               
                <hr>               
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-success">
                        <i class="fas fa-print me-2"></i>Print / PDF
                    </button>
                </div>
            </div>
        </div>
        <div class="d-none d-print-block mb-3">
            <div class="print-header">
                <div class="d-flex align-items-center">
                    <img src="../assets/logo_kyb.png" alt="KYB" style="height:48px;margin-right:10px;">
                    <div>
                        <div class="fw-bold">PT KAYABA INDONESIA</div>
                        <div class="small">Department IT</div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="small">Tanggal Export:</div>
                    <div class="fw-bold"><?php echo date('d/m/Y H:i'); ?></div>
                </div>
            </div>
            <div class="print-title mt-2">
                <div class="h5">LAPORAN PREVENTIVE MAINTENANCE KOMPUTER</div>
                <div>Periode: <?php echo formatTanggal($periode_dari); ?> s/d <?php echo formatTanggal($periode_sampai); ?></div>
            </div>
            <hr>
        </div>
        <?php if ($tipe_laporan == 'pm' || $tipe_laporan == 'semua'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Laporan Preventive Maintenance</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="stat-summary">
                            <h6 class="mb-3">Ringkasan PM</h6>
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h2><?php echo $stats_pm['baik'] ?? 0; ?></h2>
                                    <small>Kondisi Baik</small>
                                </div>
                                <div class="col-md-3">
                                    <h2><?php echo $stats_pm['perlu_perbaikan'] ?? 0; ?></h2>
                                    <small>Perlu Perbaikan</small>
                                </div>
                                <div class="col-md-3">
                                    <h2><?php echo $stats_pm['rusak'] ?? 0; ?></h2>
                                    <small>Rusak</small>
                                </div>
                                <div class="col-md-3">
                                    <h2><?php echo $laporan_pm->num_rows; ?></h2>
                                    <small>Total PM</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tablePM">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Kode Komputer</th>
                                <th>Nama Komputer</th>
                                <th>Lokasi</th>
                                <th>Teknisi</th>
                                <th>Status</th>
                                <th class="no-print">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $laporan_pm->data_seek(0);
                            if ($laporan_pm->num_rows > 0): 
                                while($pm = $laporan_pm->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pm['tanggal_pm'])); ?></td>
                                <td><?php echo $pm['kode_komputer']; ?></td>
                                <td><?php echo $pm['nama_komputer']; ?></td>
                                <td><?php echo $pm['lokasi']; ?></td>
                                <td><?php echo $pm['nama_teknisi']; ?></td>
                                <td>
                                    <?php
                                    if ($pm['status_hasil'] == 'baik') {
                                        echo '<span class="badge bg-success">Baik</span>';
                                    } elseif ($pm['status_hasil'] == 'perlu_perbaikan') {
                                        echo '<span class="badge bg-warning">Perlu Perbaikan</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Rusak</span>';
                                    }
                                    ?>
                                </td>
                                <td class="no-print"><small><?php echo $pm['catatan'] ?: '-'; ?></small></td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data PM pada periode ini</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($tipe_laporan == 'masalah' || $tipe_laporan == 'semua'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Laporan Masalah Komputer</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="stat-summary">
                            <h6 class="mb-3">Ringkasan Laporan Masalah</h6>
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <h2><?php echo $stats_masalah['menunggu'] ?? 0; ?></h2>
                                    <small>Menunggu</small>
                                </div>
                                <div class="col-md-3">
                                    <h2><?php echo $stats_masalah['ditindaklanjuti'] ?? 0; ?></h2>
                                    <small>Ditindaklanjuti</small>
                                </div>
                                <div class="col-md-3">
                                    <h2><?php echo $stats_masalah['selesai'] ?? 0; ?></h2>
                                    <small>Selesai</small>
                                </div>
                                <div class="col-md-3">
                                    <h2><?php echo $laporan_masalah->num_rows; ?></h2>
                                    <small>Total Laporan</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tableMasalah">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Komputer</th>
                                <th>Judul Masalah</th>
                                <th>Pelapor</th>
                                <th>Urgensi</th>
                                <th>Status</th>
                                <th>Teknisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $laporan_masalah->data_seek(0);
                            if ($laporan_masalah->num_rows > 0): 
                                while($lm = $laporan_masalah->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($lm['tanggal_laporan'])); ?></td>
                                <td><?php echo $lm['kode_komputer']; ?></td>
                                <td><?php echo $lm['judul_masalah']; ?></td>
                                <td><?php echo $lm['nama_karyawan']; ?></td>
                                <td>
                                    <?php
                                    $urgensi_colors = [
                                        'kritis' => 'danger',
                                        'tinggi' => 'warning',
                                        'sedang' => 'info',
                                        'rendah' => 'success'
                                    ];
                                    echo '<span class="badge bg-' . $urgensi_colors[$lm['tingkat_urgensi']] . '">' . ucfirst($lm['tingkat_urgensi']) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'menunggu' => 'secondary',
                                        'ditindaklanjuti' => 'info',
                                        'selesai' => 'success'
                                    ];
                                    echo '<span class="badge bg-' . $status_colors[$lm['status_laporan']] . '">' . ucfirst($lm['status_laporan']) . '</span>';
                                    ?>
                                </td>
                                <td><?php echo $lm['nama_teknisi'] ?: '-'; ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada laporan masalah pada periode ini</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($tipe_laporan == 'ticket' || $tipe_laporan == 'semua'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Laporan Monitoring Ticket (Selesai)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tableTicket">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal Selesai</th>
                                <th>Komputer</th>
                                <th>Judul Masalah</th>
                                <th>Pelapor</th>
                                <th>Teknisi</th>
                                <th>Urgensi</th>
                                <th class="no-print">Solusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $laporan_ticket->data_seek(0);
                            if ($laporan_ticket->num_rows > 0): 
                                while($tick = $laporan_ticket->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($tick['tanggal_selesai'])); ?></td>
                                <td>
                                    <strong><?php echo $tick['kode_komputer']; ?></strong><br>
                                    <small><?php echo $tick['lokasi']; ?></small>
                                </td>
                                <td><?php echo $tick['judul_masalah']; ?></td>
                                <td><?php echo $tick['nama_karyawan']; ?></td>
                                <td><?php echo $tick['nama_teknisi']; ?></td>
                                <td>
                                    <?php
                                    $urgensi_colors = [
                                        'kritis' => 'danger',
                                        'tinggi' => 'warning',
                                        'sedang' => 'info',
                                        'rendah' => 'success'
                                    ];
                                    echo '<span class="badge bg-' . $urgensi_colors[$tick['tingkat_urgensi']] . '">' . ucfirst($tick['tingkat_urgensi']) . '</span>';
                                    ?>
                                </td>
                                <td class="no-print"><small><?php echo substr($tick['solusi'], 0, 50); ?>...</small></td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada ticket selesai pada periode ini</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="d-none d-print-block mt-5">
            <div class="row">
                <div class="col-6 text-center">
                    <p>Mengetahui,<br>Kepala Bagian IT</p>
                    <br><br><br>
                    <p>(...........................)</p>
                </div>
                <div class="col-6 text-center">
                    <p>Cikarang, <?php echo formatTanggal(date('Y-m-d')); ?><br>Dibuat Oleh</p>
                    <br><br><br>
                    <p><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            const wb = XLSX.utils.book_new();
            
            <?php if ($tipe_laporan == 'pm' || $tipe_laporan == 'semua'): ?>
            // Export PM
            const tablePM = document.getElementById('tablePM');
            if (tablePM) {
                const wsPM = XLSX.utils.table_to_sheet(tablePM);
                XLSX.utils.book_append_sheet(wb, wsPM, 'Laporan PM');
            }
            <?php endif; ?>
            
            <?php if ($tipe_laporan == 'masalah' || $tipe_laporan == 'semua'): ?>
            // Export Masalah
            const tableMasalah = document.getElementById('tableMasalah');
            if (tableMasalah) {
                const wsMasalah = XLSX.utils.table_to_sheet(tableMasalah);
                XLSX.utils.book_append_sheet(wb, wsMasalah, 'Laporan Masalah');
            }
            <?php endif; ?>
            
            <?php if ($tipe_laporan == 'ticket' || $tipe_laporan == 'semua'): ?>
            // Export Ticket
            const tableTicket = document.getElementById('tableTicket');
            if (tableTicket) {
                const wsTicket = XLSX.utils.table_to_sheet(tableTicket);
                XLSX.utils.book_append_sheet(wb, wsTicket, 'Ticket Selesai');
            }
            <?php endif; ?>
            
            <?php if ($tipe_laporan == 'ticket' || $tipe_laporan == 'semua'): ?>
            // Export Ticket
            const tableTicket = document.getElementById('tableTicket');
            if (tableTicket) {
                const wsTicket = XLSX.utils.table_to_sheet(tableTicket);
                XLSX.utils.book_append_sheet(wb, wsTicket, 'Ticket Selesai');
            }
            <?php endif; ?>
            
            const filename = 'Laporan_PM_Kayaba_<?php echo date("Ymd"); ?>.xlsx';
            XLSX.writeFile(wb, filename);
        }
    </script>
</body>
</html>