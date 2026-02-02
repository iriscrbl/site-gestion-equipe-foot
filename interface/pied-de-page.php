<?php
// Déterminer le préfixe selon l'emplacement
$pageActuelle = basename($_SERVER['PHP_SELF']);
$estDansPages = strpos($_SERVER['PHP_SELF'], 'pages-principales') !== false;
$prefixe = $estDansPages ? '../' : '';
?>
        </main>
    </div>

    <!-- Pied de page -->
    <footer class="pied-de-page">
        <div class="conteneur-pied">
            <div class="info-application">
                <p class="nom-application"><?php echo NOM_APPLICATION; ?> v<?php echo VERSION_APPLICATION; ?></p>
                <p class="copyright">&copy; <?php echo date('Y'); ?> <?php echo NOM_EQUIPE; ?></p>
            </div>

            <?php if (Authentification::estConnecte()): ?>
            <div class="info-utilisateur">
                <p class="nom-utilisateur">
                    <strong><?php echo htmlspecialchars($_SESSION['prenom_entraineur'] . ' ' . $_SESSION['nom_entraineur']); ?></strong>
                </p>
                <p class="email-utilisateur">
                    <?php echo htmlspecialchars($_SESSION['email_entraineur']); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </footer>

    <!-- Scripts JavaScript -->
    <script src="<?php echo $prefixe; ?>scripts/script-utilitaires.js"></script>

    <?php
    // Scripts spécifiques selon la page
    if ($pageActuelle == 'compositions-equipe.php') {
        echo '<script src="' . $prefixe . 'scripts/script-composer-equipe.js"></script>';
    } elseif ($pageActuelle == 'gestion-joueurs.php') {
        echo '<script src="' . $prefixe . 'scripts/script-joueurs.js"></script>';
    } elseif ($pageActuelle == 'gestion-matchs.php') {
        echo '<script src="' . $prefixe . 'scripts/script-matchs.js"></script>';
    } elseif ($pageActuelle == 'statistiques-equipe.php') {
        echo '<script src="' . $prefixe . 'scripts/script-statistiques.js"></script>';
    }
    ?>

    <!-- Inclure le CSS du pied de page -->
    <link rel="stylesheet" href="<?php echo $prefixe; ?>styles/style-pied-de-page.css">
</body>
</html>
