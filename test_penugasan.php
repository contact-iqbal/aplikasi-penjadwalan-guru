<?php
require_once 'config/database.php';

echo "<h2>Test Penugasan Kelas</h2>";

// Cek data guru_mapel
echo "<h3>Data Guru-Mapel yang tersedia:</h3>";
$result = mysqli_query($koneksi, "
    SELECT gm.id_guru_mapel, g.nama_guru, m.nama_mapel
    FROM tbl_guru_mapel gm
    JOIN tbl_guru g ON gm.id_guru = g.id_guru
    JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel
    ORDER BY g.nama_guru, m.nama_mapel
");

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Guru</th><th>Mapel</th><th>Link</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $link = "penugasan_kelas.php?id_guru_mapel=" . $row['id_guru_mapel'];
        echo "<tr>";
        echo "<td>" . $row['id_guru_mapel'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_guru']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_mapel']) . "</td>";
        echo "<td><a href='" . $link . "' target='_blank'>Buka</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Belum ada data guru-mapel. Silakan tambahkan data guru dan assign ke mata pelajaran terlebih dahulu.</p>";
}

// Cek data kelas
echo "<h3>Data Kelas yang tersedia:</h3>";
$kelas_result = mysqli_query($koneksi, "SELECT id_kelas, nama_kelas FROM tbl_kelas ORDER BY nama_kelas");
if ($kelas_result && mysqli_num_rows($kelas_result) > 0) {
    echo "<ul>";
    while ($kelas = mysqli_fetch_assoc($kelas_result)) {
        echo "<li>" . htmlspecialchars($kelas['nama_kelas']) . " (ID: " . $kelas['id_kelas'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Belum ada data kelas.</p>";
}

echo "<hr>";
echo "<p><a href='guru.php'>Kembali ke Guru</a> | <a href='index.php'>Dashboard</a></p>";
?>
