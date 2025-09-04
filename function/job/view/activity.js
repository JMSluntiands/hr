function loadActivityLogs() {
  let jobID = $("#jobID").val();
  $("#activityLogs").load("../controller/job/view_activity_logs.php?job_id=" + jobID);
}

loadActivityLogs();