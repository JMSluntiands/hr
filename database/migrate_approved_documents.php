<?php
/**
 * Migrate existing approved documents to document_files table.
 * Run once: http://localhost/hr/database/migrate_approved_documents.php
 */
require_once __DIR__ . '/db.php';

echo "<h2>Migrating Approved Documents to document_files</h2>";

if (!$conn) {
    echo "Database connection failed.<br>";
    exit;
}

// Check if document_files table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'document_files'");
if (!$checkTable || $checkTable->num_rows === 0) {
    echo "document_files table does not exist. Please run setup_document_files_table.php first.<br>";
    exit;
}

$migrated = 0;
$skipped = 0;
$errors = 0;

// 1. Migrate approved employee_document_uploads
$checkUploads = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
if ($checkUploads && $checkUploads->num_rows > 0) {
    echo "<h3>Migrating approved employee_document_uploads...</h3>";
    $sql = "SELECT id, employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at 
            FROM employee_document_uploads 
            WHERE status = 'Approved'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Check if already exists
            $check = $conn->prepare("SELECT id FROM document_files WHERE employee_id = ? AND document_type = ? AND file_path = ? LIMIT 1");
            $check->bind_param('iss', $row['employee_id'], $row['document_type'], $row['file_path']);
            $check->execute();
            $exists = $check->get_result();
            $check->close();
            
            if ($exists && $exists->num_rows > 0) {
                $skipped++;
                continue;
            }
            
            // Insert into document_files
            $filePath = $row['file_path'] ?? '';
            $approvedBy = $row['approved_by'] ?? null;
            $approvedByName = $row['approved_by_name'] ?? null;
            $approvedAt = $row['approved_at'] ?? null;
            $createdAt = $row['created_at'] ?? date('Y-m-d H:i:s');
            
            $insert = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param('issssss', 
                $row['employee_id'], 
                $row['document_type'], 
                $filePath, 
                $approvedBy, 
                $approvedByName, 
                $approvedAt, 
                $createdAt
            );
            
            if ($insert->execute()) {
                $migrated++;
                echo "Migrated: Employee ID {$row['employee_id']}, {$row['document_type']}<br>";
            } else {
                $errors++;
                echo "Error migrating: " . $insert->error . " (Employee: {$row['employee_id']}, Type: {$row['document_type']})<br>";
            }
            $insert->close();
        }
    } else {
        echo "No approved employee_document_uploads found.<br>";
    }
}

// 2. Migrate approved document_requests (may not have files)
$checkRequests = $conn->query("SHOW TABLES LIKE 'document_requests'");
if ($checkRequests && $checkRequests->num_rows > 0) {
    echo "<h3>Migrating approved document_requests...</h3>";
    $sql = "SELECT id, employee_id, document_type, approved_by, approved_by_name, approved_at, created_at 
            FROM document_requests 
            WHERE status = 'Approved'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Check if already exists
            $check = $conn->prepare("SELECT id FROM document_files WHERE employee_id = ? AND document_type = ? AND (file_path = '' OR file_path IS NULL) LIMIT 1");
            $check->bind_param('is', $row['employee_id'], $row['document_type']);
            $check->execute();
            $exists = $check->get_result();
            $check->close();
            
            if ($exists && $exists->num_rows > 0) {
                $skipped++;
                continue;
            }
            
            // Try to find related file
            $filePath = '';
            $fileCheck = $conn->prepare("SELECT file_path FROM employee_document_uploads WHERE employee_id = ? AND document_type = ? AND status = 'Approved' ORDER BY created_at DESC LIMIT 1");
            if ($fileCheck) {
                $fileCheck->bind_param('is', $row['employee_id'], $row['document_type']);
                $fileCheck->execute();
                $fileResult = $fileCheck->get_result();
                if ($fileRow = $fileResult->fetch_assoc()) {
                    $filePath = $fileRow['file_path'] ?? '';
                }
                $fileCheck->close();
            }
            
            // Insert into document_files
            $approvedBy = $row['approved_by'] ?? null;
            $approvedByName = $row['approved_by_name'] ?? null;
            $approvedAt = $row['approved_at'] ?? null;
            $createdAt = $row['created_at'] ?? date('Y-m-d H:i:s');
            
            $insert = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param('issssss', 
                $row['employee_id'], 
                $row['document_type'], 
                $filePath, 
                $approvedBy, 
                $approvedByName, 
                $approvedAt, 
                $createdAt
            );
            
            if ($insert->execute()) {
                $migrated++;
                echo "Migrated: Employee ID {$row['employee_id']}, {$row['document_type']}<br>";
            } else {
                $errors++;
                echo "Error migrating: " . $insert->error . " (Employee: {$row['employee_id']}, Type: {$row['document_type']})<br>";
            }
            $insert->close();
        }
    } else {
        echo "No approved document_requests found.<br>";
    }
}

echo "<br><h3>Migration Summary:</h3>";
echo "Migrated: $migrated<br>";
echo "Skipped (already exists): $skipped<br>";
echo "Errors: $errors<br>";
echo "<br>Done!";
