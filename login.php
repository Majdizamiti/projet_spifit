<?php
// login.php

// Démarrer la session
session_start();

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

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validation des données
    $errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est requise";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    }

    // Si aucune erreur, on vérifie les identifiants
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Authentification réussie
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Redirection vers la page d'accueil ou dashboard
            header("Location: page_accueil.html");
            exit();
        } else {
            $errors[] = "Email ou mot de passe incorrect";
        }
    }
}

// Message de succès après inscription
$registrationSuccess = isset($_GET['registration']) && $_GET['registration'] === 'success';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connexion</title>
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

    <?php if ($registrationSuccess): ?>
        <div class="success-message">
            <p>Inscription réussie! Vous pouvez maintenant vous connecter.</p>
        </div>
    <?php endif; ?>

    <div class="form-container sign-in-container">
        <form action="login.php" method="POST">
            <h1>Sign in</h1>
            <input type="email" name="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>
