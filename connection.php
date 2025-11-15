<?php
// connection.php
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "DCElectricals";

try {
    // Create connection using mysqli
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8 for proper character handling
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>