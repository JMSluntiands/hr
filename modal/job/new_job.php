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
      <form id="newJobForm">
        <div class="modal-body">
          <div class="row g-3">

            

            <div class="col-md-6">
              <label class="form-label">Reference No.</label>
              <input type="text" name="reference" class="form-control" placeholder="Enter Reference No." required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Client Reference</label>
              <input type="text" name="client_ref" class="form-control" placeholder="Enter Client Reference">
            </div>

            <div class="col-md-6">
              <label class="form-label">Assigned To</label>
              <select name="assigned" class="form-select" required>
                <option value="" selected>Choose</option>
                <?php 
                  $assign = "SELECT * FROM staff ORDER BY id DESC";
                  $assign_sql = mysqli_query($conn, $assign);

                  foreach ($assign_sql as $dataAss) {
                    echo '<option value="'.$dataAss['staff_id'].'">'.$dataAss['name'].'</option>';
                  }
                ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Checked By</label>
              <select name="checked" class="form-select" required>
                <option value="" selected>Choose</option>
                <?php 
                  $assign = "SELECT * FROM checker ORDER BY id DESC";
                  $assign_sql = mysqli_query($conn, $assign);

                  foreach ($assign_sql as $dataAss) {
                    echo '<option value="'.$dataAss['checker_id'].'">'.$dataAss['name'].'</option>';
                  }
                ?>
              </select>
            </div>

            


            <div class="col-md-6">
              <label class="form-label">Compliance</label>
              <select name="compliance" class="form-select" required>
                <option value="" selected>Choose</option>
                <option value="NCC2019">NCC2019</option>
                <option value="NCC2022">NCC2022</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-select" required>
                <option value="Top">Top</option>
                <option value="High">High</option>
                <option value="Standard 2 days">Standard 2 days</option>
                <option value="Standard 3 days">Standard 3 days</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Job Request</label>
              <select name="jobRequest" id="jobRequest" class=" select2"></select>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
  <label for="clientID" class="form-label">Client</label>
  <select id="clientID" name="clientID" class="form-control"></select>
</div>

            </div>

            <div class="col-md-12">
              <label class="form-label">Address</label>
              <textarea name="address" rows="2" class="form-control" placeholder="Complete Address"></textarea>
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
