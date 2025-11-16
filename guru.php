<?php
require_once 'config/database.php';
require_once 'config/excel_reader.php';

$notification = [];

if (isset($_POST['tambah_guru'])) {
    $nama_guru = trim($_POST['nama_guru']);

    if (!empty($nama_guru)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tbl_guru (nama_guru) VALUES (?)");
        mysqli_stmt_bind_param($stmt, 's', $nama_guru);

        if (mysqli_stmt_execute($stmt)) {
            $notification = ['type' => 'success', 'message' => 'Guru berhasil ditambahkan.'];
        } else {
            $notification = ['type' => 'danger', 'message' => 'Gagal menambahkan guru.'];
        }
    } else {
        $notification = ['type' => 'warning', 'message' => 'Nama guru tidak boleh kosong.'];
    }
}

if (isset($_POST['tambah_mapel'])) {
    $nama_mapel = trim($_POST['nama_mapel']);

    if (!empty($nama_mapel)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tbl_mata_pelajaran (nama_mapel) VALUES (?)");
        mysqli_stmt_bind_param($stmt, 's', $nama_mapel);

        if (mysqli_stmt_execute($stmt)) {
            $notification = ['type' => 'success', 'message' => 'Mata pelajaran berhasil ditambahkan.'];
        } else {
            $notification = ['type' => 'danger', 'message' => 'Gagal menambahkan mata pelajaran.'];
        }
    } else {
        $notification = ['type' => 'warning', 'message' => 'Nama mata pelajaran tidak boleh kosong.'];
    }
}

if (isset($_POST['assign_mapel'])) {
    $id_guru = (int)$_POST['id_guru'];
    $id_mapel = (int)$_POST['id_mapel'];

    if ($id_guru > 0 && $id_mapel > 0) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tbl_guru_mapel (id_guru, id_mapel) VALUES (?, ?) ON DUPLICATE KEY UPDATE id_guru = VALUES(id_guru)");
        mysqli_stmt_bind_param($stmt, 'ii', $id_guru, $id_mapel);

        if (mysqli_stmt_execute($stmt)) {
            $notification = ['type' => 'success', 'message' => 'Penugasan mata pelajaran berhasil disimpan.'];
        } else {
            $notification = ['type' => 'danger', 'message' => 'Gagal menyimpan penugasan.'];
        }
    } else {
        $notification = ['type' => 'warning', 'message' => 'Semua field harus diisi dengan benar.'];
    }
}

