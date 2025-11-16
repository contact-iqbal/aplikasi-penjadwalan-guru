<?php
session_start();
require_once 'config/database.php';

if (isset($_GET['action']) && $_GET['action'] == 'reset') {
    mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE (id_waktu, id_kelas) NOT IN (SELECT id_waktu, id_kelas FROM tbl_jadwal_wajib)");
    header('Location: jadwal.php?status=reset_success');
    exit;
}

include 'templates/header.php';

$has_emergency = isset($_SESSION['emergency_placements']) && !empty($_SESSION['emergency_placements']);
$has_unplaced = isset($_SESSION['unplaced_blocks']) && !empty($_SESSION['unplaced_blocks']);

$assignments_list = mysqli_query($koneksi, "SELECT gm.id_guru_mapel, g.nama_guru, m.nama_mapel FROM tbl_guru_mapel gm JOIN tbl_guru g ON gm.id_guru = g.id_guru JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel ORDER BY g.nama_guru, m.nama_mapel");
$guru_dropdown = mysqli_fetch_all($assignments_list, MYSQLI_ASSOC);

$list_kelas = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC");
$kelas_header = mysqli_fetch_all($list_kelas, MYSQLI_ASSOC);

$list_guru = mysqli_query($koneksi, "SELECT id_guru, nama_guru FROM tbl_guru ORDER BY nama_guru ASC");
$guru_list = mysqli_fetch_all($list_guru, MYSQLI_ASSOC);

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
.main-view-toggle {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}
.main-toggle-btn {
    flex: 1;
    padding: 15px;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}
.main-toggle-btn:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.main-toggle-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}
.main-toggle-btn i {
    font-size: 2rem;
    display: block;
    margin-bottom: 10px;
}
.main-view-container {
    display: none;
}
.main-view-container.active {
    display: block;
}
.schedule-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 2px solid #dee2e6;
    flex-wrap: wrap;
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
    font-size: 0.9rem;
}
.lock-icon {
    color: #ffc107;
    margin-left: 5px;
}
.preview-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.preview-mode-btn {
    padding: 10px 20px;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}
.preview-mode-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}
.preview-content {
    display: none;
}
.preview-content.active {
    display: block;
}
.day-schedule-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 8px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white;
}
.day-schedule-item.filled {
    background: #e7f3ff;
}
@media print {
    .no-print {
        display: none !important;
    }
}
</style>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home" style="margin-left: 12px;"></i>Dashboard</a></li>
        <li class="breadcrumb-item active">Kelola Jadwal</li>
    </ol>
</nav>

