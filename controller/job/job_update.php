<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid method']);
    exit;
  }

  $jobID        = (int)($_POST['job_id'] ?? 0);
  $reference    = trim($_POST['reference'] ?? '');
  $client_ref   = trim($_POST['client_ref'] ?? '');
  $compliance   = $_POST['compliance'] ?? null;
  $client_id    = (int)($_POST['client_account_id'] ?? 0);
  $priority     = $_POST['priority'] ?? null;
  $status       = $_POST['status'] ?? null;
  $notes        = $_POST['notes'] ?? null;
  $address      = $_POST['address'] ?? null;
  $staff_id     = $_POST['staff_id'] ?? null;
  $checker_id   = $_POST['checker_id'] ?? null;
  $job_type     = $_POST['job_type'] ?? null;
  $updated_by   = $_SESSION['username'] ?? ($_SESSION['role'] ?? 'system');

  if (!$jobID || !$reference) {
    echo json_encode(['status'=>'error','message'=>'Missing job_id or reference']);
    exit;
  }

  // Escape inputs
  $reference  = mysqli_real_escape_string($conn, $reference);
  $client_ref = mysqli_real_escape_string($conn, $client_ref);
  $compliance = mysqli_real_escape_string($conn, $compliance);
  $priority   = mysqli_real_escape_string($conn, $priority);
  $status     = mysqli_real_escape_string($conn, $status);
  $notes      = mysqli_real_escape_string($conn, $notes);
  $address    = mysqli_real_escape_string($conn, $address);
  $updated_by = mysqli_real_escape_string($conn, $updated_by);
  $staff_id   = mysqli_real_escape_string($conn, $staff_id);
  $checker_id = mysqli_real_escape_string($conn, $checker_id);
  $job_type   = mysqli_real_escape_string($conn, $job_type);

  // Load current job
  $curRes = $conn->query("SELECT * FROM jobs WHERE job_id=$jobID");
  $cur = $curRes->fetch_assoc();
  if (!$cur) {
    echo json_encode(['status'=>'error','message'=>'Job not found']);
    exit;
  }

  // Duplicate checks (exclude this job)
  $dupRef = $conn->query("SELECT job_id FROM jobs WHERE job_reference_no = '$reference' AND job_id <> $jobID");
  if ($dupRef->num_rows > 0) {
    echo json_encode(['status'=>'error','message'=>'Reference No already exists']);
    exit;
  }

  $dupClientRef = $conn->query("SELECT job_id FROM jobs WHERE client_reference_no = '$client_ref' AND job_id <> $jobID");
  if ($dupClientRef->num_rows > 0) {
    echo json_encode(['status'=>'error','message'=>'Client Reference already exists']);
    exit;
  }

    // ✅ Duplicate Address check (ignoring spaces) only if changed
  $oldNormalized = strtolower(preg_replace('/\s+/', '', trim($cur['address_client'] ?? '')));
  $newNormalized = strtolower(preg_replace('/\s+/', '', trim($address)));

  if ($oldNormalized !== $newNormalized) {
    $dupAddress = $conn->query("
        SELECT job_id 
        FROM jobs 
        WHERE REPLACE(LOWER(address_client), ' ', '') = '$newNormalized'
        AND job_id <> $jobID
    ");
    if ($dupAddress->num_rows > 0) {
      echo json_encode(['status'=>'error','message'=>'Job Address already exists (ignoring spaces).']);
      exit;
    }
  }


  // Decode existing files
  $plans = json_decode($cur['upload_files'] ?? '[]', true);
  $docs  = json_decode($cur['upload_project_files'] ?? '[]', true);
  if (!is_array($plans)) $plans=[];
  if (!is_array($docs))  $docs=[];

  // Upload handling (append), PDF only, <=10MB
  $baseDir = realpath(__DIR__ . '/../../') . '/document';
  $jobFolder = $baseDir . '/' . $cur['job_reference_no'];
  if (!is_dir($jobFolder)) {
    @mkdir($jobFolder, 0777, true);
  }

  $addedPlans = [];
  if (!empty($_FILES['plans']['name'][0])) {
    for ($i=0; $i<count($_FILES['plans']['name']); $i++) {
      $name = $_FILES['plans']['name'][$i];
      $tmp  = $_FILES['plans']['tmp_name'][$i];
      $size = $_FILES['plans']['size'][$i];
      $type = mime_content_type($tmp);

      if ($type !== 'application/pdf') {
        echo json_encode(['status'=>'error','message'=>"Plans must be PDF: $name"]);
        exit;
      }
      if ($size > 10*1024*1024) {
        echo json_encode(['status'=>'error','message'=>"Plans file exceeds 10MB: $name"]);
        exit;
      }

      $safe = preg_replace('/[^A-Za-z0-9._-]/','_', $name);
      $final = time().'_'.$safe;
      if (!move_uploaded_file($tmp, $jobFolder.'/'.$final)) {
        echo json_encode(['status'=>'error','message'=>"Failed to upload: $name"]);
        exit;
      }
      $plans[] = $final;
      $addedPlans[] = $final;
    }
  }

  $addedDocs = [];
  if (!empty($_FILES['docs']['name'][0])) {
    for ($i=0; $i<count($_FILES['docs']['name']); $i++) {
      $name = $_FILES['docs']['name'][$i];
      $tmp  = $_FILES['docs']['tmp_name'][$i];
      $size = $_FILES['docs']['size'][$i];
      $type = mime_content_type($tmp);

      if ($type !== 'application/pdf') {
        echo json_encode(['status'=>'error','message'=>"Documents must be PDF: $name"]);
        exit;
      }
      if ($size > 10*1024*1024) {
        echo json_encode(['status'=>'error','message'=>"Document file exceeds 10MB: $name"]);
        exit;
      }

      $safe = preg_replace('/[^A-Za-z0-9._-]/','_', $name);
      $final = time().'_'.$safe;
      if (!move_uploaded_file($tmp, $jobFolder.'/'.$final)) {
        echo json_encode(['status'=>'error','message'=>"Failed to upload: $name"]);
        exit;
      }
      $docs[] = $final;
      $addedDocs[] = $final;
    }
  }

  // Update job
  $plansJson = mysqli_real_escape_string($conn, json_encode(array_values($plans)));
  $docsJson  = mysqli_real_escape_string($conn, json_encode(array_values($docs)));

  $sql = "
    UPDATE jobs SET
      job_reference_no = '$reference',
      client_reference_no = '$client_ref',
      ncc_compliance = '$compliance',
      client_account_id = $client_id,
      priority = '$priority',
      job_status = '$status',
      notes = '$notes',
      address_client = '$address',
      staff_id = '$staff_id',
      checker_id = '$checker_id',
      job_type = '$job_type',
      upload_files = '$plansJson',
      upload_project_files = '$docsJson',
      last_update = NOW()
    WHERE job_id = $jobID
  ";

  if (!$conn->query($sql)) {
    throw new Exception("Update failed: ".$conn->error);
  }

  // Build activity logs
  $changes = [];
  $map = [
    'job_reference_no'   => ['label'=>'Reference','new'=>$reference],
    'client_reference_no'=> ['label'=>'Client Reference','new'=>$client_ref],
    'ncc_compliance'     => ['label'=>'Compliance','new'=>$compliance],
    'client_account_id'  => ['label'=>'Client','new'=>$client_id],
    'priority'           => ['label'=>'Priority','new'=>$priority],
    'job_status'         => ['label'=>'Status','new'=>$status],
    'notes'              => ['label'=>'Notes','new'=>$notes],
    'address_client'     => ['label'=>'Address','new'=>$address],
    'staff_id'           => ['label'=>'Assigned To','new'=>$staff_id],
    'checker_id'         => ['label'=>'Checked By','new'=>$checker_id],
    'job_type'           => ['label'=>'Job Type','new'=>$job_type],
  ];

  foreach ($map as $field=>$info) {
    $old = (string)($cur[$field] ?? '');
    $new = (string)$info['new'];
    if ($old !== $new) {
      $changes[] = $info['label'].": \"{$old}\" → \"{$new}\"";
    }
  }
  if (!empty($addedPlans)) $changes[] = "Added Plans: ".implode(', ', $addedPlans);
  if (!empty($addedDocs))  $changes[] = "Added Documents: ".implode(', ', $addedDocs);

  if (!empty($changes)) {
    $desc = mysqli_real_escape_string($conn, implode("\n", $changes));
    $conn->query("
      INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by)
      VALUES ($jobID, 'Update', '$desc', '$updated_by')
    ");
  }

  echo json_encode(['status'=>'success','message'=>'Job updated successfully']);
} catch (Throwable $e) {
  echo json_encode(['status'=>'error','message'=>'Server error','debug'=>$e->getMessage()]);
}
