<?php
// pages-principales/statistiques-equipe.php
require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

// vérification que l’utilisateur est connecté
Authentification::verifierConnexion();

// informations utilisées pour l’en-tête de la page
$titrePage = 'Statistiques de l\'Équipe';
$descriptionPage = 'Vue d\'ensemble des performances et statistiques complètes';

// création de l’objet permettant d’interagir avec la base de données
$fonctionsBDD = new FonctionsBaseDeDonnees();

// ==================== STATISTIQUES GLOBALES ====================
// récupération des statistiques globales de l’équipe depuis la base
$statsGlobales = $fonctionsBDD->obtenirStatistiquesGlobalesEquipe();

// récupération des valeurs principales avec valeur par défaut si absente
$totalMatchs = $statsGlobales['total_matchs'] ?? 0;
$victoires = $statsGlobales['victoires'] ?? 0;
$defaites = $statsGlobales['defaites'] ?? 0;
$nuls = $statsGlobales['nuls'] ?? 0;

// calcul des pourcentages de résultats
$pourcentageVictoires = $totalMatchs > 0 ? round(($victoires / $totalMatchs) * 100, 1) : 0;
$pourcentageDefaites = $totalMatchs > 0 ? round(($defaites / $totalMatchs) * 100, 1) : 0;
$pourcentageNuls = $totalMatchs > 0 ? round(($nuls / $totalMatchs) * 100, 1) : 0;

// ==================== STATISTIQUES PAR JOUEUR ====================
// récupération des statistiques détaillées pour chaque joueur
$statsJoueurs = $fonctionsBDD->obtenirStatistiquesJoueurs();

// calcul du poste préféré et des sélections consécutives pour chaque joueur
foreach ($statsJoueurs as &$joueur) {

    // récupération du poste où le joueur est le plus performant
    $joueur['poste_prefere'] = $fonctionsBDD->obtenirPostePreferJoueur($joueur['id_joueur']);

    // calcul du pourcentage de victoires du joueur
    $joueur['pourcentage_victoires'] = $joueur['total_selections'] > 0
        ? round(($joueur['matchs_gagnes'] / $joueur['total_selections']) * 100, 1)
        : 0;

    // Sélections consécutives (calcul complexe)
    // récupération de la liste des matchs joués par le joueur
    $selections = $fonctionsBDD->obtenirSelectionsConsecutives($joueur['id_joueur']);

    // initialisation du compteur
    $consecutives = 0;

    // si le joueur a des sélections enregistrées
    if (!empty($selections)) {
        $consecutives = 1;

        // comparaison des dates des matchs successifs
        for ($i = 0; $i < count($selections) - 1; $i++) {
            $date1 = new DateTime($selections[$i]['date_heure_match']);
            $date2 = new DateTime($selections[$i + 1]['date_heure_match']);
            $diff = $date1->diff($date2)->days;

            // Si moins de 30 jours entre deux matchs, on considère que c'est consécutif
            if ($diff <= 30) {
                $consecutives++;
            } else {
                // arrêt du calcul dès qu’il y a une coupure trop longue
                break;
            }
        }
    }

    // stockage du nombre de sélections consécutives
    $joueur['selections_consecutives'] = $consecutives;
}

// inclusion de l’en-tête html
include CHEMIN_INCLUDES . '/entete.php';
?>

