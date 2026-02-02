<?php
// Utiliser les constantes de configuration
$cheminStyles = CHEMIN_STYLES;
$cheminIncludes = CHEMIN_INCLUDES;
$cheminRacine = CHEMIN_RACINE;

// Déterminer le préfixe selon l'emplacement
$pageActuelle = basename($_SERVER['PHP_SELF']);
$estDansPages = strpos($_SERVER['PHP_SELF'], 'pages-principales') !== false;
$prefixe = $estDansPages ? '../' : '';

// Calculer le chemin vers la racine pour les liens
$cheminVersRacine = $estDansPages ? '../' : './';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titrePage) ? $titrePage . ' - ' . NOM_APPLICATION : NOM_APPLICATION; ?></title>

    <!-- Styles avec chemins relatifs corrects -->
    <link rel="stylesheet" href="<?php echo $prefixe; ?>styles/style-general.css">
    <link rel="stylesheet" href="<?php echo $prefixe; ?>styles/style-base.css">

    <?php
    // Styles spécifiques selon la page
    if ($pageActuelle == 'connexion.php') {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-connexion.css">';
    } elseif ($pageActuelle == 'tableau-de-bord.php') {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-tableau-de-bord.css">';
    } elseif ($pageActuelle == 'details-joueur.php') {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-details-joueur.css">';
    } elseif ($pageActuelle == 'details-match.php') {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-details-match.css">';
    } elseif ($pageActuelle == 'gestion-matchs.php') {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-gestion-matchs.css">';
    } elseif ($pageActuelle == 'compositions-equipe.php') {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-composer-equipe.css">';
    } elseif ($pageActuelle == 'evaluations-joueurs.php') {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-evaluations.css">';
    } elseif (strpos($pageActuelle, 'match') !== false) {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-formulaire.css">';
    } elseif (strpos($pageActuelle, 'joueur') !== false) {
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-joueurs.css">';
        echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-formulaire.css">';
    } elseif ($pageActuelle == 'statistiques-equipe.php') {
      echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-statistiques.css">';
    }

    // Toujours charger
    echo '<link rel="stylesheet" href="' . $prefixe . 'styles/style-tableaux.css">';
    ?>

    <!-- Icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Styles de base -->
    <link rel="stylesheet" href="<?php echo $prefixe; ?>styles/style-enetete.css">
</head>
<body>
    <?php
    // Inclure la navigation sauf sur la page de connexion
    if ($pageActuelle !== 'connexion.php' && Authentification::estConnecte()) {
        $cheminNavigation = $cheminIncludes . '/navigation.php';
        if (file_exists($cheminNavigation)) {
            include $cheminNavigation;
        }
    }
    ?>

    <div class="conteneur-principal">
        <main class="contenu-page">
            <header class="en-tete-page">
                <h1><?php echo isset($titrePage) ? $titrePage : NOM_APPLICATION; ?></h1>
                <?php if (isset($descriptionPage)): ?>
                    <p class="description-page"><?php echo $descriptionPage; ?></p>
                <?php endif; ?>
            </header>

            <!-- Messages d'alerte -->
            <?php if (isset($messageErreur) && !empty($messageErreur)): ?>
                <div class="message-erreur">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $messageErreur; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($messageSucces) && !empty($messageSucces)): ?>
                <div class="message-succes">
                    <i class="fas fa-check-circle"></i> <?php echo $messageSucces; ?>
                </div>
            <?php endif; ?>
