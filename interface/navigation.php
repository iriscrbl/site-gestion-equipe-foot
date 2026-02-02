<?php
// Vérifier la connexion
if (!Authentification::estConnecte()) {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], 'pages-principales') !== false ? '../' : '') . 'connexion.php');
    exit();
}

// Déterminer la page active et les préfixes
$pageActuelle = basename($_SERVER['PHP_SELF']);
$estDansPages = strpos($_SERVER['PHP_SELF'], 'pages-principales') !== false;
$prefixeLien = $estDansPages ? '' : 'pages-principales/';
$prefixeRacine = $estDansPages ? '../' : './';
?>
<nav class="navigation-principale">
    <div class="logo-navigation">
        <div class="icone-logo">⚽</div>
        <div class="texte-logo">
            <h1><?php echo NOM_APPLICATION; ?></h1>
            <p><?php echo NOM_EQUIPE; ?></p>
        </div>
    </div>

    <ul class="menu-principal">
        <li class="<?php echo $pageActuelle == 'tableau-de-bord.php' ? 'actif' : ''; ?>">
            <a href="<?php echo $prefixeLien; ?>tableau-de-bord.php">
                <i class="fas fa-home"></i>
                <span>Tableau de bord</span>
            </a>
        </li>

        <li class="<?php echo in_array($pageActuelle, ['gestion-joueurs.php', 'ajouter-joueur.php', 'modifier-joueur.php', 'details-joueur.php']) ? 'actif' : ''; ?>">
            <a href="<?php echo $prefixeLien; ?>gestion-joueurs.php">
                <i class="fas fa-users"></i>
                <span>Joueurs</span>
            </a>
            <ul class="sous-menu">
                <li><a href="<?php echo $prefixeLien; ?>gestion-joueurs.php">Liste des joueurs</a></li>
                <li><a href="<?php echo $prefixeLien; ?>ajouter-joueur.php">Ajouter un joueur</a></li>
            </ul>
        </li>

        <li class="<?php echo in_array($pageActuelle, ['gestion-matchs.php', 'ajouter-match.php', 'modifier-match.php', 'details-match.php', 'compositions-equipe.php']) ? 'actif' : ''; ?>">
            <a href="<?php echo $prefixeLien; ?>gestion-matchs.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Matchs</span>
            </a>
            <ul class="sous-menu">
                <li><a href="<?php echo $prefixeLien; ?>gestion-matchs.php">Liste des matchs</a></li>
                <li><a href="<?php echo $prefixeLien; ?>ajouter-match.php">Ajouter un match</a></li>
            </ul>
        </li>

        <li class="<?php echo $pageActuelle == 'evaluations-joueurs.php' ? 'actif' : ''; ?>">
            <a href="<?php echo $prefixeLien; ?>evaluations-joueurs.php">
                <i class="fas fa-star"></i>
                <span>Évaluations</span>
            </a>
        </li>

        <li class="<?php echo $pageActuelle == 'statistiques-equipe.php' ? 'actif' : ''; ?>">
            <a href="<?php echo $prefixeLien; ?>statistiques-equipe.php">
                <i class="fas fa-chart-bar"></i>
                <span>Statistiques</span>
            </a>
        </li>
    </ul>

    <div class="profil-entraineur">
        <div class="info-profil">
            <div class="avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="details">
                <strong><?php echo htmlspecialchars($_SESSION['nom_entraineur'] . ' ' . $_SESSION['prenom_entraineur']); ?></strong>
                <small>Entraîneur</small>
            </div>
        </div>
        <a href="<?php echo $prefixeRacine; ?>deconnexion.php" class="btn-deconnexion">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</nav>

<!-- Inclure le fichier CSS -->
<link rel="stylesheet" href="<?php echo $prefixeRacine; ?>styles/style-navigation.css">
