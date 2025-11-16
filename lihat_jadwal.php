<?php
require_once 'config/database.php';
include 'templates/header.php';

$list_kelas_result = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC");
$list_guru_result = mysqli_query($koneksi, "SELECT id_guru, nama_guru FROM tbl_guru ORDER BY nama_guru ASC");
$waktu_list_query = "SELECT id_waktu, hari, jam_ke, range_waktu FROM tbl_waktu_pelajaran ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_ke ASC";
$waktu_list = mysqli_query($koneksi, $waktu_list_query);

$show_all_schedule = isset($_GET['show_all']) && $_GET['show_all'] == '1';

$view_mode = '';
$id_terpilih = 0;
$nama_header = '';
$jadwal_data = [];

if (isset($_GET['kelas_id']) && !empty($_GET['kelas_id'])) {
    $view_mode = 'kelas';
    $id_terpilih = (int)$_GET['kelas_id'];
    $res = mysqli_query($koneksi, "SELECT nama_kelas FROM tbl_kelas WHERE id_kelas = $id_terpilih");
    $nama_header = "Jadwal Kelas: " . htmlspecialchars(mysqli_fetch_assoc($res)['nama_kelas']);
    $res_jadwal = mysqli_query($koneksi, "SELECT j.id_waktu, g.nama_guru, m.nama_mapel FROM tbl_jadwal j JOIN tbl_guru_mapel gm ON j.id_guru_mapel = gm.id_guru_mapel JOIN tbl_guru g ON gm.id_guru = g.id_guru JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel WHERE j.id_kelas = $id_terpilih");
} elseif (isset($_GET['guru_id']) && !empty($_GET['guru_id'])) {
    $view_mode = 'guru';
    $id_terpilih = (int)$_GET['guru_id'];
    $res = mysqli_query($koneksi, "SELECT nama_guru FROM tbl_guru WHERE id_guru = $id_terpilih");
    $nama_header = "Jadwal Guru: " . htmlspecialchars(mysqli_fetch_assoc($res)['nama_guru']);
    $res_jadwal = mysqli_query($koneksi, "SELECT j.id_waktu, k.nama_kelas, m.nama_mapel FROM tbl_jadwal j JOIN tbl_guru_mapel gm ON j.id_guru_mapel = gm.id_guru_mapel JOIN tbl_kelas k ON j.id_kelas = k.id_kelas JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel WHERE gm.id_guru = $id_terpilih");
}

if (!empty($view_mode) && $res_jadwal) {
    while($row = mysqli_fetch_assoc($res_jadwal)) {
        $jadwal_data[$row['id_waktu']] = $row;
    }
}
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home" style="margin-left: 12px;"></i>Dashboard</a></li>
        <li class="breadcrumb-item active">Lihat & Cetak</li>
    </ol>
</nav>

