<?php
// view_calculations.php

// Database connection
$host = 'localhost';
$dbname = 'spifit';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fetch all calculations from the database
$stmt = $pdo->query("SELECT * FROM calculations ORDER BY created_at DESC");
$calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Calculations</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Fitness Calculations</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Age</th>
                <th>Weight (kg)</th>
                <th>Size (cm)</th>
                <th>Gender</th>
                <th>Activity Factor</th>
                <th>BMI</th>
                <th>Result</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($calculations as $calc): ?>
                <tr>
                    <td><?php echo $calc['id']; ?></td>
                    <td><?php echo $calc['age']; ?></td>
                    <td><?php echo $calc['weight']; ?></td>
                    <td><?php echo $calc['size']; ?></td>
                    <td><?php echo $calc['gender']; ?></td>
                    <td><?php echo $calc['activity']; ?></td>
                    <td><?php echo $calc['bmi']; ?></td>
                    <td><?php echo $calc['result']; ?></td>
                    <td><?php echo $calc['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html> 