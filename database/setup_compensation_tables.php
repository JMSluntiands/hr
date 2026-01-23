<?php
/**
 * Setup script for compensation-related tables
 * Run this once to create the necessary tables
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed");
}

// Read and execute SQL file
$sqlFile = __DIR__ . '/create_compensation_tables.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement)) {
        continue;
    }
    
    if ($conn->query($statement)) {
        echo "✓ Executed successfully\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
        echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
    }
}

echo "\nSetup completed!\n";
$conn->close();
?>
