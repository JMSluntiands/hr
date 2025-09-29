<?php
  $complexity = 0;

  $res = mysqli_query($conn, "SELECT plan_complexity FROM jobs WHERE job_id = $jobID");
  if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    $complexity = (int)$row['plan_complexity'];
  }
?>

<div class="card-body">
  <!-- Staff -->
  <div class="d-flex justify-content-between align-items-center py-1">
    <span><strong>Complexity</strong></span>

      <div id="starRating" class="d-flex">
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <i class="fa fa-star complexity-star <?php echo ($i <= $complexity) ? 'text-warning' : 'text-secondary'; ?>" 
         data-value="<?php echo $i; ?>" 
         style="font-size: 24px; cursor:pointer; margin-right:5px;"></i>
    <?php endfor; ?>
  </div>
  </div>

  <input type="hidden" id="jobID" value="<?php echo $jobID; ?>">
</div>

<script>
$(document).ready(function() {
  // Kapag nag click ng star
  $(".complexity-star").on("click", function() {
    let value = $(this).data("value");
    let jobID = $("#jobID").val();

    // Update UI agad (highlight stars)
    $(".complexity-star").each(function() {
      if ($(this).data("value") <= value) {
        $(this).removeClass("text-secondary").addClass("text-warning");
      } else {
        $(this).removeClass("text-warning").addClass("text-secondary");
      }
    });

    // AJAX request para i-save sa DB
    $.ajax({
      url: "../controller/job/update_complexity.php",
      type: "POST",
      data: { job_id: jobID, complexity: value },
      success: function(res) {
        try {
          let r = JSON.parse(res);
          if (r.status === "success") {
            toastr.success("Complexity updated to " + value, "Success");
          } else {
            toastr.error(r.message || "Update failed", "Error");
          }
        } catch (e) {
          toastr.error("Invalid response", "Error");
        }
      },
      error: function() {
        toastr.error("AJAX error", "Error");
      }
    });
  });
});
</script>
