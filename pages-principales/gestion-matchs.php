<?php
// pages-principales/gestion-matchs.php
require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

Authentification::verifierConnexion();

$titrePage = 'Gestion des Matchs';
$descriptionPage = 'Gérer les matchs de l\'équipe';

// création de l'objet pour communiquer avec la base de données
$fonctionsBDD = new FonctionsBaseDeDonnees();

// messages destinés à l'utilisateur
$messageSucces = '';
$messageErreur = '';

// récupération du filtre actif depuis l'url
// par défaut : afficher tous les matchs
$filtreActif = $_GET['filtre'] ?? 'tous';

// récupération des matchs selon le filtre sélectionné
$matchs = $fonctionsBDD->obtenirMatchs($filtreActif);

// récupération des statistiques globales sur les matchs
$statsGlobales = $fonctionsBDD->obtenirStatistiquesMatchs();

// gestion de la suppression d'un match
if (isset($_GET['supprimer'])) {

    // sécurisation de l'id du match à supprimer
    $id = intval($_GET['supprimer']);

    try {
        // vérifie que le match existe
        $match = $fonctionsBDD->obtenirMatchParId($id);

        if ($match) {
            // suppression du match
            $resultat = $fonctionsBDD->supprimerMatch($id);

            if ($resultat) {
                // message de succès
                $messageSucces = 'Le match contre ' . $match['equipe_adverse'] . ' a été supprimé avec succès.';

                // redirection pour éviter la resoumission
                header('Location: gestion-matchs.php?success=1');
                exit();
            } else {
                // message d'erreur si la suppression échoue
                $messageErreur = 'Erreur lors de la suppression du match.';
            }
        } else {
            // message si le match n'existe pas
            $messageErreur = 'Match non trouvé.';
        }
    } catch (Exception $e) {
        // message en cas d'erreur technique
        $messageErreur = 'Erreur technique: ' . $e->getMessage();
    }
}

// message affiché après une redirection réussie
if (isset($_GET['success'])) {
    $messageSucces = 'Opération effectuée avec succès !';
}

include CHEMIN_INCLUDES . '/entete.php';
?>

<div class="conteneur-gestion-matchs">
    <!-- En-tête avec statistiques -->
    <div class="entete-matchs">
        <div class="titre-section">
            <h1><i class="fas fa-futbol"></i> Gestion des Matchs</h1>
            <a href="ajouter-match.php" class="bouton-principal">
                <i class="fas fa-plus-circle"></i> Ajouter un match
            </a>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-rapides">
            <div class="stat-card">
                <i class="fas fa-trophy icon-stat victoire"></i>
                <div class="stat-info">
                    <span class="stat-valeur"><?php echo $statsGlobales['victoires'] ?? 0; ?></span>
                    <span class="stat-label">Victoires</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle icon-stat defaite"></i>
                <div class="stat-info">
                    <span class="stat-valeur"><?php echo $statsGlobales['defaites'] ?? 0; ?></span>
                    <span class="stat-label">Défaites</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-equals icon-stat nul"></i>
                <div class="stat-info">
                    <span class="stat-valeur"><?php echo $statsGlobales['nuls'] ?? 0; ?></span>
                    <span class="stat-label">Nuls</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check icon-stat a-venir"></i>
                <div class="stat-info">
                    <span class="stat-valeur"><?php echo $statsGlobales['a_venir'] ?? 0; ?></span>
                    <span class="stat-label">À venir</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filtres-matchs">
        <a href="?filtre=tous" class="filtre-btn <?php echo $filtreActif == 'tous' ? 'actif' : ''; ?>">
            <i class="fas fa-list"></i> Tous (<?php echo $statsGlobales['total_matchs'] ?? 0; ?>)
        </a>
        <a href="?filtre=a-venir" class="filtre-btn <?php echo $filtreActif == 'a-venir' ? 'actif' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> À venir (<?php echo $statsGlobales['a_venir'] ?? 0; ?>)
        </a>
        <a href="?filtre=passes" class="filtre-btn <?php echo $filtreActif == 'passes' ? 'actif' : ''; ?>">
            <i class="fas fa-history"></i> Passés (<?php echo $statsGlobales['passes'] ?? 0; ?>)
        </a>
        <a href="?filtre=victoires" class="filtre-btn <?php echo $filtreActif == 'victoires' ? 'actif' : ''; ?>">
            <i class="fas fa-trophy"></i> Victoires (<?php echo $statsGlobales['victoires'] ?? 0; ?>)
        </a>
        <a href="?filtre=defaites" class="filtre-btn <?php echo $filtreActif == 'defaites' ? 'actif' : ''; ?>">
            <i class="fas fa-times"></i> Défaites (<?php echo $statsGlobales['defaites'] ?? 0; ?>)
        </a>
        <a href="?filtre=domicile" class="filtre-btn <?php echo $filtreActif == 'domicile' ? 'actif' : ''; ?>">
            <i class="fas fa-home"></i> Domicile (<?php echo $statsGlobales['domicile'] ?? 0; ?>)
        </a>
        <a href="?filtre=exterieur" class="filtre-btn <?php echo $filtreActif == 'exterieur' ? 'actif' : ''; ?>">
            <i class="fas fa-plane"></i> Extérieur (<?php echo $statsGlobales['exterieur'] ?? 0; ?>)
        </a>
    </div>

    <!-- Liste des matchs -->
