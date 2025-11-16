<?php
require_once 'config/database.php';
include 'templates/header.php';

$total_guru = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_guru) AS total FROM tbl_guru"))['total'] ?? 0;
$total_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_kelas) AS total FROM tbl_kelas"))['total'] ?? 0;
$total_mapel = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_mapel) AS total FROM tbl_mata_pelajaran"))['total'] ?? 0;
$total_waktu = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_waktu) AS total FROM tbl_waktu_pelajaran"))['total'] ?? 0;
$total_jadwal_terisi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_jadwal) AS total FROM tbl_jadwal"))['total'] ?? 0;
$total_jadwal_terkunci = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_wajib) AS total FROM tbl_jadwal_wajib"))['total'] ?? 0;

$total_slot_tersedia = $total_kelas * $total_waktu;
$persentase_terisi = $total_slot_tersedia > 0 ? round(($total_jadwal_terisi / $total_slot_tersedia) * 100, 1) : 0;

$jadwal_per_hari = mysqli_query($koneksi, "
    SELECT w.hari, COUNT(j.id_jadwal) as total
    FROM tbl_waktu_pelajaran w
    LEFT JOIN tbl_jadwal j ON w.id_waktu = j.id_waktu
    GROUP BY w.hari
    ORDER BY FIELD(w.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')
");
$hari_labels = [];
$hari_data = [];
while ($row = mysqli_fetch_assoc($jadwal_per_hari)) {
    $hari_labels[] = $row['hari'];
    $hari_data[] = $row['total'];
}

$mapel_populer = mysqli_query($koneksi, "
    SELECT m.nama_mapel, COUNT(j.id_jadwal) as total
    FROM tbl_mata_pelajaran m
    LEFT JOIN tbl_guru_mapel gm ON m.id_mapel = gm.id_mapel
    LEFT JOIN tbl_jadwal j ON gm.id_guru_mapel = j.id_guru_mapel
    GROUP BY m.id_mapel
    ORDER BY total DESC
    LIMIT 5
");
$mapel_labels = [];
$mapel_data = [];
while ($row = mysqli_fetch_assoc($mapel_populer)) {
    $mapel_labels[] = $row['nama_mapel'];
    $mapel_data[] = $row['total'];
}

$beban_guru = mysqli_query($koneksi, "
    SELECT g.nama_guru, COUNT(j.id_jadwal) as total_jam
    FROM tbl_guru g
    LEFT JOIN tbl_guru_mapel gm ON g.id_guru = gm.id_guru
    LEFT JOIN tbl_jadwal j ON gm.id_guru_mapel = j.id_guru_mapel
    GROUP BY g.id_guru
    ORDER BY total_jam DESC
    LIMIT 10
");
$guru_labels = [];
$guru_data = [];
while ($row = mysqli_fetch_assoc($beban_guru)) {
    $guru_labels[] = $row['nama_guru'];
    $guru_data[] = $row['total_jam'];
}
?>

<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $total_guru; ?></h3>
                        <p>Total Guru</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                    <a href="guru.php" class="small-box-footer">Kelola <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $total_kelas; ?></h3>
                        <p>Total Kelas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <a href="kelas.php" class="small-box-footer">Kelola <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $total_mapel; ?></h3>
                        <p>Total Mata Pelajaran</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book-open-reader"></i>
                    </div>
                    <a href="guru.php" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $total_jadwal_terisi; ?></h3>
                        <p>Slot Terisi</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <a href="jadwal.php" class="small-box-footer">Susun <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i> Progress Jadwal</h3>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; width: 100%; max-height: 250px;">
                            <canvas id="progressChart"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <h4><?php echo $persentase_terisi; ?>%</h4>
                            <p class="text-muted">Slot Terisi dari Total <?php echo $total_slot_tersedia; ?> Slot</p>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?php echo $persentase_terisi; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 d-flex">
                <div class="card container-fluid">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i> Distribusi Jadwal per Hari</h3>
                    </div>
                    <div class="card-body" style="display: flex;">
                        <div style="position: relative; width: 100%;">
                            <canvas id="hariChart" class=""></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book mr-2"></i> Top 5 Mata Pelajaran Terbanyak</h3>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; width: 100%; max-height: 300px;">
                            <canvas id="mapelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chalkboard-user mr-2"></i> Beban Mengajar Guru (Top 10)</h3>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; width: 100%; max-height: 300px;">
                            <canvas id="guruChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Panduan Memulai</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Langkah 1</span>
                                        <span class="info-box-number">Input Data Master</span>
                                        <a href="guru.php" class="small">Kelola Data &raquo;</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-lock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Langkah 2</span>
                                        <span class="info-box-number">Kunci Jadwal</span>
                                        <a href="jadwal_wajib.php" class="small">Kelola Kunci &raquo;</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-success"><i class="fas fa-calendar-plus"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Langkah 3</span>
                                        <span class="info-box-number">Kelola Jadwal</span>
                                        <a href="jadwal.php" class="small">Susun & Lihat &raquo;</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartColors = {
            primary: '#007bff',
            success: '#28a745',
            danger: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8',
            secondary: '#6c757d'
        };

        const progressCanvas = document.getElementById('progressChart');
        if (progressCanvas) {
            const progressCtx = progressCanvas.getContext('2d');
            new Chart(progressCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Terisi', 'Kosong', 'Terkunci'],
                    datasets: [{
                        data: [
                            <?php echo max(0, $total_jadwal_terisi - $total_jadwal_terkunci); ?>,
                            <?php echo max(0, $total_slot_tersedia - $total_jadwal_terisi); ?>,
                            <?php echo max(0, $total_jadwal_terkunci); ?>
                        ],
                        backgroundColor: [chartColors.success, '#e9ecef', chartColors.warning],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        const hariCanvas = document.getElementById('hariChart');
        if (hariCanvas) {
            const hariCtx = hariCanvas.getContext('2d');
            new Chart(hariCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($hari_labels); ?>,
                    datasets: [{
                        label: 'Jumlah Jadwal',
                        data: <?php echo json_encode($hari_data); ?>,
                        backgroundColor: chartColors.primary,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        const mapelCanvas = document.getElementById('mapelChart');
        if (mapelCanvas) {
            const mapelCtx = mapelCanvas.getContext('2d');
            new Chart(mapelCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($mapel_labels); ?>,
                    datasets: [{
                        label: 'Jumlah Jadwal',
                        data: <?php echo json_encode($mapel_data); ?>,
                        backgroundColor: [
                            chartColors.info,
                            chartColors.success,
                            chartColors.warning,
                            chartColors.danger,
                            chartColors.secondary
                        ],
                        borderRadius: 5
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        const guruCanvas = document.getElementById('guruChart');
        if (guruCanvas) {
            const guruCtx = guruCanvas.getContext('2d');
            new Chart(guruCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($guru_labels); ?>,
                    datasets: [{
                        label: 'Jam Mengajar',
                        data: <?php echo json_encode($guru_data); ?>,
                        backgroundColor: chartColors.success,
                        borderRadius: 5
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
include 'templates/footer.php';
?>