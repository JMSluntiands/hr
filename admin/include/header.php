<head>
  <?php 
    include '../controller/auth.php'; 
    include '../database/db.php';
  ?>
  <?php
    // Disable caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
  ?>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
  <title>Luntians</title>
  <link rel="shortcut icon" href="../assets/img/honekawa.ico" />

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/plugins/feather/feather.css" />
  <link rel="stylesheet" href="../assets/plugins/icons/flags/flags.css" />
  <link rel="stylesheet" href="../assets/plugins/fontawesome/css/fontawesome.min.css" />
  <link rel="stylesheet" href="../assets/plugins/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/plugins/simpleline/simple-line-icons.css">
  <link rel="stylesheet" href="../assets/plugins/datatables/datatables.min.css">
  <link rel="stylesheet" href="../assets/plugins/toastr/toastr.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <link rel="stylesheet" href="../assets/css/style.css" />

  <!-- JS -->
  <script src="../assets/js/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

  <!-- Select2 CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

  <style>
    /* Select2 */
    .select2-container--default .select2-selection--single {
      height: 50px !important;
      border: 1px solid #e5e5e5 !important;
      border-radius: 6px !important;
      display: flex !important;
      align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      font-size: 16px;
      padding-left: 15px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 100%;
      right: 10px;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border: 1px solid #e5e5e5;
      height: 50px;
      border-radius: 6px;
      font-size: 16px;
      padding-left: 15px;
    }
    .select2-dropdown {
      background-color: white;
      border: 1px solid #e5e5e5;
      border-radius: 6px;
      z-index: 1051;
    }
    .select2-results__option {
      padding: 10px 14px;
    }

    #inventoryTable_wrapper > div.dt-buttons > button {
      background-color: #2e7d32;
      border: none;
      color: #ffffff;
    }

    .form-control {
      height: 50px !important;
    }

    /* ✅ DARK MODE */
    body.dark-mode {
      background-color: #1e1e1e;
      color: #e0e0e0;
    }

    /* Sidebar & Header */
    body.dark-mode .sidebar,
    body.dark-mode .header,
    body.dark-mode .header-left,
    body.dark-mode .user-header {
      background-color: #2b2b2b;
      border-color: #444;
      color: #fff;
    }
    body.dark-mode .sidebar-menu li a { color: #cfcfcf; }
    body.dark-mode .sidebar-menu li.active > a,
    body.dark-mode .sidebar-menu li a:hover {
      background-color: #3a3a3a;
      color: #fff;
    }

    /* Dropdown */
    body.dark-mode .dropdown-menu {
      background-color: #333;
      color: #fff;
    }
    body.dark-mode .dropdown-menu a { color: #fff; }
    body.dark-mode .dropdown-menu a:hover { background-color: #444; }

    /* Cards & Modals */
    body.dark-mode .card,
    body.dark-mode .modal-content {
      background-color: #2b2b2b;
      border: 1px solid #444;
      color: #e0e0e0;
    }
    body.dark-mode .card-header,
    body.dark-mode .modal-header,
    body.dark-mode .modal-footer {
      background-color: #333;
      border-color: #444;
      color: #fff;
    }
    body.dark-mode .card-title,
    body.dark-mode .modal-title {
      color: #fff !important;
    }

    /* Forms */
    body.dark-mode input,
    body.dark-mode select,
    body.dark-mode textarea,
    body.dark-mode .form-control,
    body.dark-mode .form-select,
    body.dark-mode .select2-container--default .select2-selection--single {
      background-color: #333 !important;
      color: #fff !important;
      border: 1px solid #555 !important;
    }

    body.dark-mode h5,
    body.dark-mode .page-title,
    body.dark-mode .breadcrumb-item,
    body.dark-mode .user-text h6 {
      color: #fff;
    }

    body.dark-mode .breadcrumb-item a {
      color: #777;
    }

    body.dark-mode .form-control:focus,
    body.dark-mode .form-select:focus,
    body.dark-mode textarea:focus {
      background-color: #444;
      border-color: #777;
      box-shadow: none;
    }

    /* DataTables */
    body.dark-mode table.dataTable,
    body.dark-mode .dataTables_wrapper {
      background-color: #2b2b2b;
      color: #ddd;
    }
    body.dark-mode .dataTables_wrapper .pagination .page-link {
      background-color: #2b2b2b;
      color: #ddd;
      border: 1px solid #555;
    }
    body.dark-mode .dataTables_wrapper .pagination .page-link:hover {
      background-color: #444;
      color: #fff;
    }
    body.dark-mode .dataTables_wrapper .pagination .active .page-link {
      background-color: #4caf50;
      border-color: #4caf50;
      color: #fff;
    }

    /* Buttons */
    body.dark-mode #inventoryTable_wrapper > div.dt-buttons > button {
      background-color: #4caf50;
      color: #fff;
      border: none;
    }

    /* Lists (Files & Activity Logs) */
    body.dark-mode .list-group-item {
      background-color: #2c2c2c;
      color: #f1f1f1;
      border-color: #444;
    }
    body.dark-mode .list-group-item a { color: #4da6ff; }
    body.dark-mode .list-group-item a:hover {
      color: #80c0ff;
      text-decoration: underline;
    }
    body.dark-mode .list-group-flush .list-group-item { color: #ddd; }
    body.dark-mode .list-group-flush .fw-bold.text-primary {
      color: #66b3ff !important;
    }
    body.dark-mode .list-group-flush small.text-muted { color: #aaa !important; }
    body.dark-mode .list-group-flush small.text-secondary { color: #bbb !important; }

    /* 🔥 Fix: Select2 text & options in Dark Mode */
    body.dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #fff !important;
    }
    body.dark-mode .select2-results__option {
      background-color: #333;
      color: #fff;
    }
    body.dark-mode .select2-results__option--highlighted {
      background-color: #555 !important;
      color: #fff !important;
    }

    /* ✅ Dark Mode: Comments Input Group */
    body.dark-mode .card-footer {
      background-color: #2c2c2c;
      border-top: 1px solid #444;
    }
    body.dark-mode .card-footer .form-control {
      background-color: #333 !important;
      color: #fff !important;
      border: 1px solid #555 !important;
    }
    body.dark-mode .card-footer .form-control::placeholder {
      color: #aaa !important;
    }
    body.dark-mode .card-footer .btn {
      background-color: #0d6efd;
      color: #fff;
      border: none;
    }
    body.dark-mode .card-footer .btn:hover {
      background-color: #0b5ed7;
    }
  </style>
</head>
