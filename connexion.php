<?php
// Fichiers nécessaires
require_once 'configuration/config.php';
require_once 'base-de-donnees/fonctions-bdd.php';
require_once 'securite/authentification.php';

// Si déjà connecté, rediriger vers le tableau de bord
if (Authentification::estConnecte()) {
    header('Location: pages-principales/tableau-de-bord.php');
    exit();
}
// Variable pour les messages d'erreur
$messageErreur = '';

// Traitement du formulaire
// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données du formulaire
    $email = trim($_POST['email_entraineur'] ?? '');
    $motDePasse = $_POST['mot_de_passe_entraineur'] ?? '';

    // Vérifier les champs obligatoires
    // Si des champs sont vides, afficher un message d'erreur
    if (empty($email) || empty($motDePasse)) {
        $messageErreur = 'Veuillez remplir tous les champs';
    } else {
        // Tenter de connecter l'entraîneur
        $auth = new Authentification();
        // Si la connexion réussit, rediriger vers le tableau de bord
        if ($auth->connecterEntraineur($email, $motDePasse)) {
            header('Location: pages-principales/tableau-de-bord.php');
            exit();
        } else {
            // Sinon, afficher un message d'erreur
            $messageErreur = 'Email ou mot de passe incorrect';
        }
    }
}

// Affichage du formulaire de connexion
// Le HTML commence ici
// Le formulaire utilise la méthode POST pour envoyer les données
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Manager d'Équipe</title>
    <link rel="stylesheet" href="styles/style-connexion.css">
    <link rel="stylesheet" href="styles/style-general.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<!-- Corps de la page de connexion -->
<body class="page-connexion">
    <div class="carte-connexion">
        <div class="entete-connexion">
            <div class="logo-equipe">
                <i class="fas fa-futbol"></i>
            </div>
            <h1>Manager d'Équipe de Football</h1>
            <p>Application de gestion pour entraîneurs</p>
        </div>

        <div class="formulaire-connexion">
            <?php if ($messageErreur): ?>
                <div class="message-erreur">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $messageErreur; ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de connexion avec champs pour email et mot de passe -->
            <form method="POST" action="">
                <div class="groupe-formulaire-connexion">
                    <label for="email_entraineur">
                        <i class="fas fa-envelope"></i> Adresse email
                    </label>
                    <input type="email"
                           id="email_entraineur"
                           name="email_entraineur"
                           required
                           placeholder="admin@equipe.com"
                           value="<?php echo htmlspecialchars($_POST['email_entraineur'] ?? ''); ?>">
                </div>

                <div class="groupe-formulaire-connexion">
                    <label for="mot_de_passe_entraineur">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <input type="password"
                           id="mot_de_passe_entraineur"
                           name="mot_de_passe_entraineur"
                           required
                           placeholder="Votre mot de passe">
                </div>

                <div class="groupe-formulaire-connexion">
                    <button type="submit" class="bouton-connexion">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </div>
            </form>

            <div class="informations-connexion">
                <h3><i class="fas fa-info-circle"></i> Instructions</h3>
                <p>1. <strong>Identifiant :</strong> monsieur.entraineur@club.com</p>
                <p>2. <strong>Mot de passe :</strong> entraineur123</p>
            </div>
        </div>
    </div>
</body>
</html>
