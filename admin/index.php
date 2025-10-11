<?php
session_start();
include '../database/db.php';

// Role ng user
$role = $_SESSION['role'] ?? '';

// Kung LUNTIAN, walang filter. Kung hindi, filter by client_code
$where = ($role === 'LUNTIAN') 
  ? "WHERE DATE(log_date) = CURDATE()" 
  : "WHERE client_code = '" . mysqli_real_escape_string($conn, $role) . "' AND DATE(log_date) = CURDATE()";

// Total Jobs (today)
$sql = "SELECT COUNT(*) as cnt FROM jobs $where";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$totalJobs = $row['cnt'] ?? 0;

// Completed Jobs (today)
$sql = "SELECT COUNT(*) as cnt FROM jobs $where AND job_status = 'Completed'";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$completedJobs = $row['cnt'] ?? 0;

// For Review Jobs (today)
$sql = "SELECT COUNT(*) as cnt FROM jobs $where AND job_status = 'For Review'";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$forReviewJobs = $row['cnt'] ?? 0;

// For Email Verification (today)
$sql = "SELECT COUNT(*) as cnt FROM jobs $where AND job_status = 'For Email Verification'";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$forEmailVerification = $row['cnt'] ?? 0;
?>


<!DOCTYPE html>
<html lang="en">
  <?php include_once 'include/header.php' ?>
  <body>
    <div class="main-wrapper">
      <!-- Navbar -->
      <?php include_once 'include/navbar.php' ?>
      

      <!-- Sidebar -->
      <?php include_once 'include/sidebar.php' ?>
      

      <div class="page-wrapper">
        <div class="content container-fluid">
          <div class="page-header">
            <div class="row">
              <div class="col-sm-12">
                <div class="page-sub-header">
                  <h3 class="page-title">Welcome <?php echo $_SESSION['role'] ?>!</h3>
                  <ul class="breadcrumb">
                    <li class="breadcrumb-item">
                      <a href="index">Home</a>
                    </li>
                    <li class="breadcrumb-item active">Dashboard</li>
                  </ul>
                </div>

                <div class="row g-3">

                  <!-- Total Jobs -->
                  <div class="col-md-3 col-sm-6">
                    <div class="card shadow rounded-3" style="background-color:#18a558;">
                      <div class="card-body d-flex align-items-center justify-content-between text-white">
                        <div>
                          <h2 class="mb-0" style="font-size:25px;"><?php echo $totalJobs; ?></h2>
                          <p class="mb-0" style="font-size:12px;">Total Jobs</p>
                        </div>
                        <div>
                          <i class="fas fa-briefcase fa-2x"></i>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Completed Jobs -->
                  <div class="col-md-3 col-sm-6">
                    <div class="card shadow rounded-3" style="background-color:#189ab4;">
                      <div class="card-body d-flex align-items-center justify-content-between text-white">
                        <div>
                          <h2 class="mb-0" style="font-size:25px;"><?php echo $completedJobs; ?></h2>
                          <p class="mb-0" style="font-size:12px;">Completed Jobs</p>
                        </div>
                        <div>
                          <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- For Review Jobs -->
                  <div class="col-md-3 col-sm-6">
                    <div class="card shadow rounded-3" style="background-color:#0c2d48;">
                      <div class="card-body d-flex align-items-center justify-content-between text-white">
                        <div>
                          <h2 class="mb-0" style="font-size:25px;"><?php echo $forReviewJobs; ?></h2>
                          <p class="mb-0" style="font-size:12px;">For Review</p>
                        </div>
                        <div>
                          <i class="fas fa-search fa-2x"></i>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- For Email Verification -->
                  <div class="col-md-3 col-sm-6">
                    <div class="card shadow rounded-3" style="background-color:#2b7c85;">
                      <div class="card-body d-flex align-items-center justify-content-between text-white">
                        <div>
                          <h2 class="mb-0" style="font-size:25px;"><?php echo $forEmailVerification; ?></h2>
                          <p class="mb-0" style="font-size:12px;">For Email Verification</p>
                        </div>
                        <div>
                          <i class="fas fa-envelope-open-text fa-2x"></i>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>



              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php include_once 'include/footer.php' ?>
  </body>
</html>
