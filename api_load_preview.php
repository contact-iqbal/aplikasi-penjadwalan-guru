<?php
require_once 'config/database.php';

$mode = $_GET['mode'] ?? 'semua';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($mode === 'semua') {
    $kelas_list = [];
    $kelas_res = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas ASC");
    while ($row = mysqli_fetch_assoc($kelas_res)) {
        $kelas_list[] = $row;
    }

    $waktu_data = [];
    $waktu_res = mysqli_query($koneksi, "SELECT id_waktu, hari, jam_ke, range_waktu FROM tbl_waktu_pelajaran ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_ke ASC");
    while ($row = mysqli_fetch_assoc($waktu_res)) {
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

    <?php
} elseif ($mode === 'kelas' && $id > 0) {
    $kelas_res = mysqli_query($koneksi, "SELECT nama_kelas FROM tbl_kelas WHERE id_kelas = $id");
    if ($kelas_res && mysqli_num_rows($kelas_res) > 0) {
        $kelas_data = mysqli_fetch_assoc($kelas_res);
        $nama_kelas = $kelas_data['nama_kelas'];

        $waktu_res = mysqli_query($koneksi, "SELECT id_waktu, hari, jam_ke, range_waktu FROM tbl_waktu_pelajaran ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_ke ASC");
        $jadwal_data = [];
        $jadwal_res = mysqli_query($koneksi, "SELECT j.id_waktu, g.nama_guru, m.nama_mapel FROM tbl_jadwal j JOIN tbl_guru_mapel gm ON j.id_guru_mapel = gm.id_guru_mapel JOIN tbl_guru g ON gm.id_guru = g.id_guru JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel WHERE j.id_kelas = $id");
        while($row = mysqli_fetch_assoc($jadwal_res)) {
            $jadwal_data[$row['id_waktu']] = $row;
        }
        ?>
        <h4 class="mb-4"><i class="fas fa-school mr-2"></i> Jadwal Kelas: <?php echo htmlspecialchars($nama_kelas); ?></h4>
        <?php
        $current_day = '';
        while($waktu_row = mysqli_fetch_assoc($waktu_res)):
            if ($current_day != $waktu_row['hari']) {
                if ($current_day != '') echo '</div>';
                $current_day = $waktu_row['hari'];
                echo '<h5 class="mt-3"><i class="far fa-calendar mr-2"></i>'.$current_day.'</h5><div class="mb-3">';
            }
            $slot_data = $jadwal_data[$waktu_row['id_waktu']] ?? null;
        ?>
            <div class="day-schedule-item <?php echo $slot_data ? 'filled' : ''; ?>">
                <div>
                    <i class="far fa-clock mr-2"></i>
                    <strong>Jam ke-<?php echo $waktu_row['jam_ke']; ?></strong>
                    <small class="text-muted">(<?php echo $waktu_row['range_waktu']; ?>)</small>
                </div>
                <div class="text-right">
                    <?php if($slot_data): ?>
                        <strong><?php echo htmlspecialchars($slot_data['nama_guru']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($slot_data['nama_mapel']); ?></small>
                    <?php else: ?>
                        <span class="text-muted"><i>Kosong</i></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; echo '</div>'; ?>
        <?php
    } else {
        echo '<div class="alert alert-warning">Kelas tidak ditemukan.</div>';
    }
} elseif ($mode === 'guru' && $id > 0) {
    $guru_res = mysqli_query($koneksi, "SELECT nama_guru FROM tbl_guru WHERE id_guru = $id");
    if ($guru_res && mysqli_num_rows($guru_res) > 0) {
        $guru_data = mysqli_fetch_assoc($guru_res);
        $nama_guru = $guru_data['nama_guru'];

        $waktu_res = mysqli_query($koneksi, "SELECT id_waktu, hari, jam_ke, range_waktu FROM tbl_waktu_pelajaran ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_ke ASC");
        $jadwal_data = [];
        $jadwal_res = mysqli_query($koneksi, "SELECT j.id_waktu, k.nama_kelas, m.nama_mapel FROM tbl_jadwal j JOIN tbl_guru_mapel gm ON j.id_guru_mapel = gm.id_guru_mapel JOIN tbl_kelas k ON j.id_kelas = k.id_kelas JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel WHERE gm.id_guru = $id");
        while($row = mysqli_fetch_assoc($jadwal_res)) {
            $jadwal_data[$row['id_waktu']] = $row;
        }
        ?>
        <h4 class="mb-4"><i class="fas fa-chalkboard-user mr-2"></i> Jadwal Mengajar: <?php echo htmlspecialchars($nama_guru); ?></h4>
        <?php
        $current_day = '';
        while($waktu_row = mysqli_fetch_assoc($waktu_res)):
            if ($current_day != $waktu_row['hari']) {
                if ($current_day != '') echo '</div>';
                $current_day = $waktu_row['hari'];
                echo '<h5 class="mt-3"><i class="far fa-calendar mr-2"></i>'.$current_day.'</h5><div class="mb-3">';
            }
            $slot_data = $jadwal_data[$waktu_row['id_waktu']] ?? null;
        ?>
            <div class="day-schedule-item <?php echo $slot_data ? 'filled' : ''; ?>">
                <div>
                    <i class="far fa-clock mr-2"></i>
                    <strong>Jam ke-<?php echo $waktu_row['jam_ke']; ?></strong>
                    <small class="text-muted">(<?php echo $waktu_row['range_waktu']; ?>)</small>
                </div>
                <div class="text-right">
                    <?php if($slot_data): ?>
                        <strong><?php echo htmlspecialchars($slot_data['nama_kelas']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($slot_data['nama_mapel']); ?></small>
                    <?php else: ?>
                        <span class="text-muted"><i>Kosong</i></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; echo '</div>'; ?>
        <?php
    } else {
        echo '<div class="alert alert-warning">Guru tidak ditemukan.</div>';
    }
} else {
    echo '<div class="alert alert-info">Silakan pilih mode preview.</div>';
}
?>
