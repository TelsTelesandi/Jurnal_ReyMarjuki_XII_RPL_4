<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['admin']);

$conn = connectDB();
$assign_message = '';
$assign_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teknisi'])) {
    $laporan_id = intval($_POST['laporan_id']);
    $teknisi_id = intval($_POST['teknisi_id']);
    $cek = $conn->prepare("SELECT COUNT(*) AS total FROM laporan_masalah WHERE siswa_pkl_id = ? AND status_laporan = 'ditindaklanjuti'");
    $cek->bind_param("i", $teknisi_id);
    $cek->execute();
    $result = $cek->get_result();
    $row = $result->fetch_assoc();
    $cek->close();
    if ($row['total'] > 0) {
        $assign_message = 'Teknisi masih memiliki ticket yang sedang dikerjakan.';
        $assign_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE laporan_masalah SET siswa_pkl_id = ?, status_laporan = 'ditindaklanjuti', tanggal_ditangani = NOW() WHERE laporan_id = ?");
        $stmt->bind_param("ii", $teknisi_id, $laporan_id);
        if ($stmt->execute()) {
            $assign_message = 'Ticket berhasil ditugaskan ke teknisi.';
            $assign_type = 'success';
        } else {
            $assign_message = 'Gagal menugaskan ticket.';
            $assign_type = 'danger';
        }
        $stmt->close();
    }
}

$teknisi_list = $conn->query("SELECT u.user_id, u.nama_lengkap FROM users u WHERE u.role = 'siswa_pkl' AND u.status = 'aktif' AND NOT EXISTS (SELECT 1 FROM laporan_masalah lm WHERE lm.siswa_pkl_id = u.user_id AND lm.status_laporan = 'ditindaklanjuti') ORDER BY u.nama_lengkap");
$filter_status = $_GET['status'] ?? '';
$filter_urgensi = $_GET['urgensi'] ?? '';
$where = "WHERE 1=1";
if ($filter_status) $where .= " AND lm.status_laporan = '" . escapeString($filter_status) . "'";
if ($filter_urgensi) $where .= " AND lm.tingkat_urgensi = '" . escapeString($filter_urgensi) . "'";

$ticket_list = $conn->query("SELECT lm.*, k.kode_komputer, k.nama_komputer, k.lokasi, k.divisi, u.nama_lengkap as nama_karyawan, s.nama_lengkap as nama_teknisi FROM laporan_masalah lm JOIN komputer k ON lm.komputer_id = k.komputer_id JOIN users u ON lm.karyawan_id = u.user_id LEFT JOIN users s ON lm.siswa_pkl_id = s.user_id $where ORDER BY lm.tanggal_laporan DESC");

