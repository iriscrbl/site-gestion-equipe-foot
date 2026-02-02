<?php
// pages-principales/tableau-de-bord.php
require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

// vérifie que l’utilisateur est connecté avant d’accéder au tableau de bord
Authentification::verifierConnexion();

// informations utilisées par l’en-tête de la page
$titrePage = 'Tableau de bord';
$descriptionPage = 'Vue d\'ensemble de votre équipe';

// création de l’objet permettant de récupérer les données depuis la base
$fonctionsBDD = new FonctionsBaseDeDonnees();

// Récupérer les statistiques du tableau de bord
// ces statistiques regroupent joueurs, matchs et états généraux
$statistiques = $fonctionsBDD->obtenirStatistiquesDashboard();

// Calculer les pourcentages
// calcul du pourcentage de victoires en évitant une division par zéro
$pourcentageVictoires = $statistiques['total_matchs'] > 0 ?
    round(($statistiques['matchs_gagnes'] / $statistiques['total_matchs']) * 100, 1) : 0;

// Récupérer les prochains matchs
// initialisation par défaut pour éviter les erreurs d’affichage
$prochainsMatchs = [];
try {
    // récupération des 5 prochains matchs à venir
    $prochainsMatchs = $fonctionsBDD->obtenirProchainsMatchs(5);
} catch (Exception $e) {
    // Ignorer l'erreur, laisser le tableau vide
    // permet d’éviter un crash si la requête échoue
}

// Récupérer les derniers résultats
// tableau des derniers matchs terminés
$derniersResultats = [];
try {
    // récupération des 5 derniers résultats
    $derniersResultats = $fonctionsBDD->obtenirDerniersResultats(5);
} catch (Exception $e) {
    // Ignorer l'erreur, laisser le tableau vide
}

// Récupérer les derniers joueurs ajoutés
// permet d’afficher rapidement les nouvelles recrues
$derniersJoueurs = [];
try {
    // récupération des 5 derniers joueurs ajoutés en base
    $derniersJoueurs = $fonctionsBDD->obtenirDerniersJoueurs(5);
} catch (Exception $e) {
    // Ignorer l'erreur, laisser le tableau vide
}

// inclusion de l’en-tête html
include CHEMIN_INCLUDES . '/entete.php';
?>

<div class="cartes-statistiques">
    <div class="carte-statistique">
        <h3>Joueurs</h3>
        <div class="valeur-statistique"><?php echo $statistiques['total_joueurs']; ?></div>
        <p class="variation-statistique"><?php echo $statistiques['joueurs_actifs']; ?> actifs</p>
    </div>

    <div class="carte-statistique">
        <h3>Matchs joués</h3>
        <div class="valeur-statistique"><?php echo $statistiques['total_matchs']; ?></div>
        <p class="variation-statistique"><?php echo $pourcentageVictoires; ?>% de victoires</p>
    </div>

    <div class="carte-statistique">
        <h3>Prochains matchs</h3>
        <div class="valeur-statistique"><?php echo $statistiques['prochains_matchs']; ?></div>
        <p class="variation-statistique">À préparer</p>
    </div>

    <div class="carte-statistique">
        <h3>Joueurs blessés</h3>
        <div class="valeur-statistique"><?php echo $statistiques['joueurs_blesses']; ?></div>
        <p class="variation-statistique">En observation</p>
    </div>
</div>

<div class="tableaux-dashboard">
    <div class="tableau-section">
        <h2>Prochains matchs</h2>
        <?php if (!empty($prochainsMatchs)): ?>
            <div class="tableau-donnees">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Adversaire</th>
                            <th>Lieu</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prochainsMatchs as $match): ?>
                            <?php $dateMatch = new DateTime($match['date_heure_match']); ?>
                            <tr>
                                <td><?php echo $dateMatch->format('d/m/Y H:i'); ?></td>
                                <td><?php echo htmlspecialchars($match['equipe_adverse']); ?></td>
                                <td>
                                    <span class="badge <?php echo $match['lieu_match'] == 'Domicile' ? 'badge-domicile' : 'badge-exterieur'; ?>">
                                        <?php echo $match['lieu_match']; ?>
                                    </span>
                                </td>
                                <td class="actions-tableau">
                                    <a href="compositions-equipe.php?id=<?php echo $match['id_match']; ?>" class="bouton-action bouton-voir">Composer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="message-aucune-donnee">Aucun match à venir pour le moment.</p>
        <?php endif; ?>
    </div>

    <div class="tableau-section">
        <h2>Derniers résultats</h2>
        <?php if (!empty($derniersResultats)): ?>
            <div class="tableau-donnees">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Adversaire</th>
                            <th>Score</th>
                            <th>Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derniersResultats as $match): ?>
                            <?php
                            $dateMatch = new DateTime($match['date_heure_match']);
                            $resultatClass = '';
                            switch ($match['resultat_match']) {
                                case 'Victoire': $resultatClass = 'badge-victoire'; break;
                                case 'Défaite': $resultatClass = 'badge-defaite'; break;
                                case 'Nul': $resultatClass = 'badge-nul'; break;
                            }
                            ?>
                            <tr>
                                <td><?php echo $dateMatch->format('d/m/Y'); ?></td>
                                <td><?php echo htmlspecialchars($match['equipe_adverse']); ?></td>
                                <td><?php echo $match['score_equipe']; ?> - <?php echo $match['score_adverse']; ?></td>
                                <td>
                                    <span class="badge <?php echo $resultatClass; ?>">
                                        <?php echo $match['resultat_match']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="message-aucune-donnee">Aucun match terminé pour le moment.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($derniersJoueurs)): ?>
<div class="tableau-section">
    <h2>Derniers joueurs ajoutés</h2>
    <div class="tableau-donnees">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Âge</th>
                    <th>Taille</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($derniersJoueurs as $joueur): ?>
                    <?php
                    $dateNaissance = new DateTime($joueur['date_naissance']);
                    $age = (new DateTime())->diff($dateNaissance)->y;
                    $statutClass = 'badge-' . strtolower($joueur['statut_joueur']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($joueur['nom_joueur']); ?></td>
                        <td><?php echo htmlspecialchars($joueur['prenom_joueur']); ?></td>
                        <td><?php echo $age; ?> ans</td>
                        <td><?php echo $joueur['taille_cm']; ?> cm</td>
                        <td>
                            <span class="badge <?php echo $statutClass; ?>">
                                <?php echo $joueur['statut_joueur']; ?>
                            </span>
                        </td>
                        <td class="actions-tableau">
                            <a href="modifier-joueur.php?id=<?php echo $joueur['id_joueur']; ?>" class="bouton-action bouton-modifier">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="actions-tableau-centre">
        <a href="gestion-joueurs.php" class="bouton-secondaire">Voir tous les joueurs</a>
    </div>
</div>
<?php endif; ?>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
