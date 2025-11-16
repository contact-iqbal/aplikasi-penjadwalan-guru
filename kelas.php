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
            $query = "INSERT INTO tbl_kelas (nama_kelas) VALUES (?)
                      ON DUPLICATE KEY UPDATE nama_kelas = VALUES(nama_kelas)";
            $stmt = mysqli_prepare($koneksi, $query);
            for ($i = 2; $i <= count($sheetData); $i++) {
                $nama_kelas = trim($sheetData[$i]['A']);
                if (!empty($nama_kelas)) {
                    mysqli_stmt_bind_param($stmt, 's', $nama_kelas);
                    if(mysqli_stmt_execute($stmt)) $sukses++;
                }
            }
            $notification = ['type' => 'success', 'message' => "$sukses data kelas berhasil diimpor/diperbarui."];
        } catch (Exception $e) {
            $notification = ['type' => 'danger', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_kelas = (int)$_GET['id'];

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "DELETE FROM tbl_jadwal WHERE id_kelas = $id_kelas");
        mysqli_query($koneksi, "DELETE FROM tbl_penugasan_kelas WHERE id_kelas = $id_kelas");
        mysqli_query($koneksi, "DELETE FROM tbl_kelas WHERE id_kelas = $id_kelas");

        mysqli_commit($koneksi);
        header('Location: kelas.php?status=deleted');
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header('Location: kelas.php?status=error');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_kelas'])) {
    $nama_kelas = trim($_POST['nama_kelas']);
    if (!empty($nama_kelas)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO tbl_kelas (nama_kelas) VALUES (?) ON DUPLICATE KEY UPDATE nama_kelas = VALUES(nama_kelas)");
        mysqli_stmt_bind_param($stmt, 's', $nama_kelas);
        mysqli_stmt_execute($stmt);
        header('Location: kelas.php?status=success');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_kelas'])) {
    $id_kelas = (int)$_POST['id_kelas'];
    $nama_kelas = trim($_POST['nama_kelas']);
    if ($id_kelas > 0 && !empty($nama_kelas)) {
        $stmt = mysqli_prepare($koneksi, "UPDATE tbl_kelas SET nama_kelas = ? WHERE id_kelas = ?");
        mysqli_stmt_bind_param($stmt, 'si', $nama_kelas, $id_kelas);
        mysqli_stmt_execute($stmt);
        header('Location: kelas.php?status=updated');
        exit;
    }
}

$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_kelas = (int)$_GET['id'];
    $result = mysqli_query($koneksi, "SELECT * FROM tbl_kelas WHERE id_kelas = $id_kelas");
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_data = mysqli_fetch_assoc($result);
    }
}

include 'templates/header.php';

$list_kelas = mysqli_query($koneksi, "SELECT * FROM tbl_kelas ORDER BY nama_kelas ASC");
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home" style="margin-left: 12px;"></i>Dashboard</a></li>
        <li class="breadcrumb-item active">Kelas</li>
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

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Tambah / Edit Data Kelas</h3>
            </div>
            <div class="card-body">
                <form action="kelas.php" method="POST">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_kelas" value="<?php echo $edit_data['id_kelas']; ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Kelas</label>
                                <input type="text" class="form-control" name="nama_kelas" required placeholder="Contoh: X IPA 1" value="<?php echo $edit_data ? htmlspecialchars($edit_data['nama_kelas']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <?php if ($edit_data): ?>
                                <button type="submit" name="update_kelas" class="btn btn-warning btn-block">
                                    <i class="fas fa-edit mr-1"></i> Update
                                </button>
                            <?php else: ?>
                                <button type="submit" name="save_kelas" class="btn btn-success btn-block">
                                    <i class="fas fa-save mr-1"></i> Simpan
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($edit_data): ?>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <a href="kelas.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-times mr-1"></i> Batal
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$edit_data): ?>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Jika nama kelas sudah ada, data akan diperbarui.
                    </small>
                    <?php endif; ?>
                </form>

                <hr class="my-3">

                <form action="kelas.php" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group mb-0">
                                <label>Import dari Excel</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="file_excel" id="fileExcelKelas" required accept=".xlsx, .xls">
                                    <label class="custom-file-label" for="fileExcelKelas">Pilih file Excel...</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="submit" name="import" class="btn btn-primary btn-block">
                                <i class="fas fa-upload mr-1"></i> Import
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <a href="templates/template_kelas.xlsx" class="btn btn-secondary btn-block" download>
                                <i class="fas fa-download mr-1"></i> Template
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="card-title mb-0"><i class="fas fa-list mr-2"></i> Daftar Kelas</h3>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <input type="text" id="searchKelas" class="form-control" placeholder="Cari nama kelas...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nama Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($list_kelas && mysqli_num_rows($list_kelas) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($list_kelas)): ?>
                            <tr class="kelas-row"
                                style="cursor: pointer;"
                                data-id="<?php echo $row['id_kelas']; ?>"
                                data-nama="<?php echo htmlspecialchars($row['nama_kelas']); ?>">
                                <td><strong><?php echo htmlspecialchars($row['nama_kelas']); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td class="text-center">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Belum ada data kelas.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <small class="text-muted">Total: <span id="totalKelas"><?php echo mysqli_num_rows($list_kelas); ?></span> kelas</small>
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

