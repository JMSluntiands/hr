<?php
$host = "localhost";
$username = "u596880582_testdev";
$password = "@Navsanne2018";
$dbname = "u596880582_testdev";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

