<?php
// pages-principales/details-joueur.php
require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

// vérifie si l'utilisateur est connecté, sinon redirection vers la connexion
Authentification::verifierConnexion();

// on instancie la classe des fonctions bdd
$fonctionsBDD = new FonctionsBaseDeDonnees();

// on vérifie qu'un id est bien présent dans l'url
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // si pas d'id, on retourne à la page de gestion des joueurs
    header('Location: gestion-joueurs.php');
    exit();
}

// on récupère l'id du joueur et on le force en entier
$idJoueur = intval($_GET['id']);

// on récupère les infos du joueur en bdd
$joueur = $fonctionsBDD->obtenirJoueurParId($idJoueur);

// si aucun joueur trouvé, on redirige vers la liste
if (!$joueur) {
    header('Location: gestion-joueurs.php');
    exit();
}

// on calcule l'âge du joueur à partir de sa date de naissance
$dateNaissance = new DateTime($joueur['date_naissance']);    //transformer date_naissance en DateTime
$aujourdhui = new DateTime();
$age = $dateNaissance->diff($aujourdhui)->y;

// Récupérer les statistiques des matchs
$statsMatchs = $fonctionsBDD->obtenirStatsMatchsJoueur($idJoueur);

// Récupérer les performances individuelles
$performances = $fonctionsBDD->obtenirPerformancesJoueur($idJoueur);

// Récupérer les derniers matchs joués
$derniersMatchs = $fonctionsBDD->obtenirDerniersMatchsJoueur($idJoueur);

// Calculer les pourcentages
$totalMatchs = $statsMatchs['total_matchs'] ?? 0;
$pourcentageVictoires = $totalMatchs > 0 ? round(($statsMatchs['victoires'] / $totalMatchs) * 100, 1) : 0;
$pourcentageDefaites = $totalMatchs > 0 ? round(($statsMatchs['defaites'] / $totalMatchs) * 100, 1) : 0;
$pourcentageNuls = $totalMatchs > 0 ? round(($statsMatchs['nuls'] / $totalMatchs) * 100, 1) : 0;

// CALCULER LES STATISTIQUES DE SÉLECTION
$totalSelections = $totalMatchs; // Total de matchs = total de sélections
$titularisations = 0;
$remplacements = 0;

// Calculer les sélections à partir des derniers matchs
if (!empty($derniersMatchs)) {
    foreach ($derniersMatchs as $match) {
        if (isset($match['statut_participation'])) {
            if ($match['statut_participation'] == 'Titulaire') {
                $titularisations++;
            } elseif ($match['statut_participation'] == 'Remplaçant') {
                $remplacements++;
            }
        }
    }
}

// Calculer les pourcentages de sélection
$pourcentageTitulaire = $totalSelections > 0 ? round(($titularisations / $totalSelections) * 100, 1) : 0;
$pourcentageRemplacant = $totalSelections > 0 ? round(($remplacements / $totalSelections) * 100, 1) : 0;

// Calculer le ratio de précision des tirs
$totalTirs = ($performances['total_tirs_cadres'] ?? 0) + ($performances['total_tirs_non_cadres'] ?? 0);
$precisionTirs = $totalTirs > 0 ? round((($performances['total_tirs_cadres'] ?? 0) / $totalTirs) * 100, 1) : 0;

//titre et description page 
$titrePage = 'Détails du Joueur';
$descriptionPage = 'Statistiques complètes de ' . $joueur['prenom_joueur'] . ' ' . $joueur['nom_joueur'];

include CHEMIN_INCLUDES . '/entete.php';
?>

