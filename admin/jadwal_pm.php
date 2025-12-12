<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
checkRole(['admin']);

$conn = connectDB();
$message = '';
$message_type = '';

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $jadwal_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM jadwal_pm WHERE jadwal_id = ?");
    $stmt->bind_param("i", $jadwal_id);
    if ($stmt->execute()) {
        $message = 'Jadwal PM berhasil dihapus!';
        $message_type = 'success';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jadwal_id = $_POST['jadwal_id'] ?? null;
    $komputer_id = intval($_POST['komputer_id']);
    $siswa_pkl_id = !empty($_POST['siswa_pkl_id']) ? intval($_POST['siswa_pkl_id']) : null;
    $tanggal_jadwal = $_POST['tanggal_jadwal'];
    $periode = escapeString($_POST['periode']);
    $catatan = escapeString($_POST['catatan']);
    
    if ($jadwal_id) {
        $stmt = $conn->prepare("UPDATE jadwal_pm SET komputer_id=?, siswa_pkl_id=?, tanggal_jadwal=?, periode=?, catatan=? WHERE jadwal_id=?");
        $stmt->bind_param("iisssi", $komputer_id, $siswa_pkl_id, $tanggal_jadwal, $periode, $catatan, $jadwal_id);
        if ($stmt->execute()) {
            $message = 'Jadwal PM berhasil diupdate!';
            $message_type = 'success';
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO jadwal_pm (komputer_id, siswa_pkl_id, tanggal_jadwal, periode, catatan, status_jadwal) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iisss", $komputer_id, $siswa_pkl_id, $tanggal_jadwal, $periode, $catatan);
        if ($stmt->execute()) {
            $message = 'Jadwal PM berhasil ditambahkan!';
            $message_type = 'success';
        }
    }
    $stmt->close();
}

$jadwal_list = $conn->query("SELECT jp.*, k.kode_komputer, k.nama_komputer, k.lokasi, u.nama_lengkap as nama_teknisi FROM jadwal_pm jp JOIN komputer k ON jp.komputer_id = k.komputer_id LEFT JOIN users u ON jp.siswa_pkl_id = u.user_id ORDER BY jp.tanggal_jadwal DESC");
$komputer_list = $conn->query("SELECT * FROM komputer ORDER BY kode_komputer");
$siswa_pkl_list = $conn->query("SELECT * FROM users WHERE role = 'siswa_pkl' AND status = 'aktif' ORDER BY nama_lengkap");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Jadwal PM - PM Kayaba</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>:root{--primary-color:#667eea;--secondary-color:#764ba2}body{background-color:#f8f9fa}.sidebar{min-height:100vh;background:linear-gradient(135deg,var(--primary-color) 0%,var(--secondary-color) 100%);color:white;position:fixed;width:250px;padding:0}.sidebar .nav-link{color:rgba(255,255,255,0.8);padding:12px 20px;transition:all 0.3s;border-left:3px solid transparent}.sidebar .nav-link:hover,.sidebar .nav-link.active{background-color:rgba(255,255,255,0.1);color:white;border-left-color:white}.main-content{margin-left:250px;padding:20px}.sidebar-header{padding:20px;border-bottom:1px solid rgba(255,255,255,0.1)}.user-info{padding:15px 20px;border-top:1px solid rgba(255,255,255,0.1);position:absolute;bottom:0;width:100%}</style>
</head>
<body>
<div class="sidebar"><div class="sidebar-header"><h4><i class="fas fa-tools me-2"></i>PM Kayaba</h4><small>Admin Dashboard</small></div>
<nav class="nav flex-column mt-3"><a class="nav-link" href="index.php"><i class="fas fa-home me-2"></i>Dashboard</a><a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i>Manajemen User</a><a class="nav-link" href="komputer.php"><i class="fas fa-desktop me-2"></i>Data Komputer</a><a class="nav-link active" href="jadwal_pm.php"><i class="fas fa-calendar-alt me-2"></i>Jadwal PM</a><a class="nav-link" href="monitoring_pm.php"><i class="fas fa-clipboard-check me-2"></i>Monitoring PM</a><a class="nav-link" href="monitoring_ticket.php"><i class="fas fa-ticket-alt me-2"></i>Monitoring Ticket</a><a class="nav-link" href="laporan.php"><i class="fas fa-chart-bar me-2"></i>Laporan</a><a class="nav-link" href="master_data.php"><i class="fas fa-database me-2"></i>Master Data</a></nav>
<div class="user-info"><div class="d-flex align-items-center"><div class="flex-grow-1"><div><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></div><small>Administrator</small></div><a href="../auth/logout.php" class="btn btn-sm btn-outline-light" title="Logout"><i class="fas fa-sign-out-alt"></i></a></div></div></div>
<div class="main-content"><div class="d-flex justify-content-between align-items-center mb-4"><div><h2><i class="fas fa-calendar-alt me-2"></i>Jadwal Preventive Maintenance</h2><p class="text-muted">Kelola jadwal PM untuk komputer</p></div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jadwalModal" onclick="resetForm()"><i class="fas fa-plus me-2"></i>Tambah Jadwal</button></div>
<?php if ($message): ?><div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert"><i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="card shadow-sm"><div class="card-body"><div class="table-responsive"><table class="table table-hover"><thead class="table-light"><tr><th>Tanggal Jadwal</th><th>Komputer</th><th>Lokasi</th><th>Teknisi</th><th>Periode</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if ($jadwal_list->num_rows > 0): while($jadwal = $jadwal_list->fetch_assoc()): ?>
<tr><td><strong><?php echo date('d/m/Y', strtotime($jadwal['tanggal_jadwal'])); ?></strong></td><td><strong><?php echo $jadwal['kode_komputer']; ?></strong><br><small><?php echo $jadwal['nama_komputer']; ?></small></td><td><small><?php echo $jadwal['lokasi']; ?></small></td><td><?php echo $jadwal['nama_teknisi'] ?: '<span class="text-muted">Belum ditugaskan</span>'; ?></td><td><span class="badge bg-info"><?php echo ucfirst($jadwal['periode']); ?></span></td><td><?php echo getStatusBadge($jadwal['status_jadwal']); ?></td><td><button class="btn btn-sm btn-warning" onclick="editJadwal(<?php echo htmlspecialchars(json_encode($jadwal)); ?>)"><i class="fas fa-edit"></i></button> <a href="?delete=1&id=<?php echo $jadwal['jadwal_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus jadwal ini?')"><i class="fas fa-trash"></i></a></td></tr>
<?php endwhile; else: ?>
<tr><td colspan="7" class="text-center text-muted py-4">Belum ada jadwal PM</td></tr>
<?php endif; ?>
</tbody></table></div></div></div></div>
<div class="modal fade" id="jadwalModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="modalTitle">Tambah Jadwal PM</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST"><div class="modal-body"><input type="hidden" name="jadwal_id" id="jadwal_id"><div class="row"><div class="col-md-6 mb-3"><label class="form-label">Komputer <span class="text-danger">*</span></label><select name="komputer_id" id="komputer_id" class="form-select" required><option value="">Pilih Komputer</option><?php $komputer_list->data_seek(0); while($pc = $komputer_list->fetch_assoc()): ?><option value="<?php echo $pc['komputer_id']; ?>"><?php echo $pc['kode_komputer']; ?> - <?php echo $pc['nama_komputer']; ?></option><?php endwhile; ?></select></div>
<div class="col-md-6 mb-3"><label class="form-label">Teknisi (Siswa PKL)</label><select name="siswa_pkl_id" id="siswa_pkl_id" class="form-select"><option value="">Belum ditugaskan</option><?php $siswa_pkl_list->data_seek(0); while($siswa = $siswa_pkl_list->fetch_assoc()): ?><option value="<?php echo $siswa['user_id']; ?>"><?php echo $siswa['nama_lengkap']; ?></option><?php endwhile; ?></select><small class="text-muted">Opsional, bisa ditugaskan nanti</small></div></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Tanggal Jadwal <span class="text-danger">*</span></label><input type="date" name="tanggal_jadwal" id="tanggal_jadwal" class="form-control" required></div><div class="col-md-6 mb-3"><label class="form-label">Periode <span class="text-danger">*</span></label><select name="periode" id="periode" class="form-select" required><option value="mingguan">Mingguan</option><option value="bulanan" selected>Bulanan</option><option value="triwulan">Triwulan (3 Bulan)</option></select></div></div>
<div class="mb-3"><label class="form-label">Catatan</label><textarea name="catatan" id="catatan" class="form-control" rows="3" placeholder="Catatan untuk teknisi"></textarea></div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan</button></div></form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>function resetForm(){document.getElementById('jadwal_id').value='';document.getElementById('komputer_id').value='';document.getElementById('siswa_pkl_id').value='';document.getElementById('tanggal_jadwal').value='';document.getElementById('periode').value='bulanan';document.getElementById('catatan').value='';document.getElementById('modalTitle').textContent='Tambah Jadwal PM'}function editJadwal(j){document.getElementById('jadwal_id').value=j.jadwal_id;document.getElementById('komputer_id').value=j.komputer_id;document.getElementById('siswa_pkl_id').value=j.siswa_pkl_id||'';document.getElementById('tanggal_jadwal').value=j.tanggal_jadwal;document.getElementById('periode').value=j.periode;document.getElementById('catatan').value=j.catatan||'';document.getElementById('modalTitle').textContent='Edit Jadwal PM';new bootstrap.Modal(document.getElementById('jadwalModal')).show()}</script>
</body>
</html>