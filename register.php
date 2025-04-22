<?php
// register.php

// Configuration de la base de données
$host = 'localhost';
$dbname = 'spifit';
$username = 'root';
$password = '';

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validation des données
    $errors = [];

    if (empty($name)) {
        $errors[] = "Le nom est requis";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est requise";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }

    // Vérification si l'email existe déjà
    $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Cette adresse email est déjà utilisée";
    }

    // Si aucune erreur, on procède à l'inscription
    if (empty($errors)) {
        // Hashage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insertion dans la base de données
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);

        // Redirection vers la page de login avec un message de succès
        header("Location: login.php?registration=success");
        exit();
    }
}

// Si des erreurs ou pour afficher le formulaire
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inscription</title>
    <link rel="stylesheet" href="logincss.css">
</head>
<body>
<div class="container">
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="form-container sign-up-container">
        <form action="register.php" method="POST">
            <h1>Create Account</h1>
            <input type="text" name="name" placeholder="Name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" />
            <input type="email" name="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Sign Up</button>
        </form>
    </div>
</div>
</body>
</html>