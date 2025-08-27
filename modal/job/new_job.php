<!-- New Job Modal -->
<div class="modal fade" id="newJobModal" tabindex="-1" aria-labelledby="newJobLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      
      <!-- Modal Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="newJobLabel">Create New Job</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <form id="newJobForm" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row g-3">

            <div class="col-12">
              <h5 class="text-center">Client Details</h5>
              <input type="hidden" name="job_id" id="job_id">
            </div>

            <!-- Reference No. (restricted) -->
            <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
              <div class="col-md-4">
                <label class="form-label">Reference No.</label>
                <input type="text" name="reference" class="form-control" placeholder="Enter Reference No." required autocomplete="off">
              </div>
            <?php endif; ?>

            <!-- Client Reference (always visible) -->
            <div class="col-md-4">
              <label class="form-label">Client Reference</label>
              <input type="text" name="client_ref" class="form-control" placeholder="Enter Client Reference" autocomplete="off">
            </div>

            <!-- Compliance (restricted) -->
            <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
              <div class="col-md-4">
                <label class="form-label">Compliance</label>
                <select name="compliance" class="form-select compliance" required>
                  <option value="2022 (WHO)" selected>2022 (WHO)</option>
                  <option value="2019">2019</option>
                </select>
              </div>
            <?php endif; ?>

            <!-- Client (restricted) -->
            <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
              <div class="col-md-12">
                <div class="mb-3">
                  <label for="clientID" class="form-label">Client</label>
                  <select id="clientID" name="clientID" class="form-control"></select>
                </div>
              </div>
            <?php endif; ?>

            <div class="col-12"><hr></div>

            <div class="col-12">
              <h5 class="text-center">Job Details</h5>
            </div>

            <div class="col-md-12">
              <label class="form-label">Job Address</label>
              <textarea name="address" rows="1" class="form-control" placeholder="Complete Address"></textarea>
            </div>

            <div class="col-md-12">
              <label class="form-label">Job Priority</label>
              <select name="priority" class="form-select priority" required>
                <option value="Top (COB)" selected>Top (COB)</option>
                <option value="High 1 day">High 1 day</option>
                <option value="Standard 2 days">Standard 2 days</option>
                <option value="Standard 3 days">Standard 3 days</option>
                <option value="Standard 4 days">Standard 4 days</option>
                <option value="Low 5 days">Low 5 days</option>
                <option value="Low 6 days">Low 6 days</option>
                <option value="Low 7 days">Low 7 days</option>
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Job Type</label>
              <select name="jobRequest" id="jobRequest" class="select2"></select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Job Status</label>
              <select name="status" class="form-select compliance" required>
                <option value="Allocated" selected>Allocated</option>
                <option value="Accepted">Accepted</option>
                <option value="Processing">Processing</option>
                <option value="For Checking">For Checking</option>
                <option value="Completed">Completed</option>
                <option value="Awaiting Further Information">Awaiting Further Information</option>
                <option value="Pending">Pending</option>
                <option value="For Discussion">For Discussion</option>
                <option value="Revision Requested">Revision Requested</option>
                <option value="Revised">Revised</option>
              </select>
            </div>

            <!-- Notes (restricted) -->
            <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
              <div class="col-md-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="1" class="form-control" placeholder="Notes"></textarea>
              </div>
            <?php endif; ?>

            <div class="col-md-12">
              <label class="form-label d-flex justify-content-between">
                Upload Plans 
                <span class="badge bg-secondary" id="plansCount">0 files</span>
              </label>
              <input type="file" class="form-control" id="uploadPlans" multiple accept="application/pdf" name="plans[]">
              <div id="plansPreview" class="mt-2"></div>
            </div>

            <div class="col-md-12">
              <label class="form-label d-flex justify-content-between">
                Upload Document
                <span class="badge bg-secondary" id="docsCount">0 files</span>
              </label>
              <input type="file" class="form-control" id="uploadDocs" multiple accept="application/pdf" name="docs[]">
              <div id="docsPreview" class="mt-2"></div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Assigned To</label>
              <select name="assigned" class="form-select assign" required>
                <?php 
                  $assign = "SELECT * FROM staff ORDER BY id DESC";
                  $assign_sql = mysqli_query($conn, $assign);

                  foreach ($assign_sql as $dataAss) {
                    $selected = ($dataAss['staff_id'] === "GM") ? "selected" : "";
                    echo '<option value="'.$dataAss['staff_id'].'" '.$selected.'>'.$dataAss['staff_id'].'</option>';
                  }
                ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Checked By</label>
              <select name="checked" class="form-select checked" required>
                <?php 
                  $assign = "SELECT * FROM checker ORDER BY id DESC";
                  $assign_sql = mysqli_query($conn, $assign);

                  foreach ($assign_sql as $dataAss) {
                    $selected = ($dataAss['checker_id'] === "GM") ? "selected" : "";
                    echo '<option value="'.$dataAss['checker_id'].'" '.$selected.'>'.$dataAss['checker_id'].'</option>';
                  }
                ?>
              </select>
            </div>
            
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Job</button>
        </div>
      </form>
    </div>
  </div>
</div>