<section class="content">
    <div class="container-fluid">

        <?php if ($has_emergency): ?>
            <div class="alert alert-warning alert-dismissible no-print">
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
            <div class="alert alert-danger alert-dismissible no-print">
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

        <div class="card no-print">
            <div class="card-body">
                <div class="main-view-toggle">
                    <div class="main-toggle-btn active" onclick="switchMainView('edit')">
                        <i class="fas fa-edit"></i>
                        <strong>Mode Edit</strong>
                        <small class="d-block">Susun & Edit Jadwal</small>
                    </div>
                    <div class="main-toggle-btn" onclick="switchMainView('preview')">
                        <i class="fas fa-eye"></i>
                        <strong>Mode Lihat</strong>
                        <small class="d-block">Preview & Cetak Jadwal</small>
                    </div>
                </div>
            </div>
        </div>

        <div id="edit-view" class="main-view-container active">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                        <p class="mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            Isi jadwal di bawah atau gunakan tombol otomatis. Jadwal dengan ikon <i class="fas fa-lock text-warning"></i> tidak dapat diubah.
                        </p>
                        <div>
                            <button class="btn btn-primary" onclick="confirmAutoGenerate()">
                                <i class="fas fa-magic mr-1"></i> Buat Otomatis
                            </button>
                            <button class="btn btn-danger" onclick="confirmReset()">
                                <i class="fas fa-trash-alt mr-1"></i> Reset
                            </button>
                        </div>
                    </div>

                    <div class="schedule-tabs no-print">
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

        <div id="preview-view" class="main-view-container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-eye mr-2"></i> Preview Jadwal</h3>
                </div>
                <div class="card-body">
                    <div class="preview-selector no-print">
                        <button class="preview-mode-btn active" onclick="switchPreviewMode('semua')">
                            <i class="fas fa-table mr-2"></i> Semua Kelas
                        </button>
                        <select id="kelas-selector" class="form-control" style="flex: 1; max-width: 300px;" onchange="switchPreviewMode('kelas', this.value)">
                            <option value="">-- Lihat Per Kelas --</option>
                            <?php foreach ($kelas_header as $kelas): ?>
                                <option value="<?php echo $kelas['id_kelas']; ?>"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="guru-selector" class="form-control" style="flex: 1; max-width: 300px;" onchange="switchPreviewMode('guru', this.value)">
                            <option value="">-- Lihat Per Guru --</option>
                            <?php foreach ($guru_list as $guru): ?>
                                <option value="<?php echo $guru['id_guru']; ?>"><?php echo htmlspecialchars($guru['nama_guru']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" onclick="cetakJadwal()">
                            <i class="fas fa-print mr-1"></i> Cetak
                        </button>
                    </div>

                    <div id="preview-semua" class="preview-content active">
                        <div id="jadwal-semua-kelas"></div>
                    </div>

                    <div id="preview-kelas" class="preview-content">
                        <div id="jadwal-per-kelas"></div>
                    </div>

                    <div id="preview-guru" class="preview-content">
                        <div id="jadwal-per-guru"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<div id="notification-container" style="position: fixed; top: 80px; right: 20px; z-index: 1050; max-width: 400px;"></div>

<script>
let originalValues = {};
let currentPreviewMode = 'semua';
let currentPreviewId = null;

function switchMainView(view) {
    document.querySelectorAll('.main-toggle-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.main-view-container').forEach(container => container.classList.remove('active'));

    if (view === 'edit') {
        document.querySelector('.main-toggle-btn:first-child').classList.add('active');
        document.getElementById('edit-view').classList.add('active');
    } else {
        document.querySelector('.main-toggle-btn:last-child').classList.add('active');
        document.getElementById('preview-view').classList.add('active');
        loadPreview();
    }
}

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

function switchPreviewMode(mode, id = null) {
    document.querySelectorAll('.preview-mode-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.preview-content').forEach(content => content.classList.remove('active'));

    currentPreviewMode = mode;
    currentPreviewId = id;

    if (mode === 'semua') {
        document.querySelector('.preview-mode-btn').classList.add('active');
        document.getElementById('preview-semua').classList.add('active');
        document.getElementById('kelas-selector').value = '';
        document.getElementById('guru-selector').value = '';
    } else if (mode === 'kelas') {
        document.getElementById('preview-kelas').classList.add('active');
        document.getElementById('guru-selector').value = '';
    } else if (mode === 'guru') {
        document.getElementById('preview-guru').classList.add('active');
        document.getElementById('kelas-selector').value = '';
    }

    loadPreview();
}

async function loadPreview() {
    try {
        let url = 'api_load_preview.php?mode=' + currentPreviewMode;
        if (currentPreviewId) {
            url += '&id=' + currentPreviewId;
        }

        const response = await fetch(url);
        const html = await response.text();

        if (currentPreviewMode === 'semua') {
            document.getElementById('jadwal-semua-kelas').innerHTML = html;
        } else if (currentPreviewMode === 'kelas') {
            document.getElementById('jadwal-per-kelas').innerHTML = html;
        } else if (currentPreviewMode === 'guru') {
            document.getElementById('jadwal-per-guru').innerHTML = html;
        }
    } catch (error) {
        showNotification('Gagal memuat preview jadwal.', 'danger');
    }
}

function cetakJadwal() {
    let url = 'cetak_jadwal.php?mode=' + currentPreviewMode;
    if (currentPreviewId) {
        if (currentPreviewMode === 'kelas') {
            url = 'cetak_jadwal.php?kelas_id=' + currentPreviewId;
        } else if (currentPreviewMode === 'guru') {
            url = 'cetak_jadwal.php?guru_id=' + currentPreviewId;
        }
    } else {
        url = 'cetak_jadwal.php?mode=semua_kelas';
    }
    window.open(url, '_blank');
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

function confirmAutoGenerate() {
    Swal.fire({
        title: 'Buat Jadwal Otomatis?',
        text: 'Ini akan menimpa semua jadwal yang tidak dikunci. Lanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Buat',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'proses_otomatis.php';
        }
    });
}

function confirmReset() {
    Swal.fire({
        title: 'Reset Jadwal?',
        text: 'Ini akan MENGHAPUS semua jadwal yang tidak dikunci. Lanjutkan?',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'jadwal.php?action=reset';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (status === 'reset_success') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Jadwal berhasil direset!',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'auto_success') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Jadwal berhasil dibuat secara otomatis!',
            timer: 2000,
            showConfirmButton: false
        });
    }
    if (status) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php include 'templates/footer.php'; ?>