.kelas-row:hover {
    background-color: #f8f9fa;
}

.aside-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9997;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.aside-overlay.active {
    opacity: 1;
    visibility: visible;
}

.preview-popup {
    position: fixed;
    top: 0;
    right: -500px;
    width: 500px;
    height: 100vh;
    background: white;
    box-shadow: -4px 0 20px rgba(0,0,0,0.2);
    z-index: 9998;
    padding: 0;
    overflow-y: auto;
    transition: right 0.3s ease;
}

.preview-popup.active {
    right: 0;
}

.preview-popup .popup-header {
    background: #007bff;
    color: white;
    padding: 20px;
    font-weight: 600;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 10;
}

.preview-popup .popup-header .header-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.preview-popup .popup-header .close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.preview-popup .popup-header .close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.preview-popup .popup-body {
    padding: 20px;
}

.preview-stats {
    display: flex;
    gap: 12px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.stat-badge {
    background: #f8f9fa;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #e9ecef;
    flex: 1;
}

.stat-badge i {
    color: #007bff;
    font-size: 20px;
}

.stat-badge strong {
    color: #495057;
    font-size: 20px;
}

.preview-section {
    margin-bottom: 25px;
}

.preview-section-title {
    font-size: 15px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e9ecef;
}

.preview-section-title i {
    color: #007bff;
    font-size: 16px;
}

.guru-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.guru-item {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 14px;
    border-left: 3px solid #007bff;
    transition: transform 0.2s, box-shadow 0.2s;
}

.guru-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.guru-item .guru-name {
    font-weight: 600;
    color: #495057;
}

.guru-item .guru-mapel {
    color: #6c757d;
    font-size: 13px;
    margin-top: 2px;
}

.jadwal-mini {
    font-size: 13px;
}

.jadwal-mini table {
    width: 100%;
    border-collapse: collapse;
}

.jadwal-mini th {
    background: #f8f9fa;
    padding: 10px 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 13px;
}

.jadwal-mini td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
}

.jadwal-mini tr:hover td {
    background: #f8f9fa;
}

.no-data-preview {
    text-align: center;
    color: #6c757d;
    padding: 40px 20px;
    font-size: 14px;
}

.no-data-preview i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.4;
    display: block;
}

.loading-preview {
    text-align: center;
    padding: 60px 20px;
    color: #007bff;
}

