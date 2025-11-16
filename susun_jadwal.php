<?php
session_start();
require_once 'config/database.php';

if (isset($_GET['action']) && $_GET['action'] == 'reset') {
    mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE (id_waktu, id_kelas) NOT IN (SELECT id_waktu, id_kelas FROM tbl_jadwal_wajib)");
    header('Location: susun_jadwal.php?status=reset_success');
    exit;
}

include 'templates/header.php';

$has_emergency = isset($_SESSION['emergency_placements']) && !empty($_SESSION['emergency_placements']);
$has_unplaced = isset($_SESSION['unplaced_blocks']) && !empty($_SESSION['unplaced_blocks']);

$assignments_list = mysqli_query($koneksi, "SELECT gm.id_guru_mapel, g.nama_guru, m.nama_mapel FROM tbl_guru_mapel gm JOIN tbl_guru g ON gm.id_guru = g.id_guru JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel ORDER BY g.nama_guru, m.nama_mapel");
$guru_dropdown = mysqli_fetch_all($assignments_list, MYSQLI_ASSOC);

$list_kelas = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC");
$kelas_header = mysqli_fetch_all($list_kelas, MYSQLI_ASSOC);

$list_waktu = mysqli_query($koneksi, "SELECT id_waktu, hari, jam_ke, range_waktu FROM tbl_waktu_pelajaran ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_ke ASC");
$waktu_rows = mysqli_fetch_all($list_waktu, MYSQLI_ASSOC);

$jadwal_tersimpan = [];
$jadwal_result = mysqli_query($koneksi, "SELECT id_waktu, id_kelas, id_guru_mapel FROM tbl_jadwal");
while($row = mysqli_fetch_assoc($jadwal_result)) {
    $jadwal_tersimpan[$row['id_waktu']][$row['id_kelas']] = $row['id_guru_mapel'];
}

$jadwal_wajib = [];
$wajib_result = mysqli_query($koneksi, "SELECT id_waktu, id_kelas FROM tbl_jadwal_wajib");
while($row = mysqli_fetch_assoc($wajib_result)) {
    $jadwal_wajib[$row['id_waktu']][$row['id_kelas']] = true;
}

$jadwal_per_hari = [];
foreach ($waktu_rows as $waktu) {
    $jadwal_per_hari[$waktu['hari']][] = $waktu;
}
?>

<style>
.schedule-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 2px solid #dee2e6;
}
.schedule-tab {
    padding: 10px 20px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-radius: 5px 5px 0 0;
    cursor: pointer;
    transition: all 0.3s;
}
.schedule-tab:hover {
    background: #e9ecef;
}
.schedule-tab.active {
    background: #fff;
    border-bottom: 2px solid #fff;
    margin-bottom: -2px;
    font-weight: bold;
}
.schedule-content {
    display: none;
}
.schedule-content.active {
    display: block;
}
.schedule-grid-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.schedule-timeslot {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    overflow: hidden;
}
.time-info {
    background: #007bff;
    color: white;
    padding: 10px 15px;
}
.time-info h4 {
    margin: 0;
    font-size: 1.1rem;
}
.time-info span {
    font-size: 0.9rem;
    opacity: 0.9;
}
.class-cells-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1px;
    background: #dee2e6;
}
.class-cell {
    background: white;
    padding: 10px;
}
.class-cell.locked {
    background: #f8f9fa;
}
.class-cell.saving {
    opacity: 0.6;
}
.class-name {
    font-weight: bold;
    margin-bottom: 5px;
    color: #495057;
}
.schedule-select {
    width: 100%;
    padding: 5px;
    border: 1px solid #ced4da;
    border-radius: 3px;
}
.lock-icon {
    color: #ffc107;
    margin-left: 5px;
}
</style>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home" style="margin-left: 12px;"></i>Dashboard</a></li>
        <li class="breadcrumb-item active">Susun Jadwal</li>
    </ol>
</nav>

