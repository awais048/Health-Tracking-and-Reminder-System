<?php
// Database connection parameters
$host = 'localhost';     // Database host, usually localhost
$dbname = 'healthcare';   // Name of your database
$username = 'root';       // Database username
$password = '';           // Database password, if set

// Create a new PDO instance for database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set PDO error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optionally, you can uncomment the line below for debugging purposes
    // echo "Connected successfully"; 

} catch(PDOException $e) {
    // Handle connection errors
    echo "Connection failed: " . $e->getMessage();
}
?>