<?php if (!empty($matchs)): ?>
<div class="grille-matchs">
    <?php
    // Tableau des mois en français
    $moisFrancais = [
        1 => 'JANVIER', 2 => 'FÉVRIER', 3 => 'MARS', 4 => 'AVRIL',
        5 => 'MAI', 6 => 'JUIN', 7 => 'JUILLET', 8 => 'AOÛT',
        9 => 'SEPTEMBRE', 10 => 'OCTOBRE', 11 => 'NOVEMBRE', 12 => 'DÉCEMBRE'
    ];

    foreach ($matchs as $match):
        $dateMatch = new DateTime($match['date_heure_match']);
        $aujourdhui = new DateTime();
        $estFutur = $dateMatch >= $aujourdhui;
        $estAujourdhui = $dateMatch->format('Y-m-d') == $aujourdhui->format('Y-m-d');

        // Formater la date en français
        $jour = $dateMatch->format('d');
        $mois = $moisFrancais[(int)$dateMatch->format('n')];
        $annee = $dateMatch->format('Y');
        $dateFormatee = "$jour $mois $annee";

        // Formater l'heure
        $heureFormatee = date('H\hi', strtotime($match['date_heure_match']));

        // Compter les joueurs sélectionnés
        $nbJoueurs = $fonctionsBDD->compterJoueursMatch($match['id_match']);
    ?>
    <div class="carte-match <?php echo $estFutur ? 'match-futur' : 'match-passe'; ?>">
        <!-- Badge de date -->
        <div class="badge-date <?php echo $estAujourdhui ? 'aujourdhui' : ''; ?>">
            <?php if ($estAujourdhui): ?>
                <i class="fas fa-star"></i> AUJOURD'HUI
            <?php else: ?>
                <?php echo $dateFormatee; ?>
            <?php endif; ?>
        </div>

            <!-- En-tête du match -->
            <div class="entete-match">
                <div class="heure-match">
                    <i class="fas fa-clock"></i>
                    <?php echo date('H:i', strtotime($match['date_heure_match'])); ?>
                </div>
                <span class="badge-type badge-<?php echo strtolower($match['lieu_match']); ?>">
                    <?php echo $match['lieu_match'] == 'Domicile' ? '<i class="fas fa-home"></i>' : '<i class="fas fa-plane"></i>'; ?>
                    <?php echo $match['lieu_match']; ?>
                </span>
            </div>

            <!-- Adversaire -->
            <div class="adversaire-section">
                <div class="vs-badge">VS</div>
                <h3 class="nom-adversaire"><?php echo htmlspecialchars($match['equipe_adverse']); ?></h3>
            </div>

            <!-- Score et résultat -->
            <?php if (!empty($match['resultat_match']) && $match['resultat_match'] != 'À venir'): ?>
            <div class="score-section">
                <div class="score-display">
                    <span class="score-equipe"><?php echo $match['score_equipe']; ?></span>
                    <span class="separateur">-</span>
                    <span class="score-adversaire"><?php echo $match['score_adverse']; ?></span>
                </div>
                <span class="badge-resultat badge-<?php echo strtolower($match['resultat_match']); ?>">
                    <?php
                    if ($match['resultat_match'] == 'Victoire') echo '<i class="fas fa-trophy"></i>';
                    elseif ($match['resultat_match'] == 'Défaite') echo '<i class="fas fa-times"></i>';
                    else echo '<i class="fas fa-equals"></i>';
                    ?>
                    <?php echo $match['resultat_match']; ?>
                </span>
            </div>
            <?php else: ?>
            <div class="score-section en-attente">
                <i class="fas fa-hourglass-half"></i>
                <span>Match à venir</span>
            </div>
            <?php endif; ?>

            <!-- Informations complémentaires -->
            <div class="info-match">
                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <span><?php echo $nbJoueurs; ?> joueur<?php echo $nbJoueurs > 1 ? 's' : ''; ?> sélectionné<?php echo $nbJoueurs > 1 ? 's' : ''; ?></span>
                </div>
                <?php if (!empty($match['commentaires_match'])): ?>
                <div class="info-item">
                    <i class="fas fa-comment"></i>
                    <span>Commentaire disponible</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="actions-match">
                <?php if ($estFutur): ?>
                <a href="compositions-equipe.php?id=<?php echo $match['id_match']; ?>"
                   class="btn-action btn-composer" title="Composer l'équipe">
                    <i class="fas fa-users-cog"></i> Composer
                </a>
                <?php else: ?>
                <a href="evaluations-joueurs.php?match=<?php echo $match['id_match']; ?>"
                   class="btn-action btn-evaluer" title="Évaluer les joueurs">
                    <i class="fas fa-star"></i> Évaluer
                </a>
                <?php endif; ?>

                <a href="details-match.php?id=<?php echo $match['id_match']; ?>"
                   class="btn-action btn-details" title="Voir détails">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="modifier-match.php?id=<?php echo $match['id_match']; ?>"
                   class="btn-action btn-modifier" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="?supprimer=<?php echo $match['id_match']; ?>"
                   class="btn-action btn-supprimer"
                   title="Supprimer"
                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce match contre <?php echo addslashes($match['equipe_adverse']); ?> ?');">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="aucune-donnee">
        <div class="illustration-vide">
            <i class="fas fa-calendar-times"></i>
        </div>
        <h3>Aucun match <?php echo $filtreActif != 'tous' ? 'dans cette catégorie' : 'enregistré'; ?></h3>
        <p>
            <?php if ($filtreActif == 'tous'): ?>
                Commencez par ajouter des matchs au calendrier.
            <?php else: ?>
                Essayez un autre filtre ou ajoutez de nouveaux matchs.
            <?php endif; ?>
        </p>
        <a href="ajouter-match.php" class="bouton-principal">
            <i class="fas fa-plus-circle"></i> Ajouter un match
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
