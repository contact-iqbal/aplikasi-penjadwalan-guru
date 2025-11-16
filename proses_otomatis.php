<?php
require_once 'config/database.php';
session_start();

$_SESSION['emergency_placements'] = [];
$_SESSION['unplaced_blocks'] = [];

mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE (id_waktu, id_kelas) NOT IN (SELECT id_waktu, id_kelas FROM tbl_jadwal_wajib)");

function pecah_jam_ke_blok($jam) {
    if ($jam <= 0) return [];
    if ($jam == 1) return [1];
    if ($jam == 2) return [2];
    if ($jam == 3) return [3];
    if ($jam == 4) return [2, 2];
    if ($jam == 5) return [3, 2];
    if ($jam == 6) return [3, 3];

    $blocks = [];
    while ($jam > 0) {
        if ($jam >= 3) {
            $blocks[] = 3;
            $jam -= 3;
        } else {
            if ($jam > 0) $blocks[] = $jam;
            $jam = 0;
        }
    }
    return $blocks;
}

function getSchedulePattern($nama_mapel, $hari, $jam_ke) {
    $patterns = [
        'Olahraga' => ['best_days' => ['Senin'], 'best_hours' => [1, 2, 3]],
        'Penjaskes' => ['best_days' => ['Senin'], 'best_hours' => [1, 2, 3]],
        'Matematika' => ['best_days' => ['Senin', 'Selasa', 'Rabu'], 'best_hours' => [1, 2, 3, 4]],
        'Bahasa Inggris' => ['best_days' => ['Selasa', 'Rabu', 'Kamis'], 'best_hours' => [2, 3, 4]],
        'IPA' => ['best_days' => ['Rabu', 'Kamis'], 'best_hours' => [2, 3, 4]],
        'IPS' => ['best_days' => ['Kamis', 'Jumat'], 'best_hours' => [3, 4, 5]],
    ];

    $score = 0;
    foreach ($patterns as $keyword => $pattern) {
        if (stripos($nama_mapel, $keyword) !== false) {
            if (in_array($hari, $pattern['best_days'])) $score += 10;
            if (in_array($jam_ke, $pattern['best_hours'])) $score += 5;
            return $score;
        }
    }

    if ($jam_ke <= 3) $score += 3;
    return $score;
}

function cekBentrokMapelBerturut($id_guru_mapel, $id_kelas, $hari, $jam_ke, $ukuran, &$jadwal_mapel_per_hari) {
    if (!isset($jadwal_mapel_per_hari[$id_kelas][$hari])) {
        return false;
    }

    $jadwal_hari = $jadwal_mapel_per_hari[$id_kelas][$hari];

    for ($j = $jam_ke; $j < $jam_ke + $ukuran; $j++) {
        $count_berturut = 0;

        for ($check_jam = $j - 3; $check_jam < $j; $check_jam++) {
            if (isset($jadwal_hari[$check_jam]) && $jadwal_hari[$check_jam] == $id_guru_mapel) {
                $count_berturut++;
            }
        }

        if ($count_berturut >= 3) {
            return true;
        }
    }

    return false;
}

