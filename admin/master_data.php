<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['admin']);

$conn = connectDB();
$message = '';
$message_type = '';

if (isset($_POST['action_lokasi'])) {
    $action = $_POST['action_lokasi'];
    if ($action == 'add' || $action == 'edit') {
        $lokasi_id = $_POST['lokasi_id'] ?? null;
        $nama_lokasi = escapeString($_POST['nama_lokasi']);
        $keterangan = escapeString($_POST['keterangan']);
        if ($lokasi_id) {
            $stmt = $conn->prepare("UPDATE master_lokasi SET nama_lokasi=?, keterangan=? WHERE lokasi_id=?");
            $stmt->bind_param("ssi", $nama_lokasi, $keterangan, $lokasi_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO master_lokasi (nama_lokasi, keterangan) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_lokasi, $keterangan);
        }
        if ($stmt->execute()) {
            $message = 'Data lokasi berhasil disimpan!';
            $message_type = 'success';
        }
        $stmt->close();
    } elseif ($action == 'delete') {
        $lokasi_id = intval($_POST['lokasi_id']);
        $stmt = $conn->prepare("DELETE FROM master_lokasi WHERE lokasi_id = ?");
        $stmt->bind_param("i", $lokasi_id);
        if ($stmt->execute()) {
            $message = 'Data lokasi berhasil dihapus!';
            $message_type = 'success';
        }
        $stmt->close();
    }
}

if (isset($_POST['action_divisi'])) {
    $action = $_POST['action_divisi'];
    if ($action == 'add' || $action == 'edit') {
        $divisi_id = $_POST['divisi_id'] ?? null;
        $nama_divisi = escapeString($_POST['nama_divisi']);
        $keterangan = escapeString($_POST['keterangan_divisi']);
        if ($divisi_id) {
            $stmt = $conn->prepare("UPDATE master_divisi SET nama_divisi=?, keterangan=? WHERE divisi_id=?");
            $stmt->bind_param("ssi", $nama_divisi, $keterangan, $divisi_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO master_divisi (nama_divisi, keterangan) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_divisi, $keterangan);
        }
        if ($stmt->execute()) {
            $message = 'Data divisi berhasil disimpan!';
            $message_type = 'success';
        }
        $stmt->close();
    } elseif ($action == 'delete') {
        $divisi_id = intval($_POST['divisi_id']);
        $stmt = $conn->prepare("DELETE FROM master_divisi WHERE divisi_id = ?");
        $stmt->bind_param("i", $divisi_id);
        if ($stmt->execute()) {
            $message = 'Data divisi berhasil dihapus!';
            $message_type = 'success';
        }
        $stmt->close();
    }
}

$lokasi_list = $conn->query("SELECT * FROM master_lokasi ORDER BY nama_lokasi");
$divisi_list = $conn->query("SELECT * FROM master_divisi ORDER BY nama_divisi");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data - PM Kayaba</title>
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
    <!-- Main Content -->
    <div class="main-content">
        <div class="mb-4">
            <h2><i class="fas fa-database me-2"></i>Master Data</h2>
            <p class="text-muted">Kelola data master lokasi dan divisi</p>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row g-4">
            <!-- Master Lokasi -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Master Lokasi</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#lokasiModal" onclick="resetLokasiForm()">
                            <i class="fas fa-plus me-1"></i>Tambah
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Lokasi</th>
                                        <th>Keterangan</th>
                                        <th width="100">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($lokasi_list->num_rows > 0): ?>
                                        <?php while($lok = $lokasi_list->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo $lok['nama_lokasi']; ?></strong></td>
                                            <td><small><?php echo $lok['keterangan']; ?></small></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editLokasi(<?php echo htmlspecialchars(json_encode($lok)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                                    <input type="hidden" name="action_lokasi" value="delete">
                                                    <input type="hidden" name="lokasi_id" value="<?php echo $lok['lokasi_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Belum ada data lokasi</td>
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
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Master Divisi</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#divisiModal" onclick="resetDivisiForm()">
                            <i class="fas fa-plus me-1"></i>Tambah
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Divisi</th>
                                        <th>Keterangan</th>
                                        <th width="100">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($divisi_list->num_rows > 0): ?>
                                        <?php while($div = $divisi_list->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo $div['nama_divisi']; ?></strong></td>
                                            <td><small><?php echo $div['keterangan']; ?></small></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editDivisi(<?php echo htmlspecialchars(json_encode($div)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                                    <input type="hidden" name="action_divisi" value="delete">
                                                    <input type="hidden" name="divisi_id" value="<?php echo $div['divisi_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Belum ada data divisi</td>
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
    <div class="modal fade" id="lokasiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lokasiModalTitle">Tambah Lokasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action_lokasi" id="action_lokasi" value="add">
                        <input type="hidden" name="lokasi_id" id="lokasi_id">                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lokasi" id="nama_lokasi" class="form-control" placeholder="Contoh: Gedung A - Lantai 1" required>
                        </div>                       
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Deskripsi lokasi"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="divisiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="divisiModalTitle">Tambah Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action_divisi" id="action_divisi" value="add">
                        <input type="hidden" name="divisi_id" id="divisi_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Divisi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_divisi" id="nama_divisi" class="form-control" placeholder="Contoh: IT, HRD, Production" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan_divisi" id="keterangan_divisi" class="form-control" rows="3" placeholder="Deskripsi divisi"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Lokasi Functions
        function resetLokasiForm() {
            document.getElementById('action_lokasi').value = 'add';
            document.getElementById('lokasi_id').value = '';
            document.getElementById('nama_lokasi').value = '';
            document.getElementById('keterangan').value = '';
            document.getElementById('lokasiModalTitle').textContent = 'Tambah Lokasi';
        }
        
        function editLokasi(lokasi) {
            document.getElementById('action_lokasi').value = 'edit';
            document.getElementById('lokasi_id').value = lokasi.lokasi_id;
            document.getElementById('nama_lokasi').value = lokasi.nama_lokasi;
            document.getElementById('keterangan').value = lokasi.keterangan || '';
            document.getElementById('lokasiModalTitle').textContent = 'Edit Lokasi';
            new bootstrap.Modal(document.getElementById('lokasiModal')).show();
        }
        
        // Divisi Functions
        function resetDivisiForm() {
            document.getElementById('action_divisi').value = 'add';
            document.getElementById('divisi_id').value = '';
            document.getElementById('nama_divisi').value = '';
            document.getElementById('keterangan_divisi').value = '';
            document.getElementById('divisiModalTitle').textContent = 'Tambah Divisi';
        }
        
        function editDivisi(divisi) {
            document.getElementById('action_divisi').value = 'edit';
            document.getElementById('divisi_id').value = divisi.divisi_id;
            document.getElementById('nama_divisi').value = divisi.nama_divisi;
            document.getElementById('keterangan_divisi').value = divisi.keterangan || '';
            document.getElementById('divisiModalTitle').textContent = 'Edit Divisi';
            new bootstrap.Modal(document.getElementById('divisiModal')).show();
        }
    </script>
</body>
</html>