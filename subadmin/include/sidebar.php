<?php
  $mailCount = 0; // default para sure na may value

  // count para sa mailbox
  $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Email Confirmation'");
  $stmt->execute();
  $stmt->bind_result($mailCount);
  $stmt->fetch();
  $stmt->close();

  $listCount = 0;
  $reviewCount = 0; // ✅ for review counter

  if ($_SESSION['role'] === 'LUNTIAN') {
      // bilangin lahat ng allocated jobs
      $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'Allocated'");
      $stmt->execute();
      $stmt->bind_result($listCount);
      $stmt->fetch();
      $stmt->close();

      // bilangin lahat ng for review jobs
      $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Review'");
      $stmt->execute();
      $stmt->bind_result($reviewCount);
      $stmt->fetch();
      $stmt->close();
  } else {
      // bilangin jobs na naka-link sa client name = role
      $stmt = $conn->prepare("
          SELECT COUNT(*) 
          FROM jobs j 
          LEFT JOIN clients c ON j.client_code = c.client_code 
          WHERE c.client_name = ? AND job_status = 'Allocated'
      ");
      $stmt->bind_param("s", $_SESSION['role']);
      $stmt->execute();
      $stmt->bind_result($listCount);
      $stmt->fetch();
      $stmt->close();

      // bilangin jobs na naka-link sa client name = role AND For Review
      $stmt = $conn->prepare("
          SELECT COUNT(*) 
          FROM jobs j 
          LEFT JOIN clients c ON j.client_code = c.client_code 
          WHERE c.client_name = ? AND job_status = 'For Review'
      ");
      $stmt->bind_param("s", $_SESSION['role']);
      $stmt->execute();
      $stmt->bind_result($reviewCount);
      $stmt->fetch();
      $stmt->close();
  }
?>

<div class="sidebar" id="sidebar">
  <div class="sidebar-inner slimscroll">
    <div id="sidebar-menu" class="sidebar-menu">
      <ul>
        <!-- Main Menu -->
        <li class="menu-title"><span>Main Menu</span></li>
        <li>
          <a href="index"><i class="si si-grid"></i> <span>Dashboard</span></a>
        </li>

        <!-- Admin Management -->
        <li class="menu-title"><span>Admin Management</span></li>
        <li class="submenu">
          <a href="#"><i class="si si-briefcase"></i> <span>Job Management</span> <span class="menu-arrow"></span></a>
          <ul class="removeActive">
            <li>
              <a href="job">
                <div class="d-flex justify-content-between align-items-center">
                  List
                  <span id="listCountBadge" class="badge badge-info float-right" style="display:none"></span>
                </div>
              </a>
            </li>

            <li><a href="job-completed">Completed</a></li>
            <?php if ($_SESSION['role'] === 'Checker'): ?>
              <li>
                <a href="job-review">
                  <div class="d-flex justify-content-between align-items-center">
                    For Review
                    <span id="reviewCountBadge" class="badge badge-warning float-right" style="display:none"></span>
                  </div>
                </a>
              </li>

              <li>
                <a href="job-mailbox">
                  <div class="d-flex justify-content-between align-items-center">
                    Mailbox
                    <span id="mailCountBadge" class="badge badge-danger float-right" style="display:none"></span>
                  </div>
                </a>
              </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'Staff' || $_SESSION['role'] === 'Checker'): ?>
              <li><a href="trash">Trash</a></li>
            <?php endif; ?>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</div>
