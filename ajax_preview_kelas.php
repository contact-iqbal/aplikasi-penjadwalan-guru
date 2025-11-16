<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id_kelas'])) {
    echo json_encode(['error' => 'ID kelas tidak ditemukan']);
    exit;
}

$id_kelas = (int)$_GET['id_kelas'];

// Get guru-guru yang mengajar di kelas ini
$query_guru = "
    SELECT DISTINCT
        g.nama_guru,
        mp.nama_mapel,
        COUNT(j.id_jadwal) as total_jam
    FROM tbl_guru g
    INNER JOIN tbl_guru_mapel gm ON g.id_guru = gm.id_guru
    INNER JOIN tbl_penugasan_kelas pk ON gm.id_guru_mapel = pk.id_guru_mapel
    INNER JOIN tbl_mata_pelajaran mp ON gm.id_mapel = mp.id_mapel
    LEFT JOIN tbl_jadwal j ON j.id_guru_mapel = gm.id_guru_mapel AND j.id_kelas = ?
    WHERE pk.id_kelas = ?
    GROUP BY g.id_guru, g.nama_guru, mp.nama_mapel
    ORDER BY g.nama_guru ASC, mp.nama_mapel ASC
";

$stmt = mysqli_prepare($koneksi, $query_guru);
mysqli_stmt_bind_param($stmt, 'ii', $id_kelas, $id_kelas);
mysqli_stmt_execute($stmt);
$result_guru = mysqli_stmt_get_result($stmt);

$guru_list = [];
while ($row = mysqli_fetch_assoc($result_guru)) {
    $guru_list[] = [
        'nama_guru' => $row['nama_guru'],
        'nama_mapel' => $row['nama_mapel'],
        'total_jam' => $row['total_jam']
    ];
}

// Get jadwal kelas (ringkasan per hari)
$query_jadwal = "
    SELECT
        w.hari,
        w.jam_ke,
        w.range_waktu,
        g.nama_guru,
        mp.nama_mapel
    FROM tbl_jadwal j
    INNER JOIN tbl_waktu_pelajaran w ON j.id_waktu = w.id_waktu
    INNER JOIN tbl_guru_mapel gm ON j.id_guru_mapel = gm.id_guru_mapel
    INNER JOIN tbl_guru g ON gm.id_guru = g.id_guru
    INNER JOIN tbl_mata_pelajaran mp ON gm.id_mapel = mp.id_mapel
    WHERE j.id_kelas = ?
    ORDER BY
        FIELD(w.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        w.jam_ke ASC
    LIMIT 20
";

$stmt = mysqli_prepare($koneksi, $query_jadwal);
mysqli_stmt_bind_param($stmt, 'i', $id_kelas);
mysqli_stmt_execute($stmt);
$result_jadwal = mysqli_stmt_get_result($stmt);

$jadwal_list = [];
while ($row = mysqli_fetch_assoc($result_jadwal)) {
    $jadwal_list[] = [
        'hari' => $row['hari'],
        'jam_ke' => $row['jam_ke'],
        'range_waktu' => $row['range_waktu'],
        'nama_guru' => $row['nama_guru'],
        'nama_mapel' => $row['nama_mapel']
    ];
}

// Get statistik
$query_stats = "
    SELECT
        COUNT(DISTINCT j.id_jadwal) as total_jam_terjadwal,
        COUNT(DISTINCT gm.id_guru) as total_guru,
        COUNT(DISTINCT gm.id_mapel) as total_mapel
    FROM tbl_penugasan_kelas pk
    LEFT JOIN tbl_guru_mapel gm ON pk.id_guru_mapel = gm.id_guru_mapel
    LEFT JOIN tbl_jadwal j ON j.id_guru_mapel = gm.id_guru_mapel AND j.id_kelas = ?
    WHERE pk.id_kelas = ?
";

$stmt = mysqli_prepare($koneksi, $query_stats);
mysqli_stmt_bind_param($stmt, 'ii', $id_kelas, $id_kelas);
mysqli_stmt_execute($stmt);
$result_stats = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result_stats);

echo json_encode([
    'success' => true,
    'guru_list' => $guru_list,
    'jadwal_list' => $jadwal_list,
    'stats' => $stats
]);
