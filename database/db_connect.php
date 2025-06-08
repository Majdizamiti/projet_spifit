<?php
// Database connection parameters
$host = 'localhost';
$dbname = 'spifit';
$username = 'root';
$password = '';

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set charset to utf8mb4
    $pdo->exec("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    // Log the error (in a production environment, you should log this to a file)
    error_log("Connection failed: " . $e->getMessage());
    
    // Show a user-friendly message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}
?> 