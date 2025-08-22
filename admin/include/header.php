<head>
  <?php 
    include '../controller/auth.php'; 
    include '../database/db.php';
  ?>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=0"
    />
    <title>Luntians</title>
    <link rel="shortcut icon" href="../assets/img/honekawa.ico" />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="../assets/plugins/bootstrap/css/bootstrap.min.css"
    />
    <link rel="stylesheet" href="../assets/plugins/feather/feather.css" />
    <link rel="stylesheet" href="../assets/plugins/icons/flags/flags.css" />
    <link
      rel="stylesheet"
      href="../assets/plugins/fontawesome/css/fontawesome.min.css"
    />
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link rel="stylesheet" href="../assets/plugins/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/plugins/simpleline/simple-line-icons.css">
    <link rel="stylesheet" href="../assets/plugins/datatables/datatables.min.css">
    <link rel="stylesheet" href="../assets/plugins//toastr/toatr.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <style>
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

      body.dark-mode {
        background-color: #1e1e1e;
        color: #e0e0e0;
      }
      body.dark-mode .sidebar {
        background-color: #2b2b2b;
      }
      body.dark-mode .sidebar-menu li a {
        color: #cfcfcf;
      }
      body.dark-mode .sidebar-menu li.active > a,
      body.dark-mode .sidebar-menu li a:hover {
        background-color: #3a3a3a;
        color: #fff;
      }
      body.dark-mode .header {
        background-color: #2b2b2b;
        border-bottom: 1px solid #444;
      }
      body.dark-mode .dropdown-menu {
        background-color: #333;
        color: #fff;
      }
      body.dark-mode .dropdown-menu a {
        color: #fff;
      }
      body.dark-mode .dropdown-menu a:hover {
        background-color: #444;
      }
      body.dark-mode table.dataTable {
        background-color: #2b2b2b;
        color: #ddd;
      }
      body.dark-mode .dataTables_wrapper {
        color: #ddd;
      }
      body.dark-mode input,
      body.dark-mode select,
      body.dark-mode textarea {
        background-color: #333;
        color: #fff;
        border: 1px solid #555;
      }
      body.dark-mode .select2-container--default .select2-selection--single {
        background-color: #333 !important;
        border-color: #555 !important;
        color: #fff !important;
      }
      body.dark-mode .select2-results__option {
        background-color: #333;
        color: #fff;
      }
      body.dark-mode .select2-results__option--highlighted {
        background-color: #555;
      }
      body.dark-mode #inventoryTable_wrapper > div.dt-buttons > button {
        background-color: #4caf50;
        color: #fff;
        border: none;
      }

      body.dark-mode .header-left {
        background-color: #2b2b2b;
      }

      body.dark-mode .user-header {
        background-color: #2b2b2b;
        color: #fff;
        border-bottom: 1px solid #444;
      }
      body.dark-mode .user-header .user-text p.text-muted {
        color: #ccc !important;
      }

      .header-left {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .header-left img {
        display: block;
      }

      body.dark-mode .card {
        background-color: #2b2b2b;
        border: 1px solid #444;
        color: #e0e0e0;
        box-shadow: none;
      }

      body.dark-mode .card-header {
        background-color: #333;
        border-bottom: 1px solid #444;
        color: #fff;
      }

      body.dark-mode .card-body {
        background-color: #2b2b2b;
        color: #ddd;
      }

      body.dark-mode .card-footer {
        background-color: #333;
        border-top: 1px solid #444;
        color: #ccc;
      }

      body.dark-mode .page-header .page-title {
        color: #fff !important;
      }

      body.dark-mode .page-header .breadcrumb-item a {
        color: #ccc !important;
      }

      body.dark-mode .page-header .breadcrumb-item.active {
        color: #fff !important;
      }

      body.dark-mode .dataTables_wrapper .pagination .page-item .page-link {
        background-color: #2b2b2b;
        color: #ddd;
        border: 1px solid #555;
      }

      body.dark-mode .dataTables_wrapper .pagination .page-item .page-link:hover {
        background-color: #444;
        color: #fff;
        border-color: #666;
      }

      body.dark-mode .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #4caf50;
        border-color: #4caf50;
        color: #fff;
      }

      body.dark-mode .dataTables_wrapper .pagination .page-item.disabled .page-link {
        background-color: #2b2b2b;
        color: #777;
        border-color: #555;
      }

      .card-title {
        color: #000;
      }

      body.dark-mode .card-title {
        color: #fff !important;
      }

      body.dark-mode .new-user-menus .user-text h6,
      body.dark-mode .new-user-menus .user-text p {
        color: #fff !important;
      }

      body.dark-mode .dropdown-menu .user-text h6,
      body.dark-mode .dropdown-menu .user-text p {
        color: #fff !important;
      }

      body.dark-mode .modal-content {
        background-color: #2b2b2b;
        color: #e0e0e0;
        border: 1px solid #444;
      }

      body.dark-mode .modal-header,
      body.dark-mode .modal-footer {
        background-color: #333;
        border-color: #444;
        color: #fff;
      }

      body.dark-mode .modal-title {
        color: #fff;
      }

      body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
      }

      body.dark-mode .form-control,
      body.dark-mode .form-select,
      body.dark-mode textarea {
        background-color: #333;
        color: #fff;
        border: 1px solid #555;
      }

      body.dark-mode .form-control:focus,
      body.dark-mode .form-select:focus,
      body.dark-mode textarea:focus {
        background-color: #444;
        color: #fff;
        border-color: #777;
        box-shadow: none;
      }

      body.dark-mode .form-label {
        color: #ddd;
      }

      body.dark-mode h5 {
        color: #ddd;
      }

      body.dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #fff;
      }

      /* Default (Light Mode) */
      .modal-custom-header {
        background-color: #0d6efd; /* Bootstrap primary */
        color: #fff;
      }

      /* Dark Mode Override */
      body.dark-mode .modal-custom-header {
        background-color: #333 !important;
        border-bottom: 1px solid #444;
        color: #fff;
      }

      .select2-container {
        z-index: 9999 !important; /* para lumutang sa ibabaw ng modal */
      }
      .select2-dropdown {
        z-index: 99999 !important;
      }

      .form-control {
        height: 50px !important;
      }
    </style>
  </head>