$stats = [];
$result = $conn->query("SELECT status_laporan, COUNT(*) as total FROM laporan_masalah GROUP BY status_laporan");
while ($row = $result->fetch_assoc()) $stats[$row['status_laporan']] = $row['total'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Ticket - PM Kayaba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        body { background-color: #f8f9fa; }
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
        .urgency-badge-kritis { background: linear-gradient(135deg, #f5576c 0%, #c51d34 100%); }
        .urgency-badge-tinggi { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .urgency-badge-sedang { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .urgency-badge-rendah { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
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
            <h2><i class="fas fa-ticket-alt me-2"></i>Monitoring Ticket Masalah</h2>
            <p class="text-muted">Pantau laporan masalah dari karyawan</p>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card-mini bg-secondary">
                    <h6 class="mb-0">Menunggu</h6>
                    <h3 class="mb-0"><?php echo $stats['menunggu'] ?? 0; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-mini bg-info">
                    <h6 class="mb-0">Ditindaklanjuti</h6>
                    <h3 class="mb-0"><?php echo $stats['ditindaklanjuti'] ?? 0; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-mini bg-success">
                    <h6 class="mb-0">Selesai</h6>
                    <h3 class="mb-0"><?php echo $stats['selesai'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Filter Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?php echo $filter_status == 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="ditindaklanjuti" <?php echo $filter_status == 'ditindaklanjuti' ? 'selected' : ''; ?>>Ditindaklanjuti</option>
                            <option value="selesai" <?php echo $filter_status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Filter Urgensi</label>
                        <select name="urgensi" class="form-select">
                            <option value="">Semua Urgensi</option>
                            <option value="kritis" <?php echo $filter_urgensi == 'kritis' ? 'selected' : ''; ?>>Kritis</option>
                            <option value="tinggi" <?php echo $filter_urgensi == 'tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                            <option value="sedang" <?php echo $filter_urgensi == 'sedang' ? 'selected' : ''; ?>>Sedang</option>
                            <option value="rendah" <?php echo $filter_urgensi == 'rendah' ? 'selected' : ''; ?>>Rendah</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php if (!empty($assign_message)): ?>
        <div class="alert alert-<?php echo $assign_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $assign_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Komputer</th>
                                <th>Judul Masalah</th>
                                <th>Pelapor</th>
                                <th>Urgensi</th>
                                <th>Status</th>
                                <th>Teknisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ticket_list->num_rows > 0): ?>
                                <?php while($ticket = $ticket_list->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($ticket['tanggal_laporan'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($ticket['tanggal_laporan'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $ticket['kode_komputer']; ?></strong><br>
                                        <small><?php echo $ticket['lokasi']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $ticket['judul_masalah']; ?></strong><br>
                                        <small class="text-muted"><?php echo substr($ticket['deskripsi_masalah'], 0, 50); ?>...</small>
                                    </td>
                                    <td><?php echo $ticket['nama_karyawan']; ?></td>
                                    <td>
                                        <?php
                                        $urgency_class = [
                                            'kritis' => 'danger',
                                            'tinggi' => 'warning',
                                            'sedang' => 'info',
                                            'rendah' => 'secondary'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $urgency_class[$ticket['tingkat_urgensi']]; ?>">
                                            <?php echo ucfirst($ticket['tingkat_urgensi']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo getStatusBadge($ticket['status_laporan']); ?></td>
                                    <td><?php echo $ticket['nama_teknisi'] ?: '-'; ?></td>
                                    <td>
                                        <?php if ($ticket['status_laporan'] === 'menunggu'): ?>
                                            <form method="POST" class="d-flex gap-1">
                                                <input type="hidden" name="laporan_id" value="<?php echo $ticket['laporan_id']; ?>">
                                                <select name="teknisi_id" class="form-select form-select-sm" required>
                                                    <option value="">Pilih Teknisi</option>
                                                    <?php $teknisi_list->data_seek(0); while ($tek = $teknisi_list->fetch_assoc()): ?>
                                                        <option value="<?php echo $tek['user_id']; ?>"><?php echo $tek['nama_lengkap']; ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <button type="submit" name="assign_teknisi" class="btn btn-sm btn-success">Assign</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-info" onclick="viewDetailTicket(<?php echo htmlspecialchars(json_encode($ticket)); ?>)">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada laporan masalah</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="detailTicketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ticket-alt me-2"></i>Detail Laporan Masalah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailTicketContent"></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetailTicket(ticket) {
            const statusMap = {
                'menunggu': '<span class="badge bg-secondary">Menunggu</span>',
                'ditindaklanjuti': '<span class="badge bg-info">Ditindaklanjuti</span>',
                'selesai': '<span class="badge bg-success">Selesai</span>'
            };
            
            const urgencyMap = {
                'kritis': '<span class="badge bg-danger">Kritis</span>',
                'tinggi': '<span class="badge bg-warning">Tinggi</span>',
                'sedang': '<span class="badge bg-info">Sedang</span>',
                'rendah': '<span class="badge bg-secondary">Rendah</span>'
            };
            
            let timeline = `
                <div class="mb-3">
                    <i class="fas fa-clock text-muted"></i> <strong>Dilaporkan:</strong> ${new Date(ticket.tanggal_laporan).toLocaleString('id-ID')}
                </div>
            `;
            
            if (ticket.tanggal_ditangani) {
                timeline += `
                    <div class="mb-3">
                        <i class="fas fa-tools text-info"></i> <strong>Ditangani:</strong> ${new Date(ticket.tanggal_ditangani).toLocaleString('id-ID')}
                    </div>
                `;
            }
            
            if (ticket.tanggal_selesai) {
                timeline += `
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success"></i> <strong>Selesai:</strong> ${new Date(ticket.tanggal_selesai).toLocaleString('id-ID')}
                    </div>
                `;
            }
            
            const content = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informasi Komputer</h6>
                        <table class="table table-sm">
                            <tr><th width="40%">Kode</th><td>${ticket.kode_komputer}</td></tr>
                            <tr><th>Nama</th><td>${ticket.nama_komputer}</td></tr>
                            <tr><th>Lokasi</th><td>${ticket.lokasi}</td></tr>
                            <tr><th>Divisi</th><td>${ticket.divisi}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Laporan</h6>
                        <table class="table table-sm">
                            <tr><th width="40%">Pelapor</th><td>${ticket.nama_karyawan}</td></tr>
                            <tr><th>Teknisi</th><td>${ticket.nama_teknisi || '-'}</td></tr>
                            <tr><th>Status</th><td>${statusMap[ticket.status_laporan]}</td></tr>
                            <tr><th>Urgensi</th><td>${urgencyMap[ticket.tingkat_urgensi]}</td></tr>
                        </table>
                    </div>
                </div>
                
                <h6>Judul Masalah</h6>
                <div class="alert alert-light">${ticket.judul_masalah}</div>
                
                <h6>Deskripsi Masalah</h6>
                <div class="alert alert-light">${ticket.deskripsi_masalah}</div>
                
                <h6>Kategori</h6>
                <span class="badge bg-primary">${ticket.kategori.toUpperCase()}</span>
                
                ${ticket.foto_masalah ? `
                    <h6 class="mt-3">Foto Masalah</h6>
                    <img src="../assets/uploads/${ticket.foto_masalah}" class="img-fluid rounded" alt="Foto Masalah">
                ` : ''}
                
                ${ticket.solusi ? `
                    <h6 class="mt-3">Solusi</h6>
                    <div class="alert alert-success">${ticket.solusi}</div>
                ` : ''}
                
                ${ticket.catatan_teknisi ? `
                    <h6>Catatan Teknisi</h6>
                    <div class="alert alert-info">${ticket.catatan_teknisi}</div>
                ` : ''}
                
                <h6 class="mt-3">Timeline</h6>
                ${timeline}
            `;
            
            document.getElementById('detailTicketContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailTicketModal')).show();
        }
    </script>
</body>
</html>