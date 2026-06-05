<?php
  // Disabled all jobs-related counters to avoid missing-table errors
  $mailCount = 0;
  $listCount = 0;
  $reviewCount = 0;
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
                  <?php if ($listCount > 0): ?>
                    <span class="badge badge-info float-right"><?= $listCount ?></span>
                  <?php endif; ?>
                </div>
              </a>
            </li>

            <li><a href="job-completed">Completed</a></li>
            <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
              <li>
                <a href="job-review">
                  <div class="d-flex justify-content-between align-items-center">
                    For Review
                    <?php if ($reviewCount > 0): ?>
                      <span class="badge badge-warning float-right"><?= $reviewCount ?></span>
                    <?php endif; ?>
                  </div>
                </a>
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
              <li><a href="client-add">Create Client</a></li>
              <li><a href="client">Client List</a></li>
            </ul>
          </li>
          <li class="submenu">
            <a href="#"><i class="si si-user"></i> <span>Checker</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <li><a href="checker-add">Create Checker</a></li>
              <li><a href="checker">Checker List</a></li>
            </ul>
          </li>
          <li class="submenu">
            <a href="#"><i class="si si-user"></i> <span>Staff</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <li><a href="staff-add">Create Staff</a></li>
              <li><a href="staff">Staff List</a></li>
            </ul>
          </li>
          <li class="submenu">
            <a href="#"><i class="si si-note"></i> <span>Job Request</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <li><a href="job-type-add">Create Job Type</a></li>
              <li><a href="job-type">Job Type List</a></li>
            </ul>
          </li>
          <li class="submenu">
            <a href="#"><i class="si si-note"></i> <span>Announcement</span> <span class="menu-arrow"></span></a>
            <ul class="removeActive">
              <!-- <li><a href="job-type-add">Create Job Type</a></li> -->
              <li><a href="announcement">Announcement List</a></li>
            </ul>
          </li>

        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