<section class="content">
    <div class="container-fluid">

        <?php if ($has_emergency): ?>
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="fas fa-exclamation-triangle mr-2"></i> Kondisi Darurat: Penempatan Terpisah</h5>
                <p>Jadwal sangat padat. Beberapa jadwal terpaksa ditempatkan secara terpisah dari blok idealnya:</p>
                <ul>
                    <?php foreach ($_SESSION['emergency_placements'] as $pesan): ?>
                        <li><?php echo $pesan; ?></li>
                    <?php endforeach; ?>
                </ul>
                <hr>
                <p class="mb-0">Silakan periksa dan atur ulang secara manual jika diperlukan.</p>
            </div>
            <?php unset($_SESSION['emergency_placements']); ?>
        <?php endif; ?>

        <?php if ($has_unplaced): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="fas fa-times-circle mr-2"></i> KRITIS: Jadwal Gagal Ditempatkan!</h5>
                <p>Beberapa jadwal berikut TIDAK BISA ditempatkan:</p>
                <ul>
                    <?php foreach ($_SESSION['unplaced_blocks'] as $pesan): ?>
                        <li><?php echo $pesan; ?></li>
                    <?php endforeach; ?>
                </ul>
                <hr>
                <p class="mb-0">Jadwal ini perlu ditambahkan secara manual.</p>
            </div>
            <?php unset($_SESSION['unplaced_blocks']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        Isi jadwal di bawah atau gunakan tombol otomatis. Jadwal dengan ikon <i class="fas fa-lock text-warning"></i> tidak dapat diubah.
                    </p>
                    <div>
                        <a href="proses_otomatis.php" class="btn btn-primary" onclick="return confirm('Ini akan menimpa semua jadwal yang tidak dikunci. Lanjutkan?')">
                            <i class="fas fa-magic mr-1"></i> Buat Otomatis
                        </a>
                        <a href="susun_jadwal.php?action=reset" class="btn btn-danger" onclick="return confirm('Ini akan MENGHAPUS semua jadwal yang tidak dikunci. Lanjutkan?')">
                            <i class="fas fa-trash-alt mr-1"></i> Reset
                        </a>
                    </div>
                </div>

                <div class="schedule-tabs">
                    <?php
                    $first_day = true;
                    foreach (array_keys($jadwal_per_hari) as $hari):
                    ?>
                        <button class="schedule-tab <?php echo $first_day ? 'active' : ''; ?>" onclick="switchTab(event, '<?php echo strtolower($hari); ?>')">
                            <?php echo $hari; ?>
                        </button>
                    <?php
                    $first_day = false;
                    endforeach;
                    ?>
                </div>

                <?php
                $first_day = true;
                foreach ($jadwal_per_hari as $hari => $waktu_list):
                ?>
                    <div id="<?php echo strtolower($hari); ?>" class="schedule-content <?php echo $first_day ? 'active' : ''; ?>">
                        <div class="schedule-grid-container">
                            <?php foreach ($waktu_list as $waktu): ?>
                                <div class="schedule-timeslot">
                                    <div class="time-info">
                                        <h4>Jam ke-<?php echo htmlspecialchars($waktu['jam_ke']); ?></h4>
                                        <span><?php echo htmlspecialchars($waktu['range_waktu']); ?></span>
                                    </div>
                                    <div class="class-cells-grid">
                                        <?php foreach ($kelas_header as $kelas):
                                            $id_gm_terpilih = $jadwal_tersimpan[$waktu['id_waktu']][$kelas['id_kelas']] ?? 0;
                                            $is_locked = isset($jadwal_wajib[$waktu['id_waktu']][$kelas['id_kelas']]);
                                        ?>
                                            <div class="class-cell <?php echo $is_locked ? 'locked' : ''; ?>">
                                                <div class="class-name"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></div>
                                                <select class="schedule-select"
                                                        data-id-waktu="<?php echo $waktu['id_waktu']; ?>"
                                                        data-id-kelas="<?php echo $kelas['id_kelas']; ?>"
                                                        onchange="simpanJadwal(this)"
                                                        <?php echo $is_locked ? 'disabled' : ''; ?>>
                                                    <option value="0">-- Kosong --</option>
                                                    <?php foreach ($guru_dropdown as $gm) {
                                                        $selected = ($gm['id_guru_mapel'] == $id_gm_terpilih) ? 'selected' : '';
                                                        echo "<option value='{$gm['id_guru_mapel']}' {$selected}>" .
                                                             htmlspecialchars($gm['nama_guru'] . ' - ' . $gm['nama_mapel']) .
                                                             "</option>";
                                                    } ?>
                                                </select>
                                                <?php if ($is_locked): ?>
                                                    <i class="fas fa-lock lock-icon" title="Jadwal ini dikunci"></i>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                $first_day = false;
                endforeach;
                ?>
            </div>
        </div>

    </div>
</section>

<div id="notification-container" style="position: fixed; top: 80px; right: 20px; z-index: 1050; max-width: 400px;"></div>

<script>
    let originalValues = {};

    function switchTab(event, day) {
        document.querySelectorAll('.schedule-content').forEach(content => content.classList.remove('active'));
        document.querySelectorAll('.schedule-tab').forEach(tab => tab.classList.remove('active'));
        document.getElementById(day).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    async function simpanJadwal(element) {
        const id_waktu = element.dataset.idWaktu;
        const id_kelas = element.dataset.idKelas;
        const id_guru_mapel = element.value;
        const cell = element.closest('.class-cell');
        const cellKey = `${id_waktu}-${id_kelas}`;

        if (typeof originalValues[cellKey] === 'undefined') {
            element.onfocus = function() {
                originalValues[cellKey] = this.value;
            };
        }

        cell.classList.add('saving');

        try {
            const response = await fetch('api_simpan_jadwal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_waktu, id_kelas, id_guru_mapel })
            });

            const result = await response.json();

            if (result.status === 'success') {
                showNotification(result.message, 'success');
                originalValues[cellKey] = id_guru_mapel;
            } else {
                showNotification(result.message, 'danger');
                element.value = originalValues[cellKey] || 0;
            }

        } catch (error) {
            showNotification('Terjadi kesalahan koneksi.', 'danger');
            element.value = originalValues[cellKey] || 0;
        } finally {
            cell.classList.remove('saving');
        }
    }

    function showNotification(message, type) {
        const container = document.getElementById('notification-container');
        const alertType = type === 'success' ? 'alert-success' : 'alert-danger';

        const notification = document.createElement('div');
        notification.className = `alert ${alertType} alert-dismissible fade show`;
        notification.innerHTML = `
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            ${message}
        `;

        container.appendChild(notification);

        setTimeout(() => {
            $(notification).alert('close');
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'reset_success') {
            showNotification('Jadwal berhasil direset!', 'success');
        } else if (status === 'auto_success') {
            showNotification('Jadwal berhasil dibuat secara otomatis!', 'success');
        }
        if (status) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
</script>

<?php include 'templates/footer.php'; ?>
