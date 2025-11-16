<?php
require_once 'config/database.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_wajib = (int)$_GET['id'];

    $stmt_info = mysqli_prepare($koneksi, "SELECT id_waktu, id_kelas FROM tbl_jadwal_wajib WHERE id_wajib = ?");
    mysqli_stmt_bind_param($stmt_info, 'i', $id_wajib);
    mysqli_stmt_execute($stmt_info);
    mysqli_stmt_bind_result($stmt_info, $id_waktu, $id_kelas);
    $info_found = mysqli_stmt_fetch($stmt_info);
    mysqli_stmt_close($stmt_info);

    if ($info_found) {
        mysqli_begin_transaction($koneksi);
        try {
            $stmt_del_jadwal = mysqli_prepare($koneksi, "DELETE FROM tbl_jadwal WHERE id_waktu = ? AND id_kelas = ?");
            mysqli_stmt_bind_param($stmt_del_jadwal, 'ii', $id_waktu, $id_kelas);
            mysqli_stmt_execute($stmt_del_jadwal);
            mysqli_stmt_close($stmt_del_jadwal);

            $stmt_del_wajib = mysqli_prepare($koneksi, "DELETE FROM tbl_jadwal_wajib WHERE id_wajib = ?");
            mysqli_stmt_bind_param($stmt_del_wajib, 'i', $id_wajib);
            mysqli_stmt_execute($stmt_del_wajib);
            mysqli_stmt_close($stmt_del_wajib);

            mysqli_commit($koneksi);
            header('Location: jadwal_wajib.php?status=deleted');
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            die("Gagal menghapus jadwal: " . $e->getMessage() . " <a href='jadwal_wajib.php'>Kembali</a>");
        }
    } else {
        header('Location: jadwal_wajib.php?status=notfound');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_wajib'])) {
    $id_guru_mapel = (int)$_POST['id_guru_mapel'];
    $id_kelas = (int)$_POST['id_kelas'];
    $id_waktu = (int)$_POST['id_waktu'];

    if($id_guru_mapel > 0 && $id_kelas > 0 && $id_waktu > 0) {

        $stmt_cek_guru_mapel = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM tbl_guru_mapel WHERE id_guru_mapel = ?");
        mysqli_stmt_bind_param($stmt_cek_guru_mapel, 'i', $id_guru_mapel);
        mysqli_stmt_execute($stmt_cek_guru_mapel);
        mysqli_stmt_bind_result($stmt_cek_guru_mapel, $guru_mapel_exists);
        mysqli_stmt_fetch($stmt_cek_guru_mapel);
        mysqli_stmt_close($stmt_cek_guru_mapel);

        if ($guru_mapel_exists == 0) {
            die("Error: Penugasan guru-mapel tidak ditemukan. <a href='jadwal_wajib.php'>Kembali</a>");
        }

        $stmt_cek_penugasan = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM tbl_penugasan_kelas WHERE id_guru_mapel = ?");
        mysqli_stmt_bind_param($stmt_cek_penugasan, 'i', $id_guru_mapel);
        mysqli_stmt_execute($stmt_cek_penugasan);
        mysqli_stmt_bind_result($stmt_cek_penugasan, $jumlah_penugasan);
        mysqli_stmt_fetch($stmt_cek_penugasan);
        mysqli_stmt_close($stmt_cek_penugasan);

        if ($jumlah_penugasan > 0) {
            $stmt_cek_penugasan_kelas = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM tbl_penugasan_kelas WHERE id_guru_mapel = ? AND id_kelas = ?");
            mysqli_stmt_bind_param($stmt_cek_penugasan_kelas, 'ii', $id_guru_mapel, $id_kelas);
            mysqli_stmt_execute($stmt_cek_penugasan_kelas);
            mysqli_stmt_bind_result($stmt_cek_penugasan_kelas, $penugasan_kelas_spesifik);
            mysqli_stmt_fetch($stmt_cek_penugasan_kelas);
            mysqli_stmt_close($stmt_cek_penugasan_kelas);

            if ($penugasan_kelas_spesifik == 0) {
                die("Error: Guru ini tidak memiliki penugasan untuk mengajar di kelas tersebut. <a href='jadwal_wajib.php'>Kembali</a>");
            }
        }

        mysqli_begin_transaction($koneksi);
        try {
            $stmt1 = mysqli_prepare($koneksi, "INSERT INTO tbl_jadwal (id_waktu, id_kelas, id_guru_mapel) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id_guru_mapel = VALUES(id_guru_mapel)");
            mysqli_stmt_bind_param($stmt1, 'iii', $id_waktu, $id_kelas, $id_guru_mapel);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            $stmt2 = mysqli_prepare($koneksi, "INSERT INTO tbl_jadwal_wajib (id_waktu, id_kelas, id_guru_mapel) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, 'iii', $id_waktu, $id_kelas, $id_guru_mapel);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            mysqli_commit($koneksi);
            header('Location: jadwal_wajib.php?status=success');
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            die("Error: Terjadi bentrok jadwal atau slot sudah dikunci. Pesan: " . $e->getMessage() . " <a href='jadwal_wajib.php'>Kembali</a>");
        }
        exit;
    }
}

include 'templates/header.php';

$assignments_list = mysqli_query($koneksi, "SELECT gm.id_guru_mapel, g.nama_guru, m.nama_mapel FROM tbl_guru_mapel gm JOIN tbl_guru g ON gm.id_guru = g.id_guru JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel ORDER BY g.nama_guru, m.nama_mapel");
$list_kelas = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas");
$list_waktu = mysqli_query($koneksi, "SELECT id_waktu, hari, jam_ke, range_waktu FROM tbl_waktu_pelajaran ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_ke");

$daftar_wajib_query = "SELECT jw.id_wajib, g.nama_guru, m.nama_mapel, k.nama_kelas, w.hari, w.jam_ke, w.range_waktu FROM tbl_jadwal_wajib jw JOIN tbl_guru_mapel gm ON jw.id_guru_mapel = gm.id_guru_mapel JOIN tbl_guru g ON gm.id_guru = g.id_guru JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel JOIN tbl_kelas k ON jw.id_kelas = k.id_kelas JOIN tbl_waktu_pelajaran w ON jw.id_waktu = w.id_waktu ORDER BY w.id_waktu, k.nama_kelas";
$daftar_wajib = mysqli_query($koneksi, $daftar_wajib_query);
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home" style="margin-left: 12px;"></i>Dashboard</a></li>
        <li class="breadcrumb-item active">Kunci Jadwal</li>
    </ol>
</nav>

<section class="content">
    <div class="container-fluid">

        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle mr-2"></i> PENTING</h5>
            Fitur ini digunakan hanya untuk guru dengan permintaan khusus, misal hanya bisa mengajar di hari Senin jam pertama.
        </div>

        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lock mr-2"></i> Tambah Jadwal Wajib Baru</h3>
            </div>
            <form action="jadwal_wajib.php" method="POST">
                <div class="card-body">
                    <div class="form-group">
                        <label>Guru & Mata Pelajaran</label>
                        <select name="id_guru_mapel" class="form-control" required>
                            <option value="">-- Pilih Guru & Mapel --</option>
                            <?php while($a = mysqli_fetch_assoc($assignments_list)) echo "<option value='{$a['id_guru_mapel']}'>".htmlspecialchars($a['nama_guru'])." (".htmlspecialchars($a['nama_mapel']).")</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="id_kelas" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php mysqli_data_seek($list_kelas, 0); while($k = mysqli_fetch_assoc($list_kelas)) echo "<option value='{$k['id_kelas']}'>".htmlspecialchars($k['nama_kelas'])."</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Waktu</label>
                        <select name="id_waktu" class="form-control" required>
                            <option value="">-- Pilih Waktu --</option>
                            <?php while($w = mysqli_fetch_assoc($list_waktu)) echo "<option value='{$w['id_waktu']}'>".htmlspecialchars($w['hari']).", Jam ke-".$w['jam_ke']." (".htmlspecialchars($w['range_waktu']).")</option>"; ?>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="save_wajib" class="btn btn-warning">
                        <i class="fas fa-lock mr-1"></i> Kunci Jadwal Ini
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i> Daftar Jadwal yang Dikunci</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Hari & Waktu</th>
                            <th>Kelas</th>
                            <th>Guru & Mapel</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if($daftar_wajib && mysqli_num_rows($daftar_wajib) > 0): ?>
                        <?php while($dw = mysqli_fetch_assoc($daftar_wajib)): ?>
                            <tr>
                                <td>
                                    <i class="far fa-calendar mr-1"></i>
                                    <?php echo htmlspecialchars($dw['hari'] . ", Jam " . $dw['jam_ke']); ?>
                                    <small class="d-block text-muted">(<?php echo htmlspecialchars($dw['range_waktu']); ?>)</small>
                                </td>
                                <td><strong><?php echo htmlspecialchars($dw['nama_kelas']); ?></strong></td>
                                <td><?php echo htmlspecialchars($dw['nama_guru'] . " (" . $dw['nama_mapel'] . ")"); ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $dw['id_wajib']; ?>, '<?php echo htmlspecialchars($dw['hari'] . ', Jam ' . $dw['jam_ke'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($dw['nama_kelas'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-unlock"></i> Buka Kunci
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Belum ada jadwal yang dikunci.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<script>
function confirmDelete(id, waktu, kelas) {
    Swal.fire({
        title: 'Buka Kunci Jadwal?',
        html: 'Yakin ingin membuka kunci jadwal <strong>' + waktu + '</strong> untuk kelas <strong>' + kelas + '</strong>?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Buka Kunci',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'jadwal_wajib.php?action=delete&id=' + id;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Jadwal berhasil dikunci.',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'deleted') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Jadwal berhasil dibuka kuncinya.',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'notfound') {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Data jadwal tidak ditemukan.'
        });
    }

    if (status) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php include 'templates/footer.php'; ?>
