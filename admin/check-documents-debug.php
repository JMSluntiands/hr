<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Document Files Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Document Files Debug</h1>
    
    <h2>1. Check document_files table structure:</h2>
    <?php
    $cols = $conn->query("SHOW COLUMNS FROM document_files");
    if ($cols) {
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($c = $cols->fetch_assoc()) {
            echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Key']}</td><td>{$c['Default']}</td></tr>";
        }
        echo "</table>";
    }
    ?>
    
    <h2>2. Approved employee_document_uploads:</h2>
    <?php
    $r = $conn->query("SELECT COUNT(*) as cnt FROM employee_document_uploads WHERE status = 'Approved'");
    if ($r) {
        $row = $r->fetch_assoc();
        echo "<p>Count: " . $row['cnt'] . "</p>";
        if ($row['cnt'] > 0) {
            $list = $conn->query("SELECT id, employee_id, document_type, file_path, approved_by, approved_by_name, approved_at FROM employee_document_uploads WHERE status = 'Approved' LIMIT 10");
            echo "<table><tr><th>ID</th><th>Employee ID</th><th>Type</th><th>File Path</th><th>Approved By</th><th>Approved At</th></tr>";
            while ($l = $list->fetch_assoc()) {
                echo "<tr><td>{$l['id']}</td><td>{$l['employee_id']}</td><td>{$l['document_type']}</td><td>{$l['file_path']}</td><td>{$l['approved_by_name']}</td><td>{$l['approved_at']}</td></tr>";
            }
            echo "</table>";
        }
    }
    ?>
    
    <h2>3. Approved document_requests:</h2>
    <?php
    $r2 = $conn->query("SELECT COUNT(*) as cnt FROM document_requests WHERE status = 'Approved'");
    if ($r2) {
        $row2 = $r2->fetch_assoc();
        echo "<p>Count: " . $row2['cnt'] . "</p>";
        if ($row2['cnt'] > 0) {
            $list2 = $conn->query("SELECT id, employee_id, document_type, approved_by, approved_by_name, approved_at FROM document_requests WHERE status = 'Approved' LIMIT 10");
            echo "<table><tr><th>ID</th><th>Employee ID</th><th>Type</th><th>Approved By</th><th>Approved At</th></tr>";
            while ($l2 = $list2->fetch_assoc()) {
                echo "<tr><td>{$l2['id']}</td><td>{$l2['employee_id']}</td><td>{$l2['document_type']}</td><td>{$l2['approved_by_name']}</td><td>{$l2['approved_at']}</td></tr>";
            }
            echo "</table>";
        }
    }
    ?>
    
    <h2>4. Current document_files:</h2>
    <?php
    $r3 = $conn->query("SELECT COUNT(*) as cnt FROM document_files");
    if ($r3) {
        $row3 = $r3->fetch_assoc();
        echo "<p>Count: " . $row3['cnt'] . "</p>";
        if ($row3['cnt'] > 0) {
            $list3 = $conn->query("SELECT id, employee_id, document_type, file_path, approved_by_name, approved_at FROM document_files LIMIT 10");
            echo "<table><tr><th>ID</th><th>Employee ID</th><th>Type</th><th>File Path</th><th>Approved By</th><th>Approved At</th></tr>";
            while ($l3 = $list3->fetch_assoc()) {
                echo "<tr><td>{$l3['id']}</td><td>{$l3['employee_id']}</td><td>{$l3['document_type']}</td><td>{$l3['file_path']}</td><td>{$l3['approved_by_name']}</td><td>{$l3['approved_at']}</td></tr>";
            }
            echo "</table>";
        }
    }
    ?>
    
    <h2>5. Test Insert:</h2>
    <?php
    // Try a test insert
    $testEmp = $conn->query("SELECT id FROM employees LIMIT 1");
    if ($testEmp && $testRow = $testEmp->fetch_assoc()) {
        $testId = $testRow['id'];
        $testStmt = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        if ($testStmt) {
            $testType = 'TEST_DOCUMENT';
            $testPath = 'test/path.txt';
            $testAdmin = 1;
            $testName = 'Test Admin';
            $testStmt->bind_param('issss', $testId, $testType, $testPath, $testAdmin, $testName);
            if ($testStmt->execute()) {
                echo "<p style='color:green'>✓ Test insert successful!</p>";
                // Delete test record
                $conn->query("DELETE FROM document_files WHERE document_type = 'TEST_DOCUMENT'");
            } else {
                echo "<p style='color:red'>✗ Test insert failed: " . $testStmt->error . "</p>";
            }
            $testStmt->close();
        } else {
            echo "<p style='color:red'>✗ Failed to prepare test insert: " . $conn->error . "</p>";
        }
    }
    ?>
    
    <p><a href="request-document-file.php">Back to Document File</a></p>
    <p><a href="database/migrate_approved_documents.php">Run Migration Script</a></p>
</body>
</html>
