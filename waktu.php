<?php
require_once 'config/database.php';
require_once 'config/excel_reader.php';

$notification = [];

if (isset($_POST['import'])) {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $filePath = $_FILES['file_excel']['tmp_name'];
        try {
            $sheetData = readExcelFile($filePath);
            $sukses = 0;
            $query = "INSERT INTO tbl_waktu_pelajaran (hari, jam_ke, range_waktu) VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE range_waktu = VALUES(range_waktu)";
            $stmt = mysqli_prepare($koneksi, $query);
            for ($i = 2; $i <= count($sheetData); $i++) {
                $hari = trim($sheetData[$i]['A']);
                $jam_ke = (int)trim($sheetData[$i]['B']);
                $range = trim($sheetData[$i]['C']);
                if (!empty($hari) && $jam_ke > 0 && !empty($range)) {
                    mysqli_stmt_bind_param($stmt, 'sis', $hari, $jam_ke, $range);
                    if(mysqli_stmt_execute($stmt)) $sukses++;
                }
            }
            $notification = ['type' => 'success', 'message' => "$sukses data waktu berhasil diimpor/diperbarui."];
        } catch (Exception $e) {
            $notification = ['type' => 'danger', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_waktu = (int)$_GET['id'];

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE id_waktu = $id_waktu");
        mysqli_query($koneksi, "DELETE FROM tbl_waktu_pelajaran WHERE id_waktu = $id_waktu");

        mysqli_commit($koneksi);
        header('Location: waktu.php?status=deleted');
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header('Location: waktu.php?status=error');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_waktu'])) {
    $hari = trim($_POST['hari']);
    $jam_ke = (int)$_POST['jam_ke'];
    $range_waktu = trim($_POST['range_waktu']);
    if (!empty($hari) && $jam_ke > 0 && !empty($range_waktu)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tbl_waktu_pelajaran (hari, jam_ke, range_waktu) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE range_waktu = VALUES(range_waktu)");
        mysqli_stmt_bind_param($stmt, 'sis', $hari, $jam_ke, $range_waktu);
        mysqli_stmt_execute($stmt);
        header('Location: waktu.php?status=success');
        exit;
    }
}

include 'templates/header.php';

$list_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$order_hari = "FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')";

// Handle sorting
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$sort_order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';

if ($sort_by == 'hari') {
    if ($sort_order == 'asc') {
        $order_sql = "$order_hari, jam_ke ASC";
    } else {
        $order_sql = "$order_hari DESC, jam_ke ASC";
    }
} else {
    $order_sql = "$order_hari, jam_ke ASC";
}

$list_waktu = mysqli_query($koneksi, "SELECT * FROM tbl_waktu_pelajaran ORDER BY $order_sql");

$waktu_by_hari = [];
if ($list_waktu && mysqli_num_rows($list_waktu) > 0) {
    while($row = mysqli_fetch_assoc($list_waktu)) {
        $waktu_by_hari[$row['hari']][] = $row;
    }
}
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home" style="margin-left: 12px;"></i>Dashboard</a></li>
        <li class="breadcrumb-item active">Waktu Pelajaran</li>
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

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Tambah Data Slot Waktu</h3>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="inputTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="manual-tab" data-toggle="tab" href="#manual" role="tab">
                            <i class="fas fa-pen-to-square mr-2"></i> Input Manual
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="excel-tab" data-toggle="tab" href="#excel" role="tab">
                            <i class="fas fa-file-excel mr-2"></i> Import Excel
                        </a>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="inputTabsContent">
                    <div class="tab-pane fade show active" id="manual" role="tabpanel">
                        <form action="waktu.php" method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Hari</label>
                                        <select name="hari" class="form-control" required>
                                            <?php foreach($list_hari as $h) echo "<option value='$h'>$h</option>"; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Jam Ke-</label>
                                        <input type="number" class="form-control" name="jam_ke" required min="1" placeholder="Contoh: 1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Rentang Waktu</label>
                                        <input type="text" class="form-control" name="range_waktu" required placeholder="Contoh: 07:00 - 07:45">
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-3">
                                <i class="fas fa-info-circle"></i> Jika hari dan jam ke- sudah ada, data akan diperbarui.
                            </small>
                            <button type="submit" name="save_waktu" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="excel" role="tabpanel">
                        <form action="waktu.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Pilih File Excel (.xlsx, .xls)</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="file_excel" id="fileExcelWaktu" required accept=".xlsx, .xls">
                                        <label class="custom-file-label" for="fileExcelWaktu">Pilih file...</label>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle"></i> Format file harus sesuai dengan template yang disediakan.
                                </small>
                            </div>
                            <button type="submit" name="import" class="btn btn-primary">
                                <i class="fas fa-upload mr-1"></i> Import
                            </button>
                            <a href="templates/template_waktu.xlsx" class="btn btn-secondary" download>
                                <i class="fas fa-download mr-1"></i> Download Template
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i> Daftar Slot Waktu</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-sm" style="border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            <th style="width: 120px; cursor: pointer; user-select: none;" onclick="toggleSort('hari')">
                                Hari
                                <?php
                                if ($sort_by == 'hari') {
                                    echo $sort_order == 'asc' ? '▲' : '▼';
                                } else {
                                    echo '<i class="fas fa-sort text-muted" style="font-size: 0.8em;"></i>';
                                }
                                ?>
                            </th>
                            <th>Jam Ke-</th>
                            <th>Rentang Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(!empty($waktu_by_hari)): ?>
                        <?php
                        $hari_colors = [
                            'Senin' => '#f8f9fa',
                            'Selasa' => '#e3f2fd',
                            'Rabu' => '#fff3e0',
                            'Kamis' => '#f3e5f5',
                            'Jumat' => '#e8f5e9',
                            'Sabtu' => '#fce4ec',
                            'Minggu' => '#fff8e1'
                        ];

                        $display_hari = $sort_order == 'desc' && $sort_by == 'hari' ? array_reverse($list_hari) : $list_hari;
                        $first_hari_loop = true;
                        ?>
                        <?php foreach($display_hari as $hari): ?>
                            <?php if(isset($waktu_by_hari[$hari])): ?>
                                <?php
                                if (!$first_hari_loop) {
                                    echo '<tr style="height: 12px; background: #dee2e6;"><td colspan="4"></td></tr>';
                                }
                                $first_hari_loop = false;
                                ?>
                                <?php $first = true; ?>
                                <?php foreach($waktu_by_hari[$hari] as $row): ?>
                                    <tr class="waktu-row"
                                        style="background-color: <?php echo $hari_colors[$hari]; ?>; cursor: context-menu;"
                                        data-id="<?php echo $row['id_waktu']; ?>"
                                        data-hari="<?php echo htmlspecialchars($hari); ?>"
                                        data-jam="<?php echo $row['jam_ke']; ?>"
                                        data-range="<?php echo htmlspecialchars($row['range_waktu']); ?>">
                                        <?php if($first): ?>
                                            <td rowspan="<?php echo count($waktu_by_hari[$hari]); ?>" class="align-middle font-weight-bold" style="background-color: <?php echo $hari_colors[$hari]; ?>; border-left: 4px solid #6c757d; padding-left: 12px;">
                                                <?php echo htmlspecialchars($hari); ?>
                                            </td>
                                            <?php $first = false; ?>
                                        <?php endif; ?>
                                        <td><span class="badge badge-secondary" style="background-color: #6c757d;">Jam <?php echo $row['jam_ke']; ?></span></td>
                                        <td><?php echo htmlspecialchars($row['range_waktu']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Belum ada data waktu.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<style>
.context-menu {
    position: fixed;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 9999;
    min-width: 180px;
    display: none;
}

.context-menu-item {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.context-menu-item:last-child {
    border-bottom: none;
}

.context-menu-item:hover {
    background-color: #f5f5f5;
}

.context-menu-item i {
    margin-right: 10px;
    width: 16px;
}

.context-menu-item.danger:hover {
    background-color: #fff5f5;
    color: #dc3545;
}

.waktu-row:hover {
    opacity: 0.9;
}
</style>

<div id="contextMenu" class="context-menu">
    <div class="context-menu-item" onclick="editWaktu()">
        <i class="fas fa-edit text-primary"></i>
        <span>Edit Rentang Jam</span>
    </div>
    <div class="context-menu-item danger" onclick="deleteWaktu()">
        <i class="fas fa-trash"></i>
        <span>Hapus</span>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Slot Waktu</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editForm" method="POST" action="waktu.php">
                <div class="modal-body">
                    <input type="hidden" name="save_waktu" value="1">
                    <div class="form-group">
                        <label>Hari</label>
                        <input type="text" class="form-control" id="editHari" name="hari" readonly>
                    </div>
                    <div class="form-group">
                        <label>Jam Ke-</label>
                        <input type="number" class="form-control" id="editJam" name="jam_ke" readonly>
                    </div>
                    <div class="form-group">
                        <label>Rentang Waktu</label>
                        <input type="text" class="form-control" id="editRange" name="range_waktu" required placeholder="Contoh: 07:00 - 07:45">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('#fileExcelWaktu').addEventListener('change', function(e) {
    var fileName = e.target.files[0].name;
    var label = e.target.nextElementSibling;
    label.textContent = fileName;
});

function toggleSort(column) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort');
    const currentOrder = urlParams.get('order');

    let newOrder = 'asc';
    if (currentSort === column && currentOrder === 'asc') {
        newOrder = 'desc';
    }

    urlParams.set('sort', column);
    urlParams.set('order', newOrder);
    window.location.search = urlParams.toString();
}

let selectedRow = null;
const contextMenu = document.getElementById('contextMenu');

document.addEventListener('contextmenu', function(e) {
    const row = e.target.closest('.waktu-row');
    if (row) {
        e.preventDefault();
        selectedRow = row;

        contextMenu.style.display = 'block';
        contextMenu.style.left = (e.clientX + 2) + 'px';
        contextMenu.style.top = (e.clientY + 2) + 'px';
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.context-menu')) {
        contextMenu.style.display = 'none';
    }
});

document.addEventListener('scroll', function() {
    contextMenu.style.display = 'none';
});

function editWaktu() {
    if (!selectedRow) return;

    const hari = selectedRow.dataset.hari;
    const jam = selectedRow.dataset.jam;
    const range = selectedRow.dataset.range;

    document.getElementById('editHari').value = hari;
    document.getElementById('editJam').value = jam;
    document.getElementById('editRange').value = range;

    $('#editModal').modal('show');
    contextMenu.style.display = 'none';
}

function deleteWaktu() {
    if (!selectedRow) return;

    const id = selectedRow.dataset.id;
    const hari = selectedRow.dataset.hari;
    const jam = selectedRow.dataset.jam;

    contextMenu.style.display = 'none';

    Swal.fire({
        title: 'Hapus Slot Waktu?',
        html: `Yakin ingin menghapus slot waktu <strong>${hari} Jam ${jam}</strong>?<br><br>Semua jadwal terkait akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `waktu.php?action=delete&id=${id}`;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data waktu pelajaran berhasil disimpan.',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'deleted') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Slot waktu berhasil dihapus.',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'error') {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Terjadi kesalahan saat menghapus slot waktu.'
        });
    }

    if (status) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php include 'templates/footer.php'; ?>
