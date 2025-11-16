<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Penjadwalan Sekolah</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .content-wrapper {
            background: #f4f6f9;
        }
        .brand-link {
            font-size: 1.25rem;
            font-weight: 500;
        }
        .main-sidebar {
            background: #343a40;
        }
        .nav-sidebar .nav-link {
            color: #c2c7d0;
        }
        .nav-sidebar .nav-link:hover {
            background: rgba(255,255,255,.1);
            color: #fff;
        }
        .nav-sidebar .nav-link.active {
            background: #007bff;
            color: #fff;
        }
        .nav-treeview > .nav-item > .nav-link {
            padding-left: 1rem;
        }
        .nav-treeview > .nav-item > .nav-link .nav-icon {
            margin-left: 0;
            margin-right: 0.5rem;
        }
        .nav-treeview > .nav-item > .nav-link.active {
            background: #495057;
        }
        .small-box {
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        .small-box .icon {
            font-size: 70px;
        }
        .card {
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        .table thead th {
            border-bottom: 2px solid #dee2e6;
        }
        .timeline-item {
            padding-left: 0;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin: 0;
            border-left: 3px solid #007bff;
            padding-left: 15px;
            font-size: 0.95rem;
        }
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #6c757d;
            font-size: 1.2rem;
            padding: 0 8px;
        }
        .breadcrumb-item a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .breadcrumb-item a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .breadcrumb-item.active {
            color: #6c757d;
            font-weight: 500;
        }
        .breadcrumb-item i.fa-home {
            margin-right: 6px;
        }
        @media print {
            .main-sidebar, .main-header, .content-header, .no-print {
                display: none !important;
            }
            .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="index.php" class="brand-link">
            <i class="fas fa-calendar-days ml-3 mr-2"></i>
            <span class="brand-text font-weight-light">Penjadwalan</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $data_master_pages = ['guru.php', 'kelas.php', 'waktu.php'];
                $penjadwalan_pages = ['jadwal_wajib.php', 'susun_jadwal.php', 'lihat_jadwal.php', 'jadwal.php'];
                $is_data_master_active = in_array($current_page, $data_master_pages);
                $is_penjadwalan_active = in_array($current_page, $penjadwalan_pages);
                ?>
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-item <?php echo $is_data_master_active ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo $is_data_master_active ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-database"></i>
                            <p>
                                Data Master
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="guru.php" class="nav-link <?php echo $current_page == 'guru.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Guru & Mapel</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="kelas.php" class="nav-link <?php echo $current_page == 'kelas.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Kelas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="waktu.php" class="nav-link <?php echo $current_page == 'waktu.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Waktu Pelajaran</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item <?php echo $is_penjadwalan_active ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo $is_penjadwalan_active ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>
                                Penjadwalan
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="jadwal_wajib.php" class="nav-link <?php echo $current_page == 'jadwal_wajib.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Kunci Jadwal</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="jadwal.php" class="nav-link <?php echo $current_page == 'jadwal.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Kelola Jadwal</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