.loading-preview i {
    font-size: 36px;
    animation: spin 1s linear infinite;
    display: block;
    margin-bottom: 12px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div id="contextMenu" class="context-menu">
    <div class="context-menu-item" onclick="editKelas()">
        <i class="fas fa-edit text-warning"></i>
        <span>Edit Kelas</span>
    </div>
    <div class="context-menu-item danger" onclick="deleteKelas()">
        <i class="fas fa-trash"></i>
        <span>Hapus</span>
    </div>
</div>

<div id="asideOverlay" class="aside-overlay"></div>

<div id="previewPopup" class="preview-popup">
    <div class="popup-header">
        <div class="header-title">
            <i class="fas fa-info-circle"></i>
            <span id="previewTitle">Preview Kelas</span>
        </div>
        <button class="close-btn" onclick="closeAside()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="popup-body">
        <div id="previewContent">
            <div class="loading-preview">
                <i class="fas fa-spinner"></i>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('#fileExcelKelas').addEventListener('change', function(e) {
    var fileName = e.target.files[0].name;
    var label = e.target.nextElementSibling;
    label.textContent = fileName;
});

document.getElementById('searchKelas').addEventListener('keyup', function() {
    var input = this.value.toLowerCase();
    var table = document.querySelector('.table tbody');
    var rows = table.getElementsByTagName('tr');
    var visibleCount = 0;

    for (var i = 0; i < rows.length; i++) {
        var namaKelas = rows[i].getElementsByTagName('td')[0];
        if (namaKelas) {
            var textValue = namaKelas.textContent || namaKelas.innerText;
            if (textValue.toLowerCase().indexOf(input) > -1) {
                rows[i].style.display = '';
                visibleCount++;
            } else {
                rows[i].style.display = 'none';
            }
        }
    }

    document.getElementById('totalKelas').textContent = visibleCount;
});

let selectedRow = null;
const contextMenu = document.getElementById('contextMenu');
const previewPopup = document.getElementById('previewPopup');
const asideOverlay = document.getElementById('asideOverlay');
let currentPreviewId = null;

document.addEventListener('contextmenu', function(e) {
    const row = e.target.closest('.kelas-row');
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

// Click functionality on row
const kelasRows = document.querySelectorAll('.kelas-row');

kelasRows.forEach(row => {
    row.addEventListener('click', function(e) {
        const id = this.dataset.id;
        const nama = this.dataset.nama;
        showAside(id, nama);
    });
});

// Close aside when clicking overlay
asideOverlay.addEventListener('click', function() {
    closeAside();
});

function showAside(id, nama) {
    if (currentPreviewId === id && previewPopup.classList.contains('active')) {
        return;
    }

    currentPreviewId = id;

    // Set title
    document.getElementById('previewTitle').textContent = nama;

    // Show loading
    document.getElementById('previewContent').innerHTML = `
        <div class="loading-preview">
            <i class="fas fa-spinner"></i>
            <div style="margin-top: 8px; font-size: 14px;">Memuat data...</div>
        </div>
    `;

    // Show aside and overlay
    previewPopup.classList.add('active');
    asideOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Fetch data
    fetch(`ajax_preview_kelas.php?id_kelas=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPreview(data);
            } else {
                showError(data.error || 'Gagal memuat data');
            }
        })
        .catch(error => {
            showError('Terjadi kesalahan saat memuat data');
        });
}

function closeAside() {
    previewPopup.classList.remove('active');
    asideOverlay.classList.remove('active');
    document.body.style.overflow = '';
    currentPreviewId = null;
}

function renderPreview(data) {
    let html = '';

    // Statistics
    html += '<div class="preview-stats">';
    html += `<div class="stat-badge">
        <i class="fas fa-chalkboard-teacher"></i>
        <span><strong>${data.stats.total_guru || 0}</strong> Guru</span>
    </div>`;
    html += `<div class="stat-badge">
        <i class="fas fa-book"></i>
        <span><strong>${data.stats.total_mapel || 0}</strong> Mapel</span>
    </div>`;
    html += '</div>';

    // Guru List
    html += '<div class="preview-section">';
    html += '<div class="preview-section-title"><i class="fas fa-users"></i> Guru Pengajar</div>';

    if (data.guru_list && data.guru_list.length > 0) {
        html += '<div class="guru-list">';
        data.guru_list.forEach(guru => {
            html += `<div class="guru-item">
                <div>
                    <div class="guru-name">${guru.nama_guru}</div>
                    <div class="guru-mapel">${guru.nama_mapel}</div>
                </div>
            </div>`;
        });
        html += '</div>';
    } else {
        html += '<div class="no-data-preview"><i class="fas fa-user-slash"></i><div>Belum ada guru ditugaskan</div></div>';
    }
    html += '</div>';

    // Jadwal Preview
    html += '<div class="preview-section">';
    html += '<div class="preview-section-title"><i class="fas fa-calendar-alt"></i> Preview Jadwal (20 Teratas)</div>';

    if (data.jadwal_list && data.jadwal_list.length > 0) {
        html += '<div class="jadwal-mini">';
        html += '<table>';
        html += '<thead><tr><th>Hari</th><th>Jam</th><th>Mapel</th><th>Guru</th></tr></thead>';
        html += '<tbody>';
        data.jadwal_list.forEach(jadwal => {
            html += `<tr>
                <td>${jadwal.hari}</td>
                <td>Jam ${jadwal.jam_ke}</td>
                <td>${jadwal.nama_mapel}</td>
                <td>${jadwal.nama_guru}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        html += '</div>';
    } else {
        html += '<div class="no-data-preview"><i class="fas fa-calendar-times"></i><div>Belum ada jadwal tersusun</div></div>';
    }
    html += '</div>';

    document.getElementById('previewContent').innerHTML = html;
}

function showError(message) {
    document.getElementById('previewContent').innerHTML = `
        <div class="no-data-preview">
            <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
            <div>${message}</div>
        </div>
    `;
}

function editKelas() {
    if (!selectedRow) return;

    const id = selectedRow.dataset.id;
    contextMenu.style.display = 'none';
    window.location.href = `kelas.php?action=edit&id=${id}`;
}

function deleteKelas() {
    if (!selectedRow) return;

    const id = selectedRow.dataset.id;
    const nama = selectedRow.dataset.nama;

    contextMenu.style.display = 'none';

    Swal.fire({
        title: 'Hapus Kelas?',
        html: `Yakin ingin menghapus kelas <strong>${nama}</strong>?<br><br>Semua jadwal dan penugasan terkait akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `kelas.php?action=delete&id=${id}`;
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
            text: 'Data kelas berhasil disimpan.',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data kelas berhasil diperbarui.',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'deleted') {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Kelas berhasil dihapus.',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (status === 'error') {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Terjadi kesalahan saat menghapus kelas.'
        });
    }

    if (status) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php include 'templates/footer.php'; ?>
