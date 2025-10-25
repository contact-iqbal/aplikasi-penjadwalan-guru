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
?>

<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Dashboard</h1>
    </div>
</div>

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
                    <a href="susun_jadwal.php" class="small-box-footer">Susun <i class="fas fa-arrow-circle-right"></i></a>
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
                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Langkah 1</span>
                                        <span class="info-box-number">Input Data Master</span>
                                        <a href="guru.php" class="small">Kelola Data &raquo;</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-lock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Langkah 2</span>
                                        <span class="info-box-number">Kunci Jadwal</span>
                                        <a href="jadwal_wajib.php" class="small">Kelola Kunci &raquo;</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-success"><i class="fas fa-calendar-plus"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Langkah 3</span>
                                        <span class="info-box-number">Susun Jadwal</span>
                                        <a href="susun_jadwal.php" class="small">Buat Jadwal &raquo;</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-print"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Langkah 4</span>
                                        <span class="info-box-number">Lihat & Cetak</span>
                                        <a href="lihat_jadwal.php" class="small">Lihat Hasil &raquo;</a>
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

<?php
include 'templates/footer.php';
?>
