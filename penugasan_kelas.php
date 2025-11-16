<?php
require_once 'config/database.php';

if (!isset($_GET['id_guru_mapel']) || empty($_GET['id_guru_mapel'])) {
    header('Location: guru.php?error=id_tidak_ditemukan');
    exit;
}

$id_guru_mapel = (int)$_GET['id_guru_mapel'];

if ($id_guru_mapel <= 0) {
    header('Location: guru.php?error=id_tidak_valid');
    exit;
}

$notification = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assigned_kelas = $_POST['kelas_ids'] ?? [];

    mysqli_begin_transaction($koneksi);
    try {
        $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM tbl_penugasan_kelas WHERE id_guru_mapel = ?");
        mysqli_stmt_bind_param($stmt_delete, 'i', $id_guru_mapel);
        mysqli_stmt_execute($stmt_delete);

        if (!empty($assigned_kelas)) {
            $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO tbl_penugasan_kelas (id_guru_mapel, id_kelas) VALUES (?, ?)");
            foreach ($assigned_kelas as $id_kelas) {
                $id_kelas_int = (int)$id_kelas;
                mysqli_stmt_bind_param($stmt_insert, 'ii', $id_guru_mapel, $id_kelas_int);
                mysqli_stmt_execute($stmt_insert);
            }
        }

        mysqli_commit($koneksi);
        $notification = ['type' => 'success', 'message' => 'Data berhasil diperbarui! Mengarahkan kembali...'];

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $notification = ['type' => 'danger', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()];
    }
}

$assignment_res = mysqli_query($koneksi, "
    SELECT g.nama_guru, m.nama_mapel, gm.id_guru, gm.id_mapel
    FROM tbl_guru_mapel gm
    JOIN tbl_guru g ON gm.id_guru = g.id_guru
    JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel
    WHERE gm.id_guru_mapel = $id_guru_mapel
");

if (!$assignment_res) {
    die("Error query assignment: " . mysqli_error($koneksi));
}

if (mysqli_num_rows($assignment_res) == 0) {
    echo "<!DOCTYPE html>
    <html><head><title>Error</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css'>
    </head><body>
    <div class='container mt-5'>
        <div class='alert alert-danger'>
            <h4><i class='fas fa-exclamation-triangle'></i> Data Tidak Ditemukan</h4>
            <p>Data penugasan guru-mapel dengan ID <strong>$id_guru_mapel</strong> tidak ditemukan!</p>
            <p>Kemungkinan:</p>
            <ul>
                <li>Data telah dihapus</li>
                <li>ID tidak valid</li>
                <li>Belum ada penugasan guru ke mata pelajaran</li>
            </ul>
            <a href='guru.php' class='btn btn-primary'>Kembali ke Halaman Guru</a>
        </div>
    </div>
    </body></html>";
    exit;
}

$assignment_info = mysqli_fetch_assoc($assignment_res);
$nama_guru = $assignment_info['nama_guru'];
$nama_mapel = $assignment_info['nama_mapel'];

$all_kelas = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas");

if (!$all_kelas) {
    die("Error query kelas: " . mysqli_error($koneksi));
}

$assigned_kelas_res = mysqli_query($koneksi, "SELECT id_kelas FROM tbl_penugasan_kelas WHERE id_guru_mapel = $id_guru_mapel");

if (!$assigned_kelas_res) {
    die("Error query penugasan kelas: " . mysqli_error($koneksi));
}

$assigned_kelas_ids = [];
while ($row = mysqli_fetch_assoc($assigned_kelas_res)) {
    $assigned_kelas_ids[] = $row['id_kelas'];
}

include 'templates/header.php';
?>

<style>
.icheck-primary {
    display: block;
    margin-bottom: 10px;
}
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}
</style>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i>Dashboard</a></li>
        <li class="breadcrumb-item"><a href="guru.php">Guru</a></li>
        <li class="breadcrumb-item active">Penugasan Kelas</li>
    </ol>
</nav>

<section class="content">
    <div class="container-fluid">

        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Informasi Penugasan</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Guru:</dt>
                    <dd class="col-sm-9"><strong><?php echo htmlspecialchars($nama_guru); ?></strong></dd>

                    <dt class="col-sm-3">Mata Pelajaran:</dt>
                    <dd class="col-sm-9"><strong><?php echo htmlspecialchars($nama_mapel); ?></strong></dd>
                </dl>
            </div>
        </div>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i> Pilih Kelas yang Dapat Diajar</h3>
            </div>
            <form action="penugasan_kelas.php?id_guru_mapel=<?php echo $id_guru_mapel; ?>" method="POST">
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>PENTING:</strong> Jika tidak ada kelas yang dipilih, maka penugasan ini dianggap dapat diajarkan di <strong>semua kelas</strong>.
                    </div>

                    <div class="checkbox-grid">
                        <?php if ($all_kelas && mysqli_num_rows($all_kelas) > 0): ?>
                            <?php while ($kelas = mysqli_fetch_assoc($all_kelas)): ?>
                                <div class="icheck-primary">
                                    <input type="checkbox" name="kelas_ids[]" value="<?php echo $kelas['id_kelas']; ?>" id="kelas_<?php echo $kelas['id_kelas']; ?>"
                                        <?php if (in_array($kelas['id_kelas'], $assigned_kelas_ids)) echo 'checked'; ?>>
                                    <label for="kelas_<?php echo $kelas['id_kelas']; ?>"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></label>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">Belum ada data kelas yang ditambahkan. Silakan tambahkan data kelas terlebih dahulu.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="guru.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary float-right">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>
</section>

<?php if (!empty($notification)): ?>
<script>
$(document).ready(function() {
    <?php if ($notification['type'] === 'success'): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?php echo $notification['message']; ?>',
            timer: 2000,
            showConfirmButton: false
        }).then(function() {
            window.location.href = 'guru.php';
        });
    <?php else: ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?php echo $notification['message']; ?>'
        });
    <?php endif; ?>
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>
