<!DOCTYPE html>
<html lang="en">
<?php include_once 'include/header.php' ?>
<body>
  <div class="main-wrapper">
    <!-- Navbar -->
    <?php include_once 'include/navbar.php' ?>
    <?php include_once 'include/announcement.php' ?>
    <!-- Sidebar -->
    <?php include_once 'include/sidebar.php' ?>

    <div class="page-wrapper" style="padding-top: 105px;">
      <div class="content container-fluid">

        <div class="page-header">
          <div class="row">
            <div class="col-sm-12">
              <div class="page-sub-header">
                <h3 class="page-title">Announcement Management</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
                  <li class="breadcrumb-item active">Announcements</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="card-title mb-2">Announcements</h5>
                  <span id="announcementCount" class="text-muted">Total Records: 0</span>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                  <i class="fa fa-plus"></i> Add Announcement
                </button>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="datatable table table-striped" id="announcementTable">
                    <thead>
                      <tr>
                        <th>Action</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                      </tr>
                    </thead>
                    <tbody id="announcementBody"></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- 🔹 Add Modal -->
  <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="addAnnouncementForm">
          <div class="modal-header">
            <h5 class="modal-title" id="addModalLabel">Add Announcement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label>Title</label>
              <input type="text" class="form-control" name="title" required>
            </div>
            <div class="mb-3">
              <label>Message</label>
              <textarea class="form-control" name="message" rows="4" required></textarea>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Start Date</label>
                <input type="datetime-local" class="form-control" name="start_date">
              </div>
              <div class="col-md-6 mb-3">
                <label>End Date</label>
                <input type="datetime-local" class="form-control" name="end_date">
              </div>
            </div>
            <div class="mb-3">
              <label>Status</label>
              <select class="form-control" name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 🔹 Update Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editAnnouncementForm">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title" id="editAnnouncementLabel">Update Announcement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">

          <div class="mb-3">
            <label class="form-label fw-bold">Title</label>
            <input type="text" class="form-control" id="edit_title" name="title" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Message</label>
            <textarea class="form-control" id="edit_message" name="message" rows="3" required></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Start Date</label>
              <input type="datetime-local" class="form-control" id="edit_start_date" name="start_date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">End Date</label>
              <input type="datetime-local" class="form-control" id="edit_end_date" name="end_date">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Status</label>
            <select class="form-select" id="edit_status" name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>


  <?php include_once 'include/footer.php' ?>
</body>
<script src="../function/announcement/announcement-list.js?v=<?php echo time(); ?>"></script>
</html>