<div class="conteneur-statistiques">
    <!-- Statistiques globales de l'équipe -->
    <div class="section-stats-globales">
        <h2><i class="fas fa-chart-pie"></i> Statistiques Globales de l'Équipe</h2>

        <div class="grille-stats-globales">
            <div class="stat-card-global victoires">
                <div class="stat-icone">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-contenu">
                    <div class="stat-label">Victoires</div>
                    <div class="stat-valeur"><?php echo $victoires; ?></div>
                    <div class="stat-pourcentage"><?php echo $pourcentageVictoires; ?>%</div>
                    <div class="barre-progression">
                        <div class="progression" style="width: <?php echo $pourcentageVictoires; ?>%; background: #27ae60;"></div>
                    </div>
                </div>
            </div>

            <div class="stat-card-global defaites">
                <div class="stat-icone">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-contenu">
                    <div class="stat-label">Défaites</div>
                    <div class="stat-valeur"><?php echo $defaites; ?></div>
                    <div class="stat-pourcentage"><?php echo $pourcentageDefaites; ?>%</div>
                    <div class="barre-progression">
                        <div class="progression" style="width: <?php echo $pourcentageDefaites; ?>%; background: #e74c3c;"></div>
                    </div>
                </div>
            </div>

            <div class="stat-card-global nuls">
                <div class="stat-icone">
                    <i class="fas fa-equals"></i>
                </div>
                <div class="stat-contenu">
                    <div class="stat-label">Matchs Nuls</div>
                    <div class="stat-valeur"><?php echo $nuls; ?></div>
                    <div class="stat-pourcentage"><?php echo $pourcentageNuls; ?>%</div>
                    <div class="barre-progression">
                        <div class="progression" style="width: <?php echo $pourcentageNuls; ?>%; background: #f39c12;"></div>
                    </div>
                </div>
            </div>

            <div class="stat-card-global total">
                <div class="stat-icone">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-contenu">
                    <div class="stat-label">Total Matchs</div>
                    <div class="stat-valeur"><?php echo $totalMatchs; ?></div>
                    <div class="stat-detail">
                        <?php echo $statsGlobales['buts_marques'] ?? 0; ?> buts marqués<br>
                        <?php echo $statsGlobales['buts_encaisses'] ?? 0; ?> buts encaissés
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des statistiques par joueur -->
    <div class="section-stats-joueurs">
        <h2><i class="fas fa-users"></i> Statistiques Détaillées par Joueur</h2>

        <?php if (!empty($statsJoueurs)): ?>
        <div class="tableau-donnees">
            <table>
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Statut</th>
                        <th>Poste Préféré</th>
                        <th>Titularisations</th>
                        <th>Remplacements</th>
                        <th>Moyenne Notes</th>
                        <th>Sélections Consécutives</th>
                        <th>% Victoires</th>
                        <th>Buts</th>
                        <th>Minutes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statsJoueurs as $joueur): ?>
                    <tr>
                        <td>
                            <div class="joueur-cell">
                                <div class="joueur-avatar-mini">
                                    <?php echo strtoupper(substr($joueur['prenom_joueur'], 0, 1) . substr($joueur['nom_joueur'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($joueur['prenom_joueur'] . ' ' . $joueur['nom_joueur']); ?></strong>
                                    <br><small>#<?php echo htmlspecialchars($joueur['numero_licence']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge-statut statut-<?php echo strtolower($joueur['statut_joueur']); ?>">
                                <?php echo $joueur['statut_joueur']; ?>
                            </span>
                        </td>
                        <td><strong><?php echo $joueur['poste_prefere']; ?></strong></td>
                        <td class="text-center">
                            <span class="badge-nombre titulaire"><?php echo $joueur['nb_titularisations']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge-nombre remplacant"><?php echo $joueur['nb_remplacements']; ?></span>
                        </td>
                        <td class="text-center">
                            <div class="note-cell">
                                <span class="note-valeur"><?php echo number_format($joueur['moyenne_evaluations'], 2); ?>/5</span>
                                <div class="etoiles-mini">
                                    <?php
                                    $note = round($joueur['moyenne_evaluations']);
                                    for ($i = 1; $i <= 5; $i++):
                                    ?>
                                        <span class="<?php echo $i <= $note ? 'active' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge-nombre consecutif"><?php echo $joueur['selections_consecutives']; ?></span>
                        </td>
                        <td class="text-center">
                            <div class="pourcentage-cell">
                                <strong><?php echo $joueur['pourcentage_victoires']; ?>%</strong>
                                <div class="mini-barre">
                                    <div class="mini-progression" style="width: <?php echo $joueur['pourcentage_victoires']; ?>%;"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge-nombre buts"><?php echo $joueur['total_buts'] ?? 0; ?></span>
                        </td>
                        <td class="text-center">
                            <?php echo $joueur['total_minutes'] ?? 0; ?> min
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="aucune-donnee">
            <i class="fas fa-chart-bar"></i>
            <h3>Aucune statistique disponible</h3>
            <p>Les statistiques apparaîtront après avoir joué des matchs.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