<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-4">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-pdf mr-2"></i> Cetak Massal</h3>
                    </div>
                    <div class="card-body">
                        <p>Membuat satu dokumen PDF berisi jadwal untuk semua kelas.</p>
                    </div>
                    <div class="card-footer">
                        <a href="?show_all=1" class="btn btn-danger btn-block">
                            <i class="fas fa-table mr-1"></i> Lihat Tabel
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-school mr-2"></i> Cetak Jadwal Kelas</h3>
                    </div>
                    <form action="lihat_jadwal.php" method="GET">
                        <div class="card-body">
                            <p>Mencetak Jadwal untuk Kelas Tertentu</p>
                            <div class="form-group">
                                <label>Pilih Kelas</label>
                                <select name="kelas_id" class="form-control" onchange="this.form.submit()" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php mysqli_data_seek($list_kelas_result, 0); while($kelas = mysqli_fetch_assoc($list_kelas_result)): ?>
                                        <option value="<?php echo $kelas['id_kelas']; ?>" <?php if($view_mode == 'kelas' && $id_terpilih == $kelas['id_kelas']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chalkboard-user mr-2"></i> Cetak Jadwal Guru</h3>
                    </div>
                    <form action="lihat_jadwal.php" method="GET">
                        <div class="card-body">
                            <p>Mencetak Jadwal untuk Individu Guru</p>
                            <div class="form-group">
                                <label>Pilih Guru</label>
                                <select name="guru_id" class="form-control" onchange="this.form.submit()" required>
                                    <option value="">-- Pilih Guru --</option>
                                    <?php mysqli_data_seek($list_guru_result, 0); while($guru = mysqli_fetch_assoc($list_guru_result)): ?>
                                        <option value="<?php echo $guru['id_guru']; ?>" <?php if($view_mode == 'guru' && $id_terpilih == $guru['id_guru']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($guru['nama_guru']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($view_mode) && !empty($jadwal_data)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo $nama_header; ?></h3>
                <div class="card-tools">
                    <a href="cetak_jadwal.php?<?php echo $view_mode; ?>_id=<?php echo $id_terpilih; ?>" target="_blank" class="btn btn-primary btn-sm no-print">
                        <i class="fas fa-print mr-1"></i> Cetak / PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php
                $current_day = '';
                mysqli_data_seek($waktu_list, 0);
                while($waktu_row = mysqli_fetch_assoc($waktu_list)):
                    if ($current_day != $waktu_row['hari']) {
                        if ($current_day != '') echo '</div>';
                        $current_day = $waktu_row['hari'];
                        echo '<h5 class="mt-3"><i class="far fa-calendar mr-2"></i>'.$current_day.'</h5><div class="mb-3">';
                    }
                    $slot_data = $jadwal_data[$waktu_row['id_waktu']] ?? null;
                ?>
                    <div class="d-flex justify-content-between align-items-center p-2 mb-2 border rounded <?php echo $slot_data ? 'bg-light' : ''; ?>">
                        <div>
                            <i class="far fa-clock mr-2"></i>
                            <strong>Jam ke-<?php echo $waktu_row['jam_ke']; ?></strong>
                            <small class="text-muted">(<?php echo $waktu_row['range_waktu']; ?>)</small>
                        </div>
                        <div class="text-right">
                            <?php if($slot_data): ?>
                                <?php if($view_mode == 'kelas'): ?>
                                    <strong><?php echo htmlspecialchars($slot_data['nama_guru']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($slot_data['nama_mapel']); ?></small>
                                <?php else: ?>
                                    <strong><?php echo htmlspecialchars($slot_data['nama_kelas']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($slot_data['nama_mapel']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted"><i>-- Istirahat / Jam Kosong --</i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; echo '</div>'; ?>
            </div>
        </div>
        <?php elseif (!empty($view_mode) && empty($jadwal_data)): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle mr-2"></i> Jadwal Belum Tersedia</h5>
                Jadwal belum tersedia untuk pilihan ini. Silakan susun jadwal terlebih dahulu.
            </div>
        <?php endif; ?>

    </div>
</section>

<?php if ($show_all_schedule): ?>
<div class="card mt-4">
    <div class="card-header bg-danger">
        <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i> Jadwal Semua Kelas</h3>
        <div class="card-tools">
            <button onclick="printSchedule()" class="btn btn-light btn-sm">
                <i class="fas fa-print mr-1"></i> Cetak
            </button>
        </div>
    </div>
    <div class="card-body p-0" id="schedule-print-area">
        <?php
        $kelas_list = [];
        $kelas_res = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC");
        while ($row = mysqli_fetch_assoc($kelas_res)) {
            $kelas_list[] = $row;
        }

        $waktu_data = [];
        mysqli_data_seek($waktu_list, 0);
        while ($row = mysqli_fetch_assoc($waktu_list)) {
            $waktu_data[$row['hari']][] = $row;
        }

        $mapel_colors = [];
        $color_palette = [
            '#FFE5E5', '#E5F5FF', '#E5FFE5', '#FFF5E5', '#FFE5F5',
            '#F5E5FF', '#E5FFFF', '#FFFFE5', '#F0F0F0', '#FFE5CC',
            '#E5CCFF', '#CCFFE5', '#FFCCCC', '#CCCCFF', '#CCFFFF',
            '#FFFFCC', '#FFD9B3', '#D9FFB3', '#B3FFD9', '#B3D9FF'
        ];
        $color_index = 0;

        $jadwal_grid = [];
        $guru_info = [];
        $jadwal_res = mysqli_query($koneksi, "SELECT j.id_waktu, j.id_kelas, g.nama_guru, m.nama_mapel, m.id_mapel
                                              FROM tbl_jadwal j
                                              JOIN tbl_guru_mapel gm ON j.id_guru_mapel = gm.id_guru_mapel
                                              JOIN tbl_guru g ON gm.id_guru = g.id_guru
                                              JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel");
        while ($row = mysqli_fetch_assoc($jadwal_res)) {
            $jadwal_grid[$row['id_waktu']][$row['id_kelas']] = $row;

            if (!isset($mapel_colors[$row['nama_mapel']])) {
                $mapel_colors[$row['nama_mapel']] = $color_palette[$color_index % count($color_palette)];
                $color_index++;
            }

            if (!isset($guru_info[$row['nama_guru']])) {
                $guru_info[$row['nama_guru']] = [];
            }
            if (!in_array($row['nama_mapel'], $guru_info[$row['nama_guru']])) {
                $guru_info[$row['nama_guru']][] = $row['nama_mapel'];
            }
        }
        ksort($guru_info);
        ?>

        <div class="table-responsive">
            <table class="table table-bordered table-sm m-0" style="font-size: 11px;">
                <thead>
                    <tr class="bg-secondary">
                        <th rowspan="2" class="text-center align-middle" style="width: 80px;">HARI</th>
                        <th rowspan="2" class="text-center align-middle" style="width: 50px;">JAM</th>
                        <th colspan="<?php echo count($kelas_list); ?>" class="text-center">KELAS</th>
                    </tr>
                    <tr>
                        <?php foreach ($kelas_list as $kelas): ?>
                        <th class="text-center bg-light" style="min-width: 100px;"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waktu_data as $hari => $slots): ?>
                        <?php $first_row = true; foreach ($slots as $slot): ?>
                        <tr>
                            <?php if ($first_row): ?>
                            <td rowspan="<?php echo count($slots); ?>" class="text-center align-middle font-weight-bold bg-light">
                                <?php echo $hari; ?>
                            </td>
                            <?php $first_row = false; endif; ?>
                            <td class="text-center font-weight-bold"><?php echo $slot['jam_ke']; ?></td>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <?php
                                $cell_data = $jadwal_grid[$slot['id_waktu']][$kelas['id_kelas']] ?? null;
                                $bg_color = $cell_data ? $mapel_colors[$cell_data['nama_mapel']] : '#ffffff';
                                ?>
                                <td class="text-center" style="background-color: <?php echo $bg_color; ?>; padding: 5px;">
                                    <?php if ($cell_data): ?>
                                        <div style="font-weight: 600; font-size: 10px; margin-bottom: 2px;">
                                            <?php echo htmlspecialchars($cell_data['nama_guru']); ?>
                                        </div>
                                        <div style="font-size: 9px; color: #555;">
                                            <?php echo htmlspecialchars($cell_data['nama_mapel']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            <h5 class="font-weight-bold mb-3"><i class="fas fa-users mr-2"></i> Daftar Guru & Mata Pelajaran</h5>
            <div class="row">
                <?php foreach ($guru_info as $guru => $mapel_list): ?>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="border rounded p-2" style="background-color: #f8f9fa;">
                        <div style="font-weight: 600; font-size: 12px; margin-bottom: 5px;">
                            <?php echo htmlspecialchars($guru); ?>
                        </div>
                        <?php foreach ($mapel_list as $mapel): ?>
                        <div class="d-inline-block mr-1 mb-1" style="padding: 2px 8px; border-radius: 3px; font-size: 10px; background-color: <?php echo $mapel_colors[$mapel]; ?>; border: 1px solid #ddd;">
                            <?php echo htmlspecialchars($mapel); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-3">
                <h6 class="font-weight-bold"><i class="fas fa-palette mr-2"></i> Legenda Warna Mata Pelajaran</h6>
                <div class="d-flex flex-wrap">
                    <?php foreach ($mapel_colors as $mapel => $color): ?>
                    <div class="mr-3 mb-2 d-flex align-items-center">
                        <div style="width: 20px; height: 20px; background-color: <?php echo $color; ?>; border: 1px solid #ddd; margin-right: 5px;"></div>
                        <span style="font-size: 11px;"><?php echo htmlspecialchars($mapel); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .content-wrapper,
    .main-sidebar,
    .main-header,
    .breadcrumb,
    .card:not(#schedule-print-area):not(#schedule-print-area *) {
        display: none !important;
    }

    body, html {
        width: 297mm;
        height: 210mm;
        margin: 0;
        padding: 0;
    }

    #schedule-print-area {
        display: block !important;
        margin: 0;
        padding: 10px;
    }

    .table {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    @page {
        size: A4 landscape;
        margin: 10mm;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>

<script>
function printSchedule() {
    window.print();
}
</script>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>
