<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

checkRole(['siswa_pkl']);

$conn = connectDB();
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle Ambil Ticket
if (isset($_POST['ambil_ticket'])) {
    $message = 'Penugasan dilakukan oleh admin. Teknisi tidak mengambil ticket sendiri.';
    $message_type = 'warning';
}

// Handle Selesaikan Ticket
if (isset($_POST['selesaikan_ticket'])) {
    $laporan_id = intval($_POST['laporan_id']);
    $solusi = escapeString($_POST['solusi']);
    $catatan_teknisi = escapeString($_POST['catatan_teknisi']);
    
    $stmt = $conn->prepare("UPDATE laporan_masalah SET status_laporan = 'selesai', tanggal_selesai = NOW(), solusi = ?, catatan_teknisi = ? WHERE laporan_id = ?");
    $stmt->bind_param("ssi", $solusi, $catatan_teknisi, $laporan_id);
    
    if ($stmt->execute()) {
        $message = 'Ticket berhasil diselesaikan!';
        $message_type = 'success';
    }
    $stmt->close();
}

// Get Laporan Baru (Menunggu)
$laporan_baru = $conn->query("
    SELECT lm.*, k.kode_komputer, k.nama_komputer, k.lokasi, k.divisi, u.nama_lengkap as nama_karyawan
    FROM laporan_masalah lm
    JOIN komputer k ON lm.komputer_id = k.komputer_id
    JOIN users u ON lm.karyawan_id = u.user_id
    WHERE lm.status_laporan = 'menunggu'
    ORDER BY 
        CASE lm.tingkat_urgensi
            WHEN 'kritis' THEN 1
            WHEN 'tinggi' THEN 2
            WHEN 'sedang' THEN 3
            WHEN 'rendah' THEN 4
        END,
        lm.tanggal_laporan DESC
");

// Get Ticket Saya yang Sedang Ditangani
$ticket_saya = $conn->query("
    SELECT lm.*, k.kode_komputer, k.nama_komputer, k.lokasi, k.divisi, u.nama_lengkap as nama_karyawan
    FROM laporan_masalah lm
    JOIN komputer k ON lm.komputer_id = k.komputer_id
    JOIN users u ON lm.karyawan_id = u.user_id
    WHERE lm.siswa_pkl_id = $user_id AND lm.status_laporan = 'ditindaklanjuti'
    ORDER BY lm.tanggal_laporan DESC
");

// Get Riwayat Ticket yang Sudah Selesai
$ticket_selesai = $conn->query("
    SELECT lm.*, k.kode_komputer, k.nama_komputer, u.nama_lengkap as nama_karyawan
    FROM laporan_masalah lm
    JOIN komputer k ON lm.komputer_id = k.komputer_id
    JOIN users u ON lm.karyawan_id = u.user_id
    WHERE lm.siswa_pkl_id = $user_id AND lm.status_laporan = 'selesai'
    ORDER BY lm.tanggal_selesai DESC
    LIMIT 10
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tindak Lanjut - PM Kayaba</title>
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
        .ticket-card {
            border-left: 4px solid #ddd;
            transition: all 0.3s;
        }
        .ticket-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .ticket-card.urgent-kritis {
            border-left-color: #dc3545;
        }
        .ticket-card.urgent-tinggi {
            border-left-color: #fd7e14;
        }
        .urgent-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    @media print { .no-print{display:none!important} .sidebar{display:none} .main-content{margin-left:0} @page { size: A4; margin: 15mm; } .table-bordered > :not(caption) > * > * { border-color: #000 !important; } }
    @media print { .no-print{display:none!important} .sidebar{display:none} .main-content{margin-left:0} @page { size: A4; margin: 15mm; } .table-bordered > :not(caption) > * > * { border-color: #000 !important; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4>
            <small>Siswa PKL Dashboard</small>
        </div>
        
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="index.php">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="form_pm.php">
                <i class="fas fa-clipboard-check me-2"></i>Input PM
            </a>
            <a class="nav-link" href="riwayat_pm.php">
                <i class="fas fa-history me-2"></i>Riwayat PM
            </a>
            <a class="nav-link active" href="tindak_lanjut.php">
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="mb-4">
            <h2><i class="fas fa-tasks me-2"></i>Tindak Lanjut Laporan Masalah</h2>
            <p class="text-muted">Kelola dan selesaikan laporan masalah dari karyawan</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="mb-3 no-print">
            <button type="button" class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print / PDF
            </button>
        </div>
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#baru">
                    <i class="fas fa-inbox me-2"></i>Laporan Baru 
                    <span class="badge bg-danger"><?php echo $laporan_baru->num_rows; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#dikerjakan">
                    <i class="fas fa-wrench me-2"></i>Sedang Dikerjakan
                    <span class="badge bg-info"><?php echo $ticket_saya->num_rows; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#selesai">
                    <i class="fas fa-check-circle me-2"></i>Selesai
                </a>
            </li>
        </ul>

   <!-- Print Header -->
    <div class="d-none d-print-block mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <img src="../assets/logo_kyb.png" alt="KYB" style="height:48px;">
            <div class="text-end">
                <div class="small">Tanggal Export:</div>
                <div class="fw-bold"><?php echo date('d/m/Y H:i'); ?></div>
            </div>
        </div>
        <div class="text-center mt-2">
            <h5 class="mb-0">TINDAK LANJUT LAPORAN</h5>
            <small>Teknisi: <?php echo $_SESSION['nama_lengkap']; ?></small>
        </div>
        <hr>
    </div>

<!-- Print Header -->
<div class="d-none d-print-block mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <img src="../assets/logo_kyb.png" alt="KYB" style="height:48px;">
        <div class="text-end">
            <div class="small">Tanggal Export:</div>
            <div class="fw-bold"><?php echo date('d/m/Y H:i'); ?></div>
        </div>
    </div>
    <div class="text-center mt-2">
        <h5 class="mb-0">TINDAK LANJUT LAPORAN</h5>
        <small>Teknisi: <?php echo $_SESSION['nama_lengkap']; ?></small>
    </div>
    <hr>
</div>

<!-- Tab Content -->
        <div class="tab-content">
            <!-- Laporan Baru -->
            <div class="tab-pane fade show active" id="baru">
                <?php if ($laporan_baru->num_rows > 0): ?>
                    <div class="row g-3">
                        <?php while($lap = $laporan_baru->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card ticket-card urgent-<?php echo $lap['tingkat_urgensi']; ?> shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><?php echo $lap['judul_masalah']; ?></h5>
                                            <small class="text-muted">
                                                <i class="far fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($lap['tanggal_laporan'])); ?>
                                            </small>
                                        </div>
                                        <?php
                                        $urgensi_badges = [
                                            'kritis' => '<span class="badge bg-danger urgent-badge">KRITIS</span>',
                                            'tinggi' => '<span class="badge bg-warning text-dark">TINGGI</span>',
                                            'sedang' => '<span class="badge bg-info">SEDANG</span>',
                                            'rendah' => '<span class="badge bg-success">RENDAH</span>'
                                        ];
                                        echo $urgensi_badges[$lap['tingkat_urgensi']];
                                        ?>
                                    </div>
                                    
                                    <p class="mb-2"><?php echo substr($lap['deskripsi_masalah'], 0, 100) . '...'; ?></p>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-secondary me-1">
                                            <i class="fas fa-desktop me-1"></i><?php echo $lap['kode_komputer']; ?>
                                        </span>
                                        <span class="badge bg-dark">
                                            <i class="fas fa-user me-1"></i><?php echo $lap['nama_karyawan']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <div class="flex-grow-1">
                                            <button type="button" class="btn btn-secondary w-100" disabled>
                                                Menunggu penugasan admin
                                            </button>
                                        </div>
                                        <button class="btn btn-info" onclick="viewDetailLaporan(<?php echo htmlspecialchars(json_encode($lap)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <p class="mb-0">Tidak ada laporan baru</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sedang Dikerjakan -->
            <div class="tab-pane fade" id="dikerjakan">
                <?php if ($ticket_saya->num_rows > 0): ?>
                    <div class="row g-3">
                        <?php while($tick = $ticket_saya->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="mb-2"><?php echo $tick['judul_masalah']; ?></h5>
                                    <p class="mb-2"><?php echo substr($tick['deskripsi_masalah'], 0, 100) . '...'; ?></p>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-secondary me-1">
                                            <i class="fas fa-desktop me-1"></i><?php echo $tick['kode_komputer']; ?>
                                        </span>
                                        <span class="badge bg-info">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($tick['tanggal_ditangani'])); ?>
                                        </span>
                                    </div>
                                    
                                    <button class="btn btn-primary w-100" onclick="showSelesaikanModal(<?php echo htmlspecialchars(json_encode($tick)); ?>)">
                                        <i class="fas fa-check me-1"></i>Selesaikan Ticket
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-tasks fa-3x mb-3"></i>
                        <p class="mb-0">Tidak ada ticket yang sedang dikerjakan</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Selesai -->
            <div class="tab-pane fade" id="selesai">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Selesai</th>
                                <th>Komputer</th>
                                <th>Judul Masalah</th>
                                <th>Solusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ticket_selesai->num_rows > 0): ?>
                                <?php while($selesai = $ticket_selesai->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($selesai['tanggal_selesai'])); ?></td>
                                    <td><strong><?php echo $selesai['kode_komputer']; ?></strong></td>
                                    <td><?php echo $selesai['judul_masalah']; ?></td>
                                    <td><small><?php echo substr($selesai['solusi'], 0, 50) . '...'; ?></small></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada ticket yang diselesaikan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Laporan -->
    <div class="modal fade" id="detailLaporanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailLaporanContent"></div>
            </div>
        </div>
    </div>

    <!-- Modal Selesaikan Ticket -->
    <div class="modal fade" id="selesaikanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selesaikan Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="laporan_id" id="selesai_laporan_id">
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Solusi <span class="text-danger">*</span></strong></label>
                            <textarea name="solusi" class="form-control" rows="4" placeholder="Jelaskan solusi yang telah dilakukan..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Catatan Teknisi</strong></label>
                            <textarea name="catatan_teknisi" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="selesaikan_ticket" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Selesaikan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetailLaporan(lap) {
            const urgensiMap = {
                'kritis': '<span class="badge bg-danger">KRITIS</span>',
                'tinggi': '<span class="badge bg-warning text-dark">TINGGI</span>',
                'sedang': '<span class="badge bg-info">SEDANG</span>',
                'rendah': '<span class="badge bg-success">RENDAH</span>'
            };
            
            const content = `
                <h6>Informasi Laporan</h6>
                <table class="table table-sm">
                    <tr><th width="30%">Judul</th><td><strong>${lap.judul_masalah}</strong></td></tr>
                    <tr><th>Kategori</th><td><span class="badge bg-dark">${lap.kategori}</span></td></tr>
                    <tr><th>Urgensi</th><td>${urgensiMap[lap.tingkat_urgensi]}</td></tr>
                    <tr><th>Tanggal Laporan</th><td>${new Date(lap.tanggal_laporan).toLocaleString('id-ID')}</td></tr>
                </table>
                
                <h6>Informasi Komputer</h6>
                <table class="table table-sm">
                    <tr><th width="30%">Kode</th><td>${lap.kode_komputer}</td></tr>
                    <tr><th>Nama</th><td>${lap.nama_komputer}</td></tr>
                    <tr><th>Lokasi</th><td>${lap.lokasi}</td></tr>
                    <tr><th>Divisi</th><td>${lap.divisi}</td></tr>
                    <tr><th>Pelapor</th><td>${lap.nama_karyawan}</td></tr>
                </table>
                
                <h6>Deskripsi Masalah</h6>
                <div class="alert alert-light">${lap.deskripsi_masalah}</div>
                
                ${lap.foto_masalah ? `
                    <h6>Foto Masalah</h6>
                    <img src="../assets/uploads/${lap.foto_masalah}" class="img-fluid" alt="Foto Masalah">
                ` : ''}
            `;
            
            document.getElementById('detailLaporanContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailLaporanModal')).show();
        }
        
        function showSelesaikanModal(ticket) {
            document.getElementById('selesai_laporan_id').value = ticket.laporan_id;
            new bootstrap.Modal(document.getElementById('selesaikanModal')).show();
        }
    </script>
</body>
</html>