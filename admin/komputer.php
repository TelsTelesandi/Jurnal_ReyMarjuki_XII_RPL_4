<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['admin']);

$conn = connectDB();
$message = '';
$message_type = '';

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $komputer_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM komputer WHERE komputer_id = ?");
    $stmt->bind_param("i", $komputer_id);
    if ($stmt->execute()) {
        $message = 'Data komputer berhasil dihapus!';
        $message_type = 'success';
    } else {
        $message = 'Gagal menghapus data komputer!';
        $message_type = 'danger';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $komputer_id = $_POST['komputer_id'] ?? null;
    $kode_komputer = escapeString($_POST['kode_komputer']);
    $nama_komputer = escapeString($_POST['nama_komputer']);
    $lokasi = escapeString($_POST['lokasi']);
    $divisi = escapeString($_POST['divisi']);
    $user_id = $_POST['user_id'] ? intval($_POST['user_id']) : null;
    $jenis_komputer = escapeString($_POST['jenis_komputer']);
    $spesifikasi = escapeString($_POST['spesifikasi']);
    $tanggal_pembelian = $_POST['tanggal_pembelian'];
    $status_komputer = escapeString($_POST['status_komputer']);
    
    if ($komputer_id) {
        $stmt = $conn->prepare("UPDATE komputer SET kode_komputer=?, nama_komputer=?, lokasi=?, divisi=?, user_id=?, jenis_komputer=?, spesifikasi=?, tanggal_pembelian=?, status_komputer=? WHERE komputer_id=?");
        $stmt->bind_param("ssssissssi", $kode_komputer, $nama_komputer, $lokasi, $divisi, $user_id, $jenis_komputer, $spesifikasi, $tanggal_pembelian, $status_komputer, $komputer_id);
        if ($stmt->execute()) {
            $message = 'Data komputer berhasil diupdate!';
            $message_type = 'success';
        } else {
            $message = 'Gagal update data komputer!';
            $message_type = 'danger';
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO komputer (kode_komputer, nama_komputer, lokasi, divisi, user_id, jenis_komputer, spesifikasi, tanggal_pembelian, status_komputer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssissss", $kode_komputer, $nama_komputer, $lokasi, $divisi, $user_id, $jenis_komputer, $spesifikasi, $tanggal_pembelian, $status_komputer);
        if ($stmt->execute()) {
            $message = 'Data komputer berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambah data komputer! Kode komputer mungkin sudah digunakan.';
            $message_type = 'danger';
        }
    }
    $stmt->close();
}

$komputer = $conn->query("SELECT k.*, u.nama_lengkap as nama_user FROM komputer k LEFT JOIN users u ON k.user_id = u.user_id ORDER BY k.created_at DESC");
$karyawan = $conn->query("SELECT user_id, nama_lengkap, divisi FROM users WHERE role = 'karyawan' AND status = 'aktif' ORDER BY nama_lengkap");
$lokasi_list = $conn->query("SELECT * FROM master_lokasi ORDER BY nama_lokasi");
$divisi_list = $conn->query("SELECT * FROM master_divisi ORDER BY nama_divisi");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Komputer - PM Kayaba</title>
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
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-desktop me-2"></i>Data Komputer</h2>
                <p class="text-muted">Kelola data komputer perusahaan</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#komputerModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Tambah Komputer
            </button>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Komputer</th>
                                <th>Lokasi</th>
                                <th>Divisi</th>
                                <th>User</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($komputer->num_rows > 0): ?>
                                <?php while($pc = $komputer->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $pc['kode_komputer']; ?></strong></td>
                                    <td><?php echo $pc['nama_komputer']; ?></td>
                                    <td><small><?php echo $pc['lokasi']; ?></small></td>
                                    <td><span class="badge bg-secondary"><?php echo $pc['divisi']; ?></span></td>
                                    <td><?php echo $pc['nama_user'] ?: '-'; ?></td>
                                    <td><?php echo $pc['jenis_komputer']; ?></td>
                                    <td><?php echo getStatusBadge($pc['status_komputer']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewDetail(<?php echo htmlspecialchars(json_encode($pc)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="editKomputer(<?php echo htmlspecialchars(json_encode($pc)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=1&id=<?php echo $pc['komputer_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin ingin menghapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada data komputer</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="komputerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-desktop me-2"></i>Tambah Komputer
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="komputer_id" id="komputer_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode Komputer <span class="text-danger">*</span></label>
                                <input type="text" name="kode_komputer" id="kode_komputer" class="form-control" placeholder="PC-001" required>
                            </div>                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Komputer <span class="text-danger">*</span></label>
                                <input type="text" name="nama_komputer" id="nama_komputer" class="form-control" placeholder="Komputer Produksi 1" required>
                            </div>
                        </div>                       
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                                <select name="lokasi" id="lokasi" class="form-select" required>
                                    <option value="">Pilih Lokasi</option>
                                    <?php
                                    $lokasi_list->data_seek(0);
                                    while($lok = $lokasi_list->fetch_assoc()): ?>
                                        <option value="<?php echo $lok['nama_lokasi']; ?>"><?php echo $lok['nama_lokasi']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>                           
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Divisi <span class="text-danger">*</span></label>
                                <select name="divisi" id="divisi" class="form-select" required>
                                    <option value="">Pilih Divisi</option>
                                    <?php
                                    $divisi_list->data_seek(0);
                                    while($div = $divisi_list->fetch_assoc()): ?>
                                        <option value="<?php echo $div['nama_divisi']; ?>"><?php echo $div['nama_divisi']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>                       
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">User/Karyawan</label>
                                <select name="user_id" id="user_id" class="form-select">
                                    <option value="">Pilih Karyawan</option>
                                    <?php
                                    $karyawan->data_seek(0);
                                    while($kar = $karyawan->fetch_assoc()): ?>
                                        <option value="<?php echo $kar['user_id']; ?>">
                                            <?php echo $kar['nama_lengkap'] . ' (' . $kar['divisi'] . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>                           
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Komputer <span class="text-danger">*</span></label>
                                <select name="jenis_komputer" id="jenis_komputer" class="form-select" required>
                                    <option value="PC">PC</option>
                                    <option value="Laptop">Laptop</option>
                                    <option value="AIO">AIO (All-in-One)</option>
                                </select>
                            </div>
                        </div>                    
                        <div class="mb-3">
                            <label class="form-label">Spesifikasi</label>
                            <textarea name="spesifikasi" id="spesifikasi" class="form-control" rows="3" placeholder="Intel Core i5, 8GB RAM, 256GB SSD"></textarea>
                        </div>             
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Pembelian</label>
                                <input type="date" name="tanggal_pembelian" id="tanggal_pembelian" class="form-control">
                            </div> 
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status_komputer" id="status_komputer" class="form-select" required>
                                    <option value="baik">Baik</option>
                                    <option value="perlu_perbaikan">Perlu Perbaikan</option>
                                    <option value="rusak">Rusak</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Komputer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('komputer_id').value = '';
            document.getElementById('kode_komputer').value = '';
            document.getElementById('nama_komputer').value = '';
            document.getElementById('lokasi').value = '';
            document.getElementById('divisi').value = '';
            document.getElementById('user_id').value = '';
            document.getElementById('jenis_komputer').value = 'PC';
            document.getElementById('spesifikasi').value = '';
            document.getElementById('tanggal_pembelian').value = '';
            document.getElementById('status_komputer').value = 'baik';
            
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-desktop me-2"></i>Tambah Komputer';
        }
        
        function editKomputer(pc) {
            document.getElementById('komputer_id').value = pc.komputer_id;
            document.getElementById('kode_komputer').value = pc.kode_komputer;
            document.getElementById('nama_komputer').value = pc.nama_komputer;
            document.getElementById('lokasi').value = pc.lokasi;
            document.getElementById('divisi').value = pc.divisi;
            document.getElementById('user_id').value = pc.user_id || '';
            document.getElementById('jenis_komputer').value = pc.jenis_komputer;
            document.getElementById('spesifikasi').value = pc.spesifikasi || '';
            document.getElementById('tanggal_pembelian').value = pc.tanggal_pembelian || '';
            document.getElementById('status_komputer').value = pc.status_komputer;
            
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Komputer';
            
            new bootstrap.Modal(document.getElementById('komputerModal')).show();
        }
        
        function viewDetail(pc) {
            let statusBadge = '';
            if (pc.status_komputer === 'baik') {
                statusBadge = '<span class="badge bg-success">Baik</span>';
            } else if (pc.status_komputer === 'perlu_perbaikan') {
                statusBadge = '<span class="badge bg-warning">Perlu Perbaikan</span>';
            } else {
                statusBadge = '<span class="badge bg-danger">Rusak</span>';
            }
            
            const content = `
                <table class="table">
                    <tr>
                        <th width="40%">Kode Komputer</th>
                        <td><strong>${pc.kode_komputer}</strong></td>
                    </tr>
                    <tr>
                        <th>Nama Komputer</th>
                        <td>${pc.nama_komputer}</td>
                    </tr>
                    <tr>
                        <th>Lokasi</th>
                        <td>${pc.lokasi}</td>
                    </tr>
                    <tr>
                        <th>Divisi</th>
                        <td>${pc.divisi}</td>
                    </tr>
                    <tr>
                        <th>User/Karyawan</th>
                        <td>${pc.nama_user || '-'}</td>
                    </tr>
                    <tr>
                        <th>Jenis</th>
                        <td>${pc.jenis_komputer}</td>
                    </tr>
                    <tr>
                        <th>Spesifikasi</th>
                        <td>${pc.spesifikasi || '-'}</td>
                    </tr>
                    <tr>
                        <th>Tanggal Pembelian</th>
                        <td>${pc.tanggal_pembelian || '-'}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>${statusBadge}</td>
                    </tr>
                </table>
            `;
            
            document.getElementById('detailContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }
    </script>
</body>
</html>