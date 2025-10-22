<?php
include '../database/db.php';

// Role ng user
$role = $_SESSION['role'] ?? '';

// Filter based on role
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

// Processing Jobs (today)
$sql = "SELECT COUNT(*) as cnt FROM jobs $where AND job_status = 'Processing'";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$forReviewJobs = $row['cnt'] ?? 0;

// Pending Jobs (today)
$sql = "SELECT COUNT(*) as cnt FROM jobs $where AND job_status = 'Pending'";
$res = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($res);
$forEmailVerification = $row['cnt'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<?php include_once 'include/header.php' ?>

<body>
  <div class="main-wrapper">
    <!-- 🔹 Navbar -->
    <?php include_once 'include/navbar.php' ?>

    <?php include_once 'include/announcement.php' ?>

    <!-- 🔹 Sidebar -->
    <?php include_once 'include/sidebar.php' ?>

    <div class="page-wrapper" style="padding-top: 105px;"> <!-- ✅ Push content down -->
      <div class="content container-fluid">
        <div class="page-header">
          <div class="row">
            <div class="col-sm-12">
              <div class="page-sub-header">
                <h3 class="page-title">Welcome <?php echo $_SESSION['role'] ?>!</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
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
                      <div><i class="fas fa-briefcase fa-2x"></i></div>
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
                      <div><i class="fas fa-check-circle fa-2x"></i></div>
                    </div>
                  </div>
                </div>

                <!-- Processing -->
                <div class="col-md-3 col-sm-6">
                  <div class="card shadow rounded-3" style="background-color:#0c2d48;">
                    <div class="card-body d-flex align-items-center justify-content-between text-white">
                      <div>
                        <h2 class="mb-0" style="font-size:25px;"><?php echo $forReviewJobs; ?></h2>
                        <p class="mb-0" style="font-size:12px;">Processing</p>
                      </div>
                      <div><i class="fas fa-search fa-2x"></i></div>
                    </div>
                  </div>
                </div>

                <!-- Pending -->
                <div class="col-md-3 col-sm-6">
                  <div class="card shadow rounded-3" style="background-color:#2b7c85;">
                    <div class="card-body d-flex align-items-center justify-content-between text-white">
                      <div>
                        <h2 class="mb-0" style="font-size:25px;"><?php echo $forEmailVerification; ?></h2>
                        <p class="mb-0" style="font-size:12px;">Pending</p>
                      </div>
                      <div><i class="fas fa-envelope-open-text fa-2x"></i></div>
                    </div>
                  </div>
                </div>

              </div> <!-- end row g-3 -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>
</body>
</html>
