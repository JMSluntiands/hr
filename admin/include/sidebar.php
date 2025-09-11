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
            <li><a href="job">List</a></li>
            <li><a href="job-completed">Completed</a></li>
            <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
            <li><a href="job-review">For Review</a></li>
            <li><a href="job-mailbox">Mailbox</a></li>
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
