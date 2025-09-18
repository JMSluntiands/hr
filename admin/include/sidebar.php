<div class="sidebar" id="sidebar">
  <div class="sidebar-inner slimscroll">
    <div id="sidebar-menu" class="sidebar-menu">
      <ul>
        <!-- Main Menu -->
        <li class="menu-title"><span>Main Menu</span></li>
        <li>
          <a href="index"><i class="si si-grid"></i> <span>Dashboard</span></a>
        </li>
        <?php
          $mailCount = 0; // default para sure na may value

          $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Email Confirmation'");
          $stmt->execute();
          $stmt->bind_result($mailCount);
          $stmt->fetch();
          $stmt->close();

          $listCount = 0;

          if ($_SESSION['role'] === 'LUNTIAN') {
              // bilangin lahat ng allocated jobs
              $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'Allocated'");
              $stmt->execute();
              $stmt->bind_result($listCount);
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
          }
          ?>

        <!-- Admin Management -->
        <li class="menu-title"><span>Admin Management</span></li>
        <li class="submenu">
          <a href="#"><i class="si si-briefcase"></i> <span>Job Management</span> <span class="menu-arrow"></span></a>
          <ul class="removeActive">
            <li>
              <a href="job">
                <div class="d-flex justify-content-between align-items-center">
                  List
                  <?php if ($listCount > 0): ?>
                    <span class="badge badge-info float-right"><?= $listCount ?></span>
                  <?php endif; ?>
                </div>
              </a>
            </li>

            <li><a href="job-completed">Completed</a></li>
            <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
              <li>
                <a href="job-review">For Review</a>
              </li>
              <li>
                <a href="job-mailbox">
                  <div class="d-flex justify-content-between align-items-center">
                    Mailbox
                    <?php if ($mailCount > 0): ?>
                      <span class="badge badge-danger float-right"><?= $mailCount ?></span>
                    <?php endif; ?>
                  </div>

                </a>
              </li>
            <?php endif; ?>
            
            <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
              <li><a href="trash">Trash</a></li>
            <?php endif; ?>
          </ul>
        </li>

        <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
          <!-- Setting -->
          <li class="menu-title"><span>Setting</span></li>
          <li class="submenu">
            <a href="#"><i class="si si-people"></i> <span>Client</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <li><a href="client-create">Create Client</a></li>
              <li><a href="client">Client List</a></li>
            </ul>
          </li>
          <li class="submenu">
            <a href="#"><i class="si si-user"></i> <span>Account</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <li><a href="creatediscount">Create</a></li>
              <li><a href="discount">Discount</a></li>
            </ul>
          </li>
          <li class="submenu">
            <a href="#"><i class="si si-note"></i> <span>Job Request</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <li><a href="creatediscount">Create</a></li>
              <li><a href="discount">Discount</a></li>
            </ul>
          </li>
          <li class="submenu">
            <a href="#"><i class="si si-user"></i> <span>User</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <li><a href="creatediscount">Create</a></li>
              <li><a href="discount">Discount</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