<div class="conteneur-details">
    <!-- Carte Profil Joueur -->
    <div class="carte-profil">
        <div class="entete-profil">
            <div class="avatar-joueur">
                <?php echo strtoupper(substr($joueur['prenom_joueur'], 0, 1) . substr($joueur['nom_joueur'], 0, 1)); ?>
            </div>
            <div class="info-principale">
                <h1 class="nom-joueur"><?php echo htmlspecialchars($joueur['prenom_joueur'] . ' ' . $joueur['nom_joueur']); ?></h1>
                <span class="badge-statut statut-<?php echo strtolower($joueur['statut_joueur']); ?>">
                    <?php echo $joueur['statut_joueur']; ?>
                </span>
                <p class="meta-info">
                    <i class="fas fa-id-card"></i> Licence: <?php echo htmlspecialchars($joueur['numero_licence']); ?>
                </p>
            </div>
            <div class="actions-profil">
                <a href="modifier-joueur.php?id=<?php echo $joueur['id_joueur']; ?>" class="bouton-principal">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <a href="gestion-joueurs.php" class="bouton-secondaire">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="grille-infos">
            <div class="info-item">
                <div class="info-label">Âge</div>
                <div class="info-valeur"><?php echo $age; ?> ans</div>
            </div>
            <div class="info-item">
                <div class="info-label">Taille</div>
                <div class="info-valeur"><?php echo $joueur['taille_cm']; ?> cm</div>
            </div>
            <div class="info-item">
                <div class="info-label">Poids</div>
                <div class="info-valeur"><?php echo $joueur['poids_kg']; ?> kg</div>
            </div>
            <div class="info-item">
                <div class="info-label">Membre depuis</div>
                <div class="info-valeur"><?php echo date('d/m/Y', strtotime($joueur['date_ajout'])); ?></div>
            </div>
        </div>
    </div>

    <!-- Grille des statistiques -->
    <div class="grille-stats">
        <!-- Carte Résultats des Matchs -->
        <div class="carte-stat">
            <h2 class="titre-carte">
                <i class="fas fa-trophy"></i> Résultats des matchs
            </h2>
            <div class="stat-item">
                <span class="stat-label">Total de matchs joués</span>
                <span class="stat-valeur"><?php echo $totalMatchs; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Victoires</span>
                <span class="stat-valeur victoire">
                    <?php echo $statsMatchs['victoires'] ?? 0; ?> (<?php echo $pourcentageVictoires; ?>%)
                </span>
            </div>
            <div class="barre-progression">
                <div class="progression victoire" style="width: <?php echo $pourcentageVictoires; ?>%;"></div>
            </div>
            <div class="stat-item">
                <span class="stat-label">Défaites</span>
                <span class="stat-valeur defaite">
                    <?php echo $statsMatchs['defaites'] ?? 0; ?> (<?php echo $pourcentageDefaites; ?>%)
                </span>
            </div>
            <div class="barre-progression">
                <div class="progression defaite" style="width: <?php echo $pourcentageDefaites; ?>%;"></div>
            </div>
            <div class="stat-item">
                <span class="stat-label">Matchs nuls</span>
                <span class="stat-valeur nul">
                    <?php echo $statsMatchs['nuls'] ?? 0; ?> (<?php echo $pourcentageNuls; ?>%)
                </span>
            </div>
        </div>

        <!-- Carte Sélections -->
        <div class="carte-stat">
            <h2 class="titre-carte">
                <i class="fas fa-user-check"></i> Sélections
            </h2>
            <div class="stat-item">
                <span class="stat-label">Total de sélections</span>
                <span class="stat-valeur"><?php echo $totalSelections; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Sélections comme titulaire</span>
                <span class="stat-valeur">
                    <?php echo $titularisations; ?> (<?php echo $pourcentageTitulaire; ?>%)
                </span>
            </div>
            <div class="barre-progression">
                <div class="progression victoire" style="width: <?php echo $pourcentageTitulaire; ?>%;"></div>
            </div>
            <div class="stat-item">
                <span class="stat-label">Sélections comme remplaçant</span>
                <span class="stat-valeur">
                    <?php echo $remplacements; ?> (<?php echo $pourcentageRemplacant; ?>%)
                </span>
            </div>
            <div class="barre-progression">
                <div class="progression defaite" style="width: <?php echo $pourcentageRemplacant; ?>%;"></div>
            </div>
        </div>

        <!-- Carte Statistiques de tir -->
        <div class="carte-stat">
            <h2 class="titre-carte">
                <i class="fas fa-bullseye"></i> Précision des tirs
            </h2>
            <div class="stat-item">
                <span class="stat-label">Tirs cadrés</span>
                <span class="stat-valeur"><?php echo $performances['total_tirs_cadres'] ?? 0; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Tirs non cadrés</span>
                <span class="stat-valeur"><?php echo $performances['total_tirs_non_cadres'] ?? 0; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total de tirs</span>
                <span class="stat-valeur"><?php echo $totalTirs; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Précision</span>
                <span class="stat-valeur"><?php echo $precisionTirs; ?>%</span>
            </div>
            <div class="barre-progression">
                <div class="progression" style="width: <?php echo $precisionTirs; ?>%;"></div>
            </div>
        </div>

        <!-- Carte Localisation -->
        <div class="carte-stat">
            <h2 class="titre-carte">
                <i class="fas fa-map-marker-alt"></i> Localisation des matchs
            </h2>
            <div class="stat-item">
                <span class="stat-label">Matchs à domicile</span>
                <span class="stat-valeur"><?php echo $statsMatchs['domicile'] ?? 0; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Matchs à l'extérieur</span>
                <span class="stat-valeur"><?php echo $statsMatchs['exterieur'] ?? 0; ?></span>
            </div>
        </div>
    </div>

    <!-- Liste des derniers matchs -->
    <div class="liste-matchs">
        <div class="entete-liste">
            <i class="fas fa-history"></i> Historique des matchs (10 derniers)
        </div>

        <?php if (!empty($derniersMatchs)): ?>
        <table class="tableau-matchs">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Adversaire</th>
                    <th>Lieu</th>
                    <th>Résultat</th>
                    <th>Score</th>
                    <th>Buts</th>
                    <th>Passes</th>
                    <th>Tirs cadrés</th>
                    <th>Temps joué</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($derniersMatchs as $match): ?>
                <tr>
                    <td>
                        <?php
                        //si la date existe on la formate
                        if (isset($match['date_heure_match']) && !empty($match['date_heure_match'])) {
                            $dateMatch = new DateTime($match['date_heure_match']);
                            echo $dateMatch->format('d/m/Y H:i');
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($match['equipe_adverse']); ?></strong></td>
                    <td>
                        <span class="badge-type badge-<?php echo strtolower($match['lieu_match']); ?>">
                            <?php echo $match['lieu_match']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge-resultat badge-<?php echo strtolower($match['resultat_match']); ?>">
                            <?php echo $match['resultat_match']; ?>
                        </span>
                    </td>
                    <td><strong><?php echo $match['score_equipe'] . ' - ' . $match['score_adverse']; ?></strong></td>
                    <td><span class="stat-match"><?php echo $match['buts_marques'] ?? 0; ?></span></td>
                    <td><span class="stat-match"><?php echo $match['passes_decisives'] ?? 0; ?></span></td>
                    <td><span class="stat-match"><?php echo $match['tirs_cadres'] ?? 0; ?></span></td>
                    <td><?php echo $match['temps_joue_minutes'] ?? $match['temps_jeu'] ?? 0; ?> min</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="aucune-donnee">
            <i class="fas fa-futbol"></i>
            <h3>Aucun match joué</h3>
            <p>Ce joueur n'a pas encore participé à des matchs.</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($joueur['commentaires_joueur'])): ?>
    <div class="carte-profil" style="margin-top: 30px;">
        <h2 class="titre-carte">
            <i class="fas fa-comment-alt"></i> Commentaires et observations
        </h2>
        <p style="color: #6c757d; line-height: 1.8; margin-top: 15px;">
            <?php echo nl2br(htmlspecialchars($joueur['commentaires_joueur'])); ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