if (isset($_POST['import_guru'])) {
    if (isset($_FILES['file_excel_guru']) && $_FILES['file_excel_guru']['error'] == 0) {
        $filePath = $_FILES['file_excel_guru']['tmp_name'];
        try {
            $sheetData = readExcelFile($filePath);
            $sukses = 0;

            mysqli_begin_transaction($koneksi);
            $stmt_guru = mysqli_prepare($koneksi, "INSERT INTO tbl_guru (nama_guru) VALUES (?) ON DUPLICATE KEY UPDATE id_guru=LAST_INSERT_ID(id_guru)");

            for ($i = 2; $i <= count($sheetData); $i++) {
                $nama_guru = trim($sheetData[$i]['A']);
                if (empty($nama_guru)) continue;

                mysqli_stmt_bind_param($stmt_guru, 's', $nama_guru);
                if(mysqli_stmt_execute($stmt_guru)) $sukses++;
            }

            mysqli_commit($koneksi);
            $notification = ['type' => 'success', 'message' => "$sukses guru berhasil diimpor."];

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $notification = ['type' => 'danger', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
}

if (isset($_POST['import_mapel'])) {
    if (isset($_FILES['file_excel_mapel']) && $_FILES['file_excel_mapel']['error'] == 0) {
        $filePath = $_FILES['file_excel_mapel']['tmp_name'];
        try {
            $sheetData = readExcelFile($filePath);
            $sukses = 0;

            mysqli_begin_transaction($koneksi);
            $stmt_mapel = mysqli_prepare($koneksi, "INSERT INTO tbl_mata_pelajaran (nama_mapel) VALUES (?) ON DUPLICATE KEY UPDATE id_mapel=LAST_INSERT_ID(id_mapel)");

            for ($i = 2; $i <= count($sheetData); $i++) {
                $nama_mapel = trim($sheetData[$i]['A']);
                if (empty($nama_mapel)) continue;

                mysqli_stmt_bind_param($stmt_mapel, 's', $nama_mapel);
                if(mysqli_stmt_execute($stmt_mapel)) $sukses++;
            }

            mysqli_commit($koneksi);
            $notification = ['type' => 'success', 'message' => "$sukses mata pelajaran berhasil diimpor."];

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $notification = ['type' => 'danger', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
}

if (isset($_POST['import'])) {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $filePath = $_FILES['file_excel']['tmp_name'];
        try {
            $sheetData = readExcelFile($filePath);
            $sukses = 0;

            mysqli_begin_transaction($koneksi);

            $stmt_guru = mysqli_prepare($koneksi, "INSERT INTO tbl_guru (nama_guru) VALUES (?) ON DUPLICATE KEY UPDATE id_guru=LAST_INSERT_ID(id_guru)");
            $stmt_mapel = mysqli_prepare($koneksi, "INSERT INTO tbl_mata_pelajaran (nama_mapel) VALUES (?) ON DUPLICATE KEY UPDATE id_mapel=LAST_INSERT_ID(id_mapel)");
            $stmt_assign = mysqli_prepare($koneksi, "INSERT INTO tbl_guru_mapel (id_guru, id_mapel) VALUES (?, ?) ON DUPLICATE KEY UPDATE id_guru = VALUES(id_guru)");

            for ($i = 2; $i <= count($sheetData); $i++) {
                $nama_guru = trim($sheetData[$i]['A']);
                $nama_mapel = trim($sheetData[$i]['B']);

                if (empty($nama_guru) || empty($nama_mapel)) continue;

                mysqli_stmt_bind_param($stmt_guru, 's', $nama_guru);
                mysqli_stmt_execute($stmt_guru);
                $id_guru = mysqli_insert_id($koneksi);
                if ($id_guru == 0) {
                     $res = mysqli_query($koneksi, "SELECT id_guru FROM tbl_guru WHERE nama_guru = '".mysqli_real_escape_string($koneksi, $nama_guru)."'");
                     if($res_row = mysqli_fetch_assoc($res)) $id_guru = $res_row['id_guru'];
                }

                mysqli_stmt_bind_param($stmt_mapel, 's', $nama_mapel);
                mysqli_stmt_execute($stmt_mapel);
                $id_mapel = mysqli_insert_id($koneksi);
                if ($id_mapel == 0) {
                     $res = mysqli_query($koneksi, "SELECT id_mapel FROM tbl_mata_pelajaran WHERE nama_mapel = '".mysqli_real_escape_string($koneksi, $nama_mapel)."'");
                     if($res_row = mysqli_fetch_assoc($res)) $id_mapel = $res_row['id_mapel'];
                }

                if ($id_guru > 0 && $id_mapel > 0) {
                    mysqli_stmt_bind_param($stmt_assign, 'ii', $id_guru, $id_mapel);
                    if(mysqli_stmt_execute($stmt_assign)) $sukses++;
                }
            }

            mysqli_commit($koneksi);
            $notification = ['type' => 'success', 'message' => "$sukses data penugasan berhasil diimpor/diperbarui."];

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $notification = ['type' => 'danger', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
}

if (isset($_POST['edit_guru'])) {
    $id_guru = (int)$_POST['id_guru'];
    $nama_guru = trim($_POST['nama_guru']);

    if (!empty($nama_guru) && $id_guru > 0) {
        $stmt = mysqli_prepare($koneksi, "UPDATE tbl_guru SET nama_guru = ? WHERE id_guru = ?");
        mysqli_stmt_bind_param($stmt, 'si', $nama_guru, $id_guru);

        if (mysqli_stmt_execute($stmt)) {
            $notification = ['type' => 'success', 'message' => 'Nama guru berhasil diperbarui.'];
        } else {
            $notification = ['type' => 'danger', 'message' => 'Gagal memperbarui nama guru.'];
        }
    } else {
        $notification = ['type' => 'warning', 'message' => 'Nama guru tidak boleh kosong.'];
    }
}


if (isset($_POST['hapus_penugasan'])) {
    $id_guru_mapel = (int)$_POST['id_guru_mapel'];

    if ($id_guru_mapel > 0) {
        mysqli_begin_transaction($koneksi);
        try {
            mysqli_query($koneksi, "DELETE FROM tbl_penugasan_kelas WHERE id_guru_mapel = $id_guru_mapel");
            mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE id_guru_mapel = $id_guru_mapel");
            mysqli_query($koneksi, "DELETE FROM tbl_guru_mapel WHERE id_guru_mapel = $id_guru_mapel");

            mysqli_commit($koneksi);
            $notification = ['type' => 'success', 'message' => 'Penugasan berhasil dihapus.'];
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $notification = ['type' => 'danger', 'message' => 'Gagal menghapus penugasan.'];
        }
    }
}

if (isset($_POST['edit_mapel'])) {
    $id_mapel = (int)$_POST['id_mapel'];
    $nama_mapel = trim($_POST['nama_mapel']);

    if (!empty($nama_mapel) && $id_mapel > 0) {
        $stmt = mysqli_prepare($koneksi, "UPDATE tbl_mata_pelajaran SET nama_mapel = ? WHERE id_mapel = ?");
        mysqli_stmt_bind_param($stmt, 'si', $nama_mapel, $id_mapel);

        if (mysqli_stmt_execute($stmt)) {
            $notification = ['type' => 'success', 'message' => 'Nama mata pelajaran berhasil diperbarui.'];
        } else {
            $notification = ['type' => 'danger', 'message' => 'Gagal memperbarui nama mata pelajaran.'];
        }
    } else {
        $notification = ['type' => 'warning', 'message' => 'Nama mata pelajaran tidak boleh kosong.'];
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete_mapel' && isset($_GET['id'])) {
    $id_mapel = (int)$_GET['id'];

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "DELETE FROM tbl_penugasan_kelas WHERE id_guru_mapel IN (SELECT id_guru_mapel FROM tbl_guru_mapel WHERE id_mapel = $id_mapel)");
        mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE id_guru_mapel IN (SELECT id_guru_mapel FROM tbl_guru_mapel WHERE id_mapel = $id_mapel)");
        mysqli_query($koneksi, "DELETE FROM tbl_guru_mapel WHERE id_mapel = $id_mapel");
        mysqli_query($koneksi, "DELETE FROM tbl_mata_pelajaran WHERE id_mapel = $id_mapel");

        mysqli_commit($koneksi);
        header('Location: guru.php?status=mapel_deleted');
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header('Location: guru.php?status=error');
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_guru = (int)$_GET['id'];

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "DELETE FROM tbl_penugasan_kelas WHERE id_guru_mapel IN (SELECT id_guru_mapel FROM tbl_guru_mapel WHERE id_guru = $id_guru)");
        mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE id_guru_mapel IN (SELECT id_guru_mapel FROM tbl_guru_mapel WHERE id_guru = $id_guru)");
        mysqli_query($koneksi, "DELETE FROM tbl_guru_mapel WHERE id_guru = $id_guru");
        mysqli_query($koneksi, "DELETE FROM tbl_guru WHERE id_guru = $id_guru");

        mysqli_commit($koneksi);
        header('Location: guru.php?status=deleted');
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header('Location: guru.php?status=error');
    }
    exit;
}

include 'templates/header.php';

$guru_list = mysqli_query($koneksi, "SELECT id_guru, nama_guru FROM tbl_guru ORDER BY nama_guru");
$guru_dropdown = mysqli_query($koneksi, "SELECT id_guru, nama_guru FROM tbl_guru ORDER BY nama_guru");
$mapel_dropdown = mysqli_query($koneksi, "SELECT id_mapel, nama_mapel FROM tbl_mata_pelajaran ORDER BY nama_mapel");
$mapel_list = mysqli_query($koneksi, "SELECT id_mapel, nama_mapel FROM tbl_mata_pelajaran ORDER BY nama_mapel");
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home" style="margin-left: 12px;"></i>Dashboard</a></li>
        <li class="breadcrumb-item active">Guru & Penugasan</li>
    </ol>
</nav>

<section class="content">
    <div class="container-fluid">

        <?php if (!empty($notification)): ?>
            <div class="alert alert-<?php echo $notification['type']; ?> alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-circle-info mr-2"></i>
                <?php echo $notification['message']; ?>
            </div>
        <?php endif; ?>

        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="custom-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#tab_guru" role="tab">
                            <i class="fas fa-user-plus mr-1"></i> Tambah Guru
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab_mapel" role="tab">
                            <i class="fas fa-book mr-1"></i> Kelola Mapel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab_import" role="tab">
                            <i class="fas fa-file-excel mr-1"></i> Import Excel
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab_guru" role="tabpanel">
                        <form action="guru.php" method="post" class="form-inline mb-3">
                            <div class="form-group mr-2" style="flex: 1;">
                                <input type="text" class="form-control w-100" name="nama_guru" placeholder="Nama Guru (contoh: Budi Santoso, S.Pd)" required>
                            </div>
                            <button type="submit" name="tambah_guru" class="btn btn-success">
                                <i class="fas fa-plus mr-1"></i> Tambah
                            </button>
                            <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#importGuruModal">
                                <i class="fas fa-file-excel mr-1"></i> Import Excel
                            </button>
                        </form>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Format Excel untuk Import Guru:</strong> Kolom A berisi Nama Guru (baris pertama adalah header)
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_mapel" role="tabpanel">
                        <form action="guru.php" method="post" class="form-inline mb-3">
                            <div class="form-group mr-2" style="flex: 1;">
                                <input type="text" class="form-control w-100" name="nama_mapel" placeholder="Nama Mata Pelajaran (contoh: Matematika)" required>
                            </div>
                            <button type="submit" name="tambah_mapel" class="btn btn-success">
                                <i class="fas fa-plus mr-1"></i> Tambah Mapel Baru
                            </button>
                            <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#importMapelModal">
                                <i class="fas fa-file-excel mr-1"></i> Import Excel
                            </button>
                        </form>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Format Excel untuk Import Mapel:</strong> Kolom A berisi Nama Mata Pelajaran (baris pertama adalah header)
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Mata Pelajaran</th>
                                        <th>Jumlah Guru yang Mengampu</th>
                                        <th style="width: 200px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($mapel_list && mysqli_num_rows($mapel_list) > 0): ?>
                                    <?php $no = 1; while($m = mysqli_fetch_assoc($mapel_list)): ?>
                                        <?php
                                            $count_guru = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_guru) as total FROM tbl_guru_mapel WHERE id_mapel = {$m['id_mapel']}");
                                            $total_guru = mysqli_fetch_assoc($count_guru)['total'];
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($m['nama_mapel']); ?></strong></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $total_guru; ?> guru</span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-default" data-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editMapelModal<?php echo $m['id_mapel']; ?>">
                                                            <i class="fas fa-edit mr-2"></i> Edit
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="#" onclick="confirmDeleteMapel(<?php echo $m['id_mapel']; ?>, '<?php echo htmlspecialchars($m['nama_mapel'], ENT_QUOTES); ?>'); return false;">
                                                            <i class="fas fa-trash mr-2"></i> Hapus
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted">Belum ada data mata pelajaran.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_import" role="tabpanel">
                        <form action="guru.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Pilih File Excel (.xlsx, .xls)</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="file_excel" id="fileExcel" required accept=".xlsx, .xls">
                                        <label class="custom-file-label" for="fileExcel">Pilih file...</label>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Import file Excel yang berisi data guru, mata pelajaran, dan penugasan
                                </small>
                            </div>
                            <div class="mt-3">
                                <button type="submit" name="import" class="btn btn-primary">
                                    <i class="fas fa-upload mr-1"></i> Import Data
                                </button>
                                <a href="templates/template_guru.xlsx" class="btn btn-secondary" download>
                                    <i class="fas fa-download mr-1"></i> Download Template
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i> Daftar Guru dan Penugasannya</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="searchGuru" class="form-control float-right" placeholder="Cari guru...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover" id="tableGuru">
                    <thead>
                        <tr>
                            <th>Nama Guru</th>
                            <th>Penugasan Mapel</th>
                            <th>Kelas yang Diampu untuk Penugasan ini</th>
                            <th style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if($guru_list && mysqli_num_rows($guru_list) > 0): ?>
                        <?php while($g = mysqli_fetch_assoc($guru_list)): ?>
                            <?php
                                $assignments_query = mysqli_query($koneksi, "
                                    SELECT gm.id_guru_mapel, m.nama_mapel
                                    FROM tbl_guru_mapel gm
                                    JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel
                                    WHERE gm.id_guru = {$g['id_guru']}
                                ");
                                $assignment_count = mysqli_num_rows($assignments_query);
                            ?>
                            <tr>
                                <td rowspan="<?php echo $assignment_count > 0 ? $assignment_count : 1; ?>">
                                    <strong><?php echo htmlspecialchars($g['nama_guru']); ?></strong>
                                </td>
                                <?php if($assignment_count > 0): ?>
                                    <?php $first = true; while($a = mysqli_fetch_assoc($assignments_query)): ?>
                                        <?php if (!$first) echo '<tr>'; ?>
                                        <td>
                                            <div class="mapel-assignment-item"
                                                 data-id="<?php echo $a['id_guru_mapel']; ?>"
                                                 data-guru="<?php echo htmlspecialchars($g['nama_guru']); ?>"
                                                 data-mapel="<?php echo htmlspecialchars($a['nama_mapel']); ?>">
                                                <span class="mapel-name"><?php echo htmlspecialchars($a['nama_mapel']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                                $kelas_ampu_query = mysqli_query($koneksi, "
                                                    SELECT GROUP_CONCAT(k.nama_kelas SEPARATOR ', ') as daftar_kelas
                                                    FROM tbl_penugasan_kelas pk
                                                    JOIN tbl_kelas k ON pk.id_kelas = k.id_kelas
                                                    WHERE pk.id_guru_mapel = {$a['id_guru_mapel']}
                                                ");
                                                $kelas_ampu = mysqli_fetch_assoc($kelas_ampu_query)['daftar_kelas'];
                                                echo !empty($kelas_ampu) ? htmlspecialchars($kelas_ampu) : '<span class="text-muted"><i>Semua Kelas</i></span>';
                                            ?>
                                            <a href="penugasan_kelas.php?id_guru_mapel=<?php echo $a['id_guru_mapel']; ?>" class="btn btn-xs btn-info mt-1">
                                                <i class="fas fa-pen-to-square"></i> Kelola Kelas
                                            </a>
                                        </td>
                                        <?php if ($first): ?>
                                        <td rowspan="<?php echo $assignment_count; ?>">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-default" data-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editGuruModal<?php echo $g['id_guru']; ?>">
                                                        <i class="fas fa-edit mr-2"></i> Edit Nama
                                                    </a>
                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#tambahPenugasanModal<?php echo $g['id_guru']; ?>">
                                                        <i class="fas fa-plus mr-2"></i> Tambah Penugasan
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#" onclick="confirmDeleteGuru(<?php echo $g['id_guru']; ?>, '<?php echo htmlspecialchars($g['nama_guru'], ENT_QUOTES); ?>'); return false;">
                                                        <i class="fas fa-trash mr-2"></i> Hapus
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <?php $first = false; endif; ?>
                                        <?php echo '</tr>'; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <td colspan="2" style="text-align:center;">
                                        <span class="text-muted"><i>Belum ada mapel ditugaskan</i></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-default" data-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editGuruModal<?php echo $g['id_guru']; ?>">
                                                    <i class="fas fa-edit mr-2"></i> Edit Nama
                                                </a>
                                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#tambahPenugasanModal<?php echo $g['id_guru']; ?>">
                                                    <i class="fas fa-plus mr-2"></i> Tambah Penugasan
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#" onclick="confirmDeleteGuru(<?php echo $g['id_guru']; ?>, '<?php echo htmlspecialchars($g['nama_guru'], ENT_QUOTES); ?>'); return false;">
                                                    <i class="fas fa-trash mr-2"></i> Hapus
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Belum ada data guru. Silakan tambah guru atau import dari Excel.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<?php
$guru_list_modal = mysqli_query($koneksi, "SELECT id_guru, nama_guru FROM tbl_guru ORDER BY nama_guru");
while($g = mysqli_fetch_assoc($guru_list_modal)):
?>
<div class="modal fade" id="editGuruModal<?php echo $g['id_guru']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Nama Guru</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="guru.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_guru" value="<?php echo $g['id_guru']; ?>">
                    <div class="form-group">
                        <label>Nama Guru</label>
                        <input type="text" class="form-control" name="nama_guru" value="<?php echo htmlspecialchars($g['nama_guru']); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_guru" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php
$mapel_list_modal = mysqli_query($koneksi, "SELECT id_mapel, nama_mapel FROM tbl_mata_pelajaran ORDER BY nama_mapel");
while($m = mysqli_fetch_assoc($mapel_list_modal)):
?>
<div class="modal fade" id="editMapelModal<?php echo $m['id_mapel']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Mata Pelajaran</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="guru.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_mapel" value="<?php echo $m['id_mapel']; ?>">
                    <div class="form-group">
                        <label>Nama Mata Pelajaran</label>
                        <input type="text" class="form-control" name="nama_mapel" value="<?php echo htmlspecialchars($m['nama_mapel']); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_mapel" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php
$penugasan_list_modal = mysqli_query($koneksi, "
    SELECT gm.id_guru_mapel, g.nama_guru, m.nama_mapel
    FROM tbl_guru_mapel gm
    JOIN tbl_guru g ON gm.id_guru = g.id_guru
    JOIN tbl_mata_pelajaran m ON gm.id_mapel = m.id_mapel
");
while($p = mysqli_fetch_assoc($penugasan_list_modal)):
?>
<div class="modal fade" id="editPenugasanModal<?php echo $p['id_guru_mapel']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Penugasan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="guru.php" method="post" id="formEditPenugasan<?php echo $p['id_guru_mapel']; ?>">
                <div class="modal-body">
                    <input type="hidden" name="id_guru_mapel" value="<?php echo $p['id_guru_mapel']; ?>">
                    <div class="form-group">
                        <label>Guru</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p['nama_guru']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Mata Pelajaran</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($p['nama_mapel']); ?>" disabled>
                    </div>
                    <p class="text-muted"><i class="fas fa-info-circle"></i> Yakin ingin menghapus penugasan ini? Semua jadwal dan penugasan kelas terkait akan dihapus.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="hapus_penugasan" class="btn btn-danger"><i class="fas fa-trash mr-1"></i> Hapus Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php
$guru_list_tambah_penugasan = mysqli_query($koneksi, "SELECT id_guru, nama_guru FROM tbl_guru ORDER BY nama_guru");
while($g = mysqli_fetch_assoc($guru_list_tambah_penugasan)):
?>
<div class="modal fade" id="tambahPenugasanModal<?php echo $g['id_guru']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Penugasan untuk <?php echo htmlspecialchars($g['nama_guru']); ?></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="guru.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_guru" value="<?php echo $g['id_guru']; ?>">
                    <div class="form-group">
                        <label>Mata Pelajaran</label>
                        <select class="form-control" name="id_mapel" required>
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php
                            $mapel_for_modal = mysqli_query($koneksi, "SELECT id_mapel, nama_mapel FROM tbl_mata_pelajaran ORDER BY nama_mapel");
                            while($m = mysqli_fetch_assoc($mapel_for_modal)):
                            ?>
                                <option value="<?php echo $m['id_mapel']; ?>"><?php echo htmlspecialchars($m['nama_mapel']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="assign_mapel" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<div class="modal fade" id="importGuruModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Data Guru dari Excel</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="guru.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih File Excel (.xlsx, .xls)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file_excel_guru" id="fileExcelGuru" required accept=".xlsx, .xls">
                            <label class="custom-file-label" for="fileExcelGuru">Pilih file...</label>
                        </div>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle"></i> Format: Kolom A = Nama Guru (baris 1 adalah header)
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="import_guru" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importMapelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Data Mata Pelajaran dari Excel</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="guru.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih File Excel (.xlsx, .xls)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file_excel_mapel" id="fileExcelMapel" required accept=".xlsx, .xls">
                            <label class="custom-file-label" for="fileExcelMapel">Pilih file...</label>
                        </div>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle"></i> Format: Kolom A = Nama Mata Pelajaran (baris 1 adalah header)
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="import_mapel" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.mapel-assignment-item {
    cursor: context-menu;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s ease;
    display: inline-block;
    min-width: 150px;
}

.mapel-assignment-item:hover {
    background-color: #f8f9fa;
}

.context-menu {
    position: fixed;
    background: white;
    border: 1px solid #ddd;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    border-radius: 4px;
    padding: 4px 0;
    z-index: 9999;
    min-width: 180px;
    display: none;
}

.context-menu-item {
    padding: 8px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.15s ease;
}

.context-menu-item:hover {
    background-color: #f8f9fa;
}

.context-menu-item i {
    width: 16px;
}

.context-menu-item.danger {
    color: #dc3545;
}

.context-menu-item.danger:hover {
    background-color: #fff5f5;
}
</style>

<div id="contextMenu" class="context-menu">
    <div class="context-menu-item danger" id="deleteAssignment">
        <i class="fas fa-trash"></i>
        <span>Hapus Penugasan</span>
    </div>
</div>

<script>
var fileInputs = document.querySelectorAll('.custom-file-input');
fileInputs.forEach(function(input) {
    input.addEventListener('change', function(e) {
        var fileName = e.target.files[0].name;
        var label = e.target.nextElementSibling;
        label.textContent = fileName;
    });
});

document.getElementById('searchGuru').addEventListener('keyup', function() {
    var searchValue = this.value.toLowerCase();
    var table = document.getElementById('tableGuru');
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var namaGuru = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';

        if (namaGuru.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
});

var contextMenu = document.getElementById('contextMenu');
var currentAssignment = null;

document.addEventListener('contextmenu', function(e) {
    const item = e.target.closest('.mapel-assignment-item');
    if (item) {
        e.preventDefault();

        currentAssignment = {
            id: item.getAttribute('data-id'),
            guru: item.getAttribute('data-guru'),
            mapel: item.getAttribute('data-mapel')
        };

        contextMenu.style.display = 'block';
        contextMenu.style.left = (e.clientX + 2) + 'px';
        contextMenu.style.top = (e.clientY + 2) + 'px';
    }
});

document.getElementById('deleteAssignment').addEventListener('click', function() {
    if (currentAssignment) {
        Swal.fire({
            title: 'Hapus Penugasan?',
            html: 'Hapus penugasan <strong>' + currentAssignment.mapel + '</strong> untuk <strong>' + currentAssignment.guru + '</strong>?<br><br>Semua jadwal dan penugasan kelas terkait akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'guru.php';

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id_guru_mapel';
                input.value = currentAssignment.id;
                form.appendChild(input);

                var submit = document.createElement('input');
                submit.type = 'hidden';
                submit.name = 'hapus_penugasan';
                submit.value = '1';
                form.appendChild(submit);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    contextMenu.style.display = 'none';
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.context-menu') && !e.target.closest('.mapel-assignment-item')) {
        contextMenu.style.display = 'none';
    }
});

document.addEventListener('scroll', function() {
    contextMenu.style.display = 'none';
});

function confirmDeleteGuru(id, nama) {
    Swal.fire({
        title: 'Hapus Guru?',
        html: 'Yakin ingin menghapus guru <strong>' + nama + '</strong>?<br><br>Semua jadwal dan penugasan terkait akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'guru.php?action=delete&id=' + id;
        }
    });
}

function confirmDeleteMapel(id, nama) {
    Swal.fire({
        title: 'Hapus Mata Pelajaran?',
        html: 'Yakin ingin menghapus mata pelajaran <strong>' + nama + '</strong>?<br><br>Semua penugasan dan jadwal terkait akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'guru.php?action=delete_mapel&id=' + id;
        }
    });
}
</script>

<?php include 'templates/footer.php'; ?>