function tryPlaceChunk($blok, $ukuran_coba, &$jadwal_guru_terisi, &$jadwal_kelas_terisi, &$jadwal_mapel_per_hari, $all_waktu_slots, $stmt, $nama_mapel, $koneksi) {
    $possible_indices = range(0, count($all_waktu_slots) - $ukuran_coba);

    $weighted_indices = [];
    foreach ($possible_indices as $i) {
        $slot_awal = $all_waktu_slots[$i];
        $slot_akhir = $all_waktu_slots[$i + $ukuran_coba - 1];
        if ($slot_awal['hari'] != $slot_akhir['hari'] || $slot_awal['jam_ke'] + $ukuran_coba - 1 != $slot_akhir['jam_ke']) {
            continue;
        }

        $score = getSchedulePattern($nama_mapel, $slot_awal['hari'], $slot_awal['jam_ke']);
        $weighted_indices[] = ['index' => $i, 'score' => $score];
    }

    usort($weighted_indices, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    foreach ($weighted_indices as $item) {
        $i = $item['index'];
        $slot_awal = $all_waktu_slots[$i];
        $slot_akhir = $all_waktu_slots[$i + $ukuran_coba - 1];

        $potongan_slot = array_slice($all_waktu_slots, $i, $ukuran_coba);
        $chunk_bebas = true;

        if (cekBentrokMapelBerturut($blok['id_guru_mapel'], $blok['id_kelas'], $slot_awal['hari'], $slot_awal['jam_ke'], $ukuran_coba, $jadwal_mapel_per_hari)) {
            continue;
        }

        foreach ($potongan_slot as $slot) {
            if (isset($jadwal_guru_terisi[$slot['id_waktu']][$blok['id_guru']]) || isset($jadwal_kelas_terisi[$slot['id_waktu']][$blok['id_kelas']])) {
                $chunk_bebas = false;
                break;
            }
        }

        if ($chunk_bebas) {
            foreach ($potongan_slot as $slot) {
                mysqli_stmt_bind_param($stmt, 'iii', $slot['id_waktu'], $blok['id_kelas'], $blok['id_guru_mapel']);
                mysqli_stmt_execute($stmt);
                $jadwal_guru_terisi[$slot['id_waktu']][$blok['id_guru']] = true;
                $jadwal_kelas_terisi[$slot['id_waktu']][$blok['id_kelas']] = true;
                $jadwal_mapel_per_hari[$blok['id_kelas']][$slot['hari']][$slot['jam_ke']] = $blok['id_guru_mapel'];
            }
            return true;
        }
    }
    return false;
}

$guru_mapel_res = mysqli_query($koneksi, "SELECT id_guru_mapel, id_guru FROM tbl_guru_mapel");
$all_guru_mapel = mysqli_fetch_all($guru_mapel_res, MYSQLI_ASSOC);

$kelas_res = mysqli_query($koneksi, "SELECT id_kelas FROM tbl_kelas");
$all_kelas_ids = array_column(mysqli_fetch_all($kelas_res, MYSQLI_ASSOC), 'id_kelas');

$all_waktu_slots = [];
$waktu_res = mysqli_query($koneksi, "SELECT id_waktu, hari, jam_ke FROM tbl_waktu_pelajaran ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_ke ASC");
while ($row = mysqli_fetch_assoc($waktu_res)) {
    $all_waktu_slots[] = $row;
}

$jadwal_guru_terisi = [];
$jadwal_kelas_terisi = [];
$jadwal_mapel_per_hari = [];
$locked_res = mysqli_query($koneksi, "SELECT jw.id_waktu, jw.id_kelas, jw.id_guru_mapel, gm.id_guru, wp.hari, wp.jam_ke FROM tbl_jadwal_wajib jw JOIN tbl_guru_mapel gm ON jw.id_guru_mapel = gm.id_guru_mapel JOIN tbl_waktu_pelajaran wp ON jw.id_waktu = wp.id_waktu");
if ($locked_res) {
    while ($row = mysqli_fetch_assoc($locked_res)) {
        $jadwal_guru_terisi[$row['id_waktu']][$row['id_guru']] = true;
        $jadwal_kelas_terisi[$row['id_waktu']][$row['id_kelas']] = true;
        $jadwal_mapel_per_hari[$row['id_kelas']][$row['hari']][$row['jam_ke']] = $row['id_guru_mapel'];
    }
}

$penugasan_izin_kelas = [];
$izin_res = mysqli_query($koneksi, "SELECT id_guru_mapel, id_kelas FROM tbl_penugasan_kelas");
if ($izin_res) while ($row = mysqli_fetch_assoc($izin_res)) $penugasan_izin_kelas[$row['id_guru_mapel']][] = $row['id_kelas'];

// Hitung alokasi jam default per mata pelajaran per kelas
// Strategi: distribusi merata berdasarkan slot tersedia
$jam_per_mapel_kelas = [];
foreach ($all_kelas_ids as $id_kelas) {
    // Hitung total slot kosong untuk kelas ini
    $total_slot_query = mysqli_query($koneksi, "
        SELECT COUNT(*) as total
        FROM tbl_waktu_pelajaran wp
        WHERE NOT EXISTS (
            SELECT 1 FROM tbl_jadwal j
            WHERE j.id_waktu = wp.id_waktu AND j.id_kelas = {$id_kelas}
        )
    ");
    $slot_data = mysqli_fetch_assoc($total_slot_query);
    $total_slot_kosong = $slot_data ? (int)$slot_data['total'] : 0;

    // Hitung jumlah mata pelajaran yang bisa diajar di kelas ini
    $mapel_count = 0;
    foreach ($all_guru_mapel as $gm) {
        $dapat_diajar = !isset($penugasan_izin_kelas[$gm['id_guru_mapel']]) ||
                        in_array($id_kelas, $penugasan_izin_kelas[$gm['id_guru_mapel']]);
        if ($dapat_diajar) $mapel_count++;
    }

    // Alokasi merata (minimal 2 jam per mata pelajaran)
    $jam_per_mapel = ($mapel_count > 0) ? max(2, floor($total_slot_kosong / $mapel_count)) : 2;
    $jam_per_mapel_kelas[$id_kelas] = $jam_per_mapel;
}

$semua_blok = [];
foreach ($all_guru_mapel as $gm) {
    $kelas_untuk_gm = isset($penugasan_izin_kelas[$gm['id_guru_mapel']]) ? $penugasan_izin_kelas[$gm['id_guru_mapel']] : $all_kelas_ids;

    foreach ($kelas_untuk_gm as $id_kelas) {
        // Hitung jumlah jam yang sudah terjadwal untuk guru_mapel dan kelas ini
        $sudah_terjadwal = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbl_jadwal WHERE id_guru_mapel = {$gm['id_guru_mapel']} AND id_kelas = {$id_kelas}");
        $row_terjadwal = mysqli_fetch_assoc($sudah_terjadwal);
        $jam_sudah = $row_terjadwal ? (int)$row_terjadwal['total'] : 0;

        // Gunakan alokasi jam yang sudah dihitung
        $jam_target = isset($jam_per_mapel_kelas[$id_kelas]) ? $jam_per_mapel_kelas[$id_kelas] : 2;

        // Jam yang masih perlu dijadwalkan
        $jam_sisa = $jam_target - $jam_sudah;

        if ($jam_sisa <= 0) continue;

        $blocks = pecah_jam_ke_blok($jam_sisa);
        foreach ($blocks as $ukuran_blok) {
            $semua_blok[] = [
                'id_guru_mapel' => $gm['id_guru_mapel'],
                'id_guru' => $gm['id_guru'],
                'id_kelas' => $id_kelas,
                'ukuran' => $ukuran_blok
            ];
        }
    }
}

usort($semua_blok, function($a, $b) {
    return $b['ukuran'] - $a['ukuran'];
});

$stmt = mysqli_prepare($koneksi, "INSERT INTO tbl_jadwal (id_waktu, id_kelas, id_guru_mapel) VALUES (?, ?, ?)");

foreach ($semua_blok as $blok) {
    $mapel_info = mysqli_query($koneksi, "SELECT m.nama_mapel FROM tbl_guru_mapel gm JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel WHERE gm.id_guru_mapel = {$blok['id_guru_mapel']}");
    $mapel_data = mysqli_fetch_assoc($mapel_info);
    $nama_mapel = $mapel_data ? $mapel_data['nama_mapel'] : '';

    $berhasil = tryPlaceChunk($blok, $blok['ukuran'], $jadwal_guru_terisi, $jadwal_kelas_terisi, $jadwal_mapel_per_hari, $all_waktu_slots, $stmt, $nama_mapel, $koneksi);

    if (!$berhasil && $blok['ukuran'] > 1) {
        $pecah_darurat = pecah_jam_ke_blok($blok['ukuran']);
        $semua_pecahan_berhasil = true;

        foreach ($pecah_darurat as $ukuran_pecah) {
            $blok_pecah = [
                'id_guru_mapel' => $blok['id_guru_mapel'],
                'id_guru' => $blok['id_guru'],
                'id_kelas' => $blok['id_kelas'],
                'ukuran' => $ukuran_pecah
            ];

            if (!tryPlaceChunk($blok_pecah, $ukuran_pecah, $jadwal_guru_terisi, $jadwal_kelas_terisi, $jadwal_mapel_per_hari, $all_waktu_slots, $stmt, $nama_mapel, $koneksi)) {
                $semua_pecahan_berhasil = false;

                $info_mapel = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT m.nama_mapel, g.nama_guru FROM tbl_guru_mapel gm JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel JOIN tbl_guru g ON gm.id_guru = g.id_guru WHERE gm.id_guru_mapel = {$blok['id_guru_mapel']}"));
                $info_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM tbl_kelas WHERE id_kelas = {$blok['id_kelas']}"));
                $_SESSION['unplaced_blocks'][] = "{$info_mapel['nama_mapel']} ({$info_mapel['nama_guru']}) untuk kelas {$info_kelas['nama_kelas']} - {$ukuran_pecah} jam";
            }
        }

        if ($semua_pecahan_berhasil) {
            $info_mapel = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT m.nama_mapel, g.nama_guru FROM tbl_guru_mapel gm JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel JOIN tbl_guru g ON gm.id_guru = g.id_guru WHERE gm.id_guru_mapel = {$blok['id_guru_mapel']}"));
            $info_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM tbl_kelas WHERE id_kelas = {$blok['id_kelas']}"));
            $_SESSION['emergency_placements'][] = "{$info_mapel['nama_mapel']} ({$info_mapel['nama_guru']}) untuk kelas {$info_kelas['nama_kelas']} dipecah dari blok {$blok['ukuran']} jam";
        }
    } elseif (!$berhasil) {
        $info_mapel = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT m.nama_mapel, g.nama_guru FROM tbl_guru_mapel gm JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel JOIN tbl_guru g ON gm.id_guru = g.id_guru WHERE gm.id_guru_mapel = {$blok['id_guru_mapel']}"));
        $info_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM tbl_kelas WHERE id_kelas = {$blok['id_kelas']}"));
        $_SESSION['unplaced_blocks'][] = "{$info_mapel['nama_mapel']} ({$info_mapel['nama_guru']}) untuk kelas {$info_kelas['nama_kelas']} - {$blok['ukuran']} jam";
    }
}

mysqli_stmt_close($stmt);
header('Location: jadwal.php?status=auto_success');
exit;
?>