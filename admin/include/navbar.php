<div class="header">
  <div class="header-left">
    <a href="index" class="logo d-flex justify-content-center items-center">
      <img src="../img/logo-light.png" alt="Logo" />
    </a>
    <a href="index" class="logo logo-small">
      <img src="assets/img/honekawa.png" alt="Logo" width="30" height="30" />
    </a>
  </div>
  <div class="menu-toggle">
    <a href="javascript:void(0);" id="toggle_btn">
      <i class="fas fa-bars"></i>
    </a>
  </div>
  <a class="mobile_btn" id="mobile_btn">
    <i class="fas fa-bars"></i>
  </a>
  <ul class="nav user-menu">
    <li class="nav-item">
      <a href="javascript:void(0);" id="darkModeToggle" class="nav-link" title="Toggle Dark Mode">
        <i class="fas fa-moon"></i>
      </a>
    </li>
    <li class="nav-item dropdown has-arrow new-user-menus">
      <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
        <span class="user-img">
          <img class="rounded-circle" src="../assets/img/default.png" width="31" alt="User" />
          <div class="user-text">
            <h6><?php echo $_SESSION['role'] ?></h6>
            <p class="text-muted mb-0"><?php echo $_SESSION['role'] ?></p>
          </div>
        </span>
      </a>
      <div class="dropdown-menu">
        <div class="user-header">
          <div class="avatar avatar-sm">
            <img src="../assets/img/default.png" alt="User Image" class="avatar-img rounded-circle" />
          </div>
          <div class="user-text">
            <h6><?php echo $_SESSION['role'] ?></h6>
            <p class="text-muted mb-0"><?php echo $_SESSION['role'] ?></p>
          </div>
        </div>
        <a class="dropdown-item" href="../controller/logout">Logout</a>
      </div>
    </li>
  </ul>
</div>