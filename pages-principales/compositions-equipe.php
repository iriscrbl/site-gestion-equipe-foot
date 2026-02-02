<?php
// pages-principales/compositions-equipe.php
error_reporting(E_ALL);              // active l'affichage de toutes les erreurs php : debug
ini_set('display_errors', 1);        // affiche les erreurs directement dans le navigateur

//inclusion fichiers nécessaires
require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

// si l'utilisateur n'est pas connecté, redirection vers login
Authentification::verifierConnexion();

// création d'une instance pour utiliser les méthodes bdd
$fonctionsBDD = new FonctionsBaseDeDonnees();

// Vérifier qu'un ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {  // si aucun id match dans l'url
    header('Location: gestion-matchs.php');   // redirection vers la page de gestion des matchs
    exit();       // arrêt du script
}

// sécurise l'id en le convertissant en entier
$idMatch = intval($_GET['id']);

// Récupérer les données du match
$match = $fonctionsBDD->obtenirMatchParId($idMatch);

// si le match n'existe pas (id invalide)
if (!$match) {
    header('Location: gestion-matchs.php');   //redirection
    exit();     //arret du script 
}

// création d'un objet date à partir de la date stockée
$dateHeure = new DateTime($match['date_heure_match']);
$dateMatch = $dateHeure->format('d/m/Y à H:i');     // formatage de la date pour affichage

//titre et description
$titrePage = 'Composer l\'équipe';
$descriptionPage = 'Match contre ' . $match['equipe_adverse'] . ' - ' . $dateMatch;

$messageSucces = '';      // variable pour afficher un message de succès
$messageErreur = '';

// Récupérer tous les joueurs actifs avec leurs statistiques et historique
$joueursActifs = $fonctionsBDD->obtenirJoueursActifsAvecStats();

// Récupérer les joueurs déjà sélectionnés pour ce match
$joueursSelectionnes = $fonctionsBDD->obtenirJoueursMatch($idMatch);

// Créer un tableau des IDs sélectionnés pour faciliter les vérifications
$idsSelectionnes = array_column($joueursSelectionnes, 'id_joueur');

// Créer un tableau associatif avec le statut de chaque joueur sélectionné
$statutsSelectionnes = [];
foreach ($joueursSelectionnes as $joueur) {
    $statutsSelectionnes[$joueur['id_joueur']] = $joueur['statut_participation'];
}

// Variables pour conserver la sélection en cas d'erreur
$selectionEnCours = [];      // tableau pour garder les choix utilisateur après soumission
$statutsEnCours = [];        // tableau pour garder les statuts après soumission

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {     // si on a cliqué sur "valider"
    error_log("=== DÉBUT COMPOSITION ÉQUIPE ===");    //log pour debug

    // récupère le tableau envoyé du formulaire
    $joueurs = $_POST['joueurs'] ?? [];

    error_log("Joueurs reçus: " . print_r($joueurs, true));

    // Filtrer pour ne garder que les joueurs cochés
    $joueursSelectionnesPost = [];
    //boucle sur chaque joueur envoyé 
    foreach ($joueurs as $idJoueur => $donnees) { 
        if (isset($donnees['selected']) && $donnees['selected'] == '1') {  //si coché
            $joueursSelectionnesPost[$idJoueur] = $donnees;   //on le garde
        }
    }

    // Validation de base
    // si aucun joueur n'est sélectionné
    if (empty($joueursSelectionnesPost)) {
        $messageErreur = 'Vous devez sélectionner au moins un joueur.';
        // Garder la sélection vide
        $selectionEnCours = [];
        $statutsEnCours = [];
    } else {
        // IMPORTANT: Conserver la sélection en cours pour l'afficher en cas d'erreur
        $selectionEnCours = array_keys($joueursSelectionnesPost);
        foreach ($joueursSelectionnesPost as $idJoueur => $donnees) {
            $statutsEnCours[$idJoueur] = $donnees['statut'] ?? 'Remplaçant';   //statut choisi
        }

        // Compter titulaires et remplaçants
        $titulaires = [];
        $remplacants = [];

        foreach ($joueursSelectionnesPost as $idJoueur => $donnees) {
            $statut = $donnees['statut'] ?? 'Remplaçant';   // si rien choisi, remplaçant
            if ($statut == 'Titulaire') {
                $titulaires[] = $idJoueur;     // ajoute au tableau titulaires
            } else {
                $remplacants[] = $idJoueur;    // ajoute au tableau remplaçants
            }   
        }

        // Validation du nombre de joueurs selon les règles du football
        $nbTitulaires = count($titulaires);
        $nbRemplacants = count($remplacants);
        $nbTotal = count($joueursSelectionnesPost);     //total sélectionné

        error_log("Validation: $nbTitulaires titulaires, $nbRemplacants remplaçants, $nbTotal total");

        // Vérification du nombre EXACT de titulaires (11 au football)
        if ($nbTitulaires != JOUEURS_TITULAIRES_MAX) {
            $messageErreur = "Vous devez sélectionner exactement " . JOUEURS_TITULAIRES_MAX . " titulaires (actuellement : $nbTitulaires sélectionné(s)).";
            // La sélection est déjà conservée dans $selectionEnCours et $statutsEnCours
        }
        // Vérification du nombre maximum de remplaçants
        elseif ($nbRemplacants > JOUEURS_REMPLACANTS_MAX) {
            $messageErreur = "Vous ne pouvez pas sélectionner plus de " . JOUEURS_REMPLACANTS_MAX . " remplaçants (actuellement : $nbRemplacants sélectionné(s)).";
            // La sélection est déjà conservée dans $selectionEnCours et $statutsEnCours
        }
        // Vérification du nombre total maximum
        elseif ($nbTotal > JOUEURS_TOTAL_MAX) {
            $messageErreur = "Vous ne pouvez pas sélectionner plus de " . JOUEURS_TOTAL_MAX . " joueurs au total (actuellement : $nbTotal sélectionné(s)).";
            // La sélection est déjà conservée dans $selectionEnCours et $statutsEnCours
        }
        // Si toutes les validations passent
        else {
            try {
                // Supprimer les anciennes participations
                $fonctionsBDD->supprimerParticipationsMatch($idMatch);

                $nbInsertions = 0;    //compte le nombre d'insertion réussie
                foreach ($joueursSelectionnesPost as $idJoueur => $donnees) {
                    $statut = $donnees['statut'] ?? 'Remplaçant';

                    $donneesParticipation = [
                        ':id_joueur' => intval($idJoueur),
                        ':id_match' => $idMatch,
                        ':statut' => $statut,
                        ':temps_jeu' => 0,
                        ':buts' => 0,
                        ':fautes' => 0,
                        ':note' => 0.0
                    ];

                    //insertion en bd
                    $resultat = $fonctionsBDD->insererParticipation($donneesParticipation);
                    if ($resultat) $nbInsertions++;   //si ok, on incrémente
                }

                //si l'insertion a eu lieu 
                if ($nbInsertions > 0) {
                    $messageSucces = "$nbInsertions joueur(s) sélectionné(s) avec succès ! ($nbTitulaires titulaires, $nbRemplacants remplaçants)";

                    // Recharger les joueurs sélectionnés
                    $joueursSelectionnes = $fonctionsBDD->obtenirJoueursMatch($idMatch);
                    $idsSelectionnes = array_column($joueursSelectionnes, 'id_joueur');

                    // Recréer le tableau des statuts
                    $statutsSelectionnes = [];
                    foreach ($joueursSelectionnes as $joueur) {
                        $statutsSelectionnes[$joueur['id_joueur']] = $joueur['statut_participation'];
                    }

                    // Redirection après 2 secondes
                    header("Refresh: 2; url=details-match.php?id=$idMatch");
                } else {
                    $messageErreur = 'Aucun joueur n\'a été ajouté.';
                }

            } catch (Exception $e) {  //en cas d'exception
                error_log("Exception: " . $e->getMessage());
                $messageErreur = 'Erreur technique: ' . $e->getMessage();
            }
        }
    }

    error_log("=== FIN COMPOSITION ÉQUIPE ===");
}

include CHEMIN_INCLUDES . '/entete.php';
?>

<style>
/* Styles pour la composition d'équipe */
.conteneur-composition {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.messages-systeme {
    margin-bottom: 20px;
}

.message-succes, .message-erreur {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
}

.message-succes {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message-erreur {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.carte-info-match, .carte-regles {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.info-match-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.meta-match {
    color: #666;
    margin-top: 5px;
}

.badge-lieu-petit {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    margin-left: 10px;
}

.badge-domicile {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-exterieur {
    background: #fff3e0;
    color: #f57c00;
}

.regles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.regle-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.regle-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.regle-item.obligatoire {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.regle-item i {
    font-size: 32px;
    margin-bottom: 10px;
    display: block;
    opacity: 0.95;
}

.regle-item strong {
    display: block;
    font-size: 18px;
    margin-bottom: 5px;
    font-weight: 700;
}

.regle-item span {
    display: block;
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.liste-joueurs-disponibles {
    display: grid;
    gap: 15px;
    margin-top: 20px;
}

.carte-joueur-dispo {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 15px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 15px;
    align-items: center;
    transition: all 0.3s;
}

.carte-joueur-dispo:hover {
    border-color: #2196f3;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

.carte-joueur-dispo.selectionne {
    border-color: #4caf50;
    background: #f1f8f4;
}

.joueur-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.joueur-info-label {
    display: flex;
    gap: 15px;
    align-items: center;
    cursor: pointer;
    flex: 1;
}

.joueur-avatar-petit {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.joueur-details {
    flex: 1;
}

.joueur-nom-complet {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 5px;
}

.joueur-meta {
    display: flex;
    gap: 15px;
    color: #666;
    font-size: 13px;
    margin-bottom: 10px;
}

.joueur-historique {
    margin-top: 10px;
}

.historique-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #555;
}

.stat-item i {
    color: #2196f3;
}

.stat-item strong {
    color: #000;
}

.stat-item small {
    color: #999;
}

.historique-commentaires {
    margin-top: 8px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 13px;
    color: #666;
}

.joueur-statut-select select {
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    min-width: 150px;
}

.joueur-statut-select select:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

.actions-composition {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.bouton-principal, .bouton-secondaire {
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.bouton-principal {
    background: #4caf50;
    color: white;
}

.bouton-principal:hover {
    background: #45a049;
}

.bouton-secondaire {
    background: #f5f5f5;
    color: #333;
    border: 2px solid #ddd;
}

.bouton-secondaire:hover {
    background: #e0e0e0;
}

.info-aide {
    background: #e3f2fd;
    padding: 12px 15px;
    border-radius: 8px;
    color: #1976d2;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.entete-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.badge-count {
    background: #e0e0e0;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

.aucune-donnee-inline {
    text-align: center;
    padding: 40px;
    color: #999;
}

.aucune-donnee-inline i {
    font-size: 48px;
    margin-bottom: 15px;
    display: block;
}
</style>

<div class="conteneur-composition">
    <?php if ($messageSucces || $messageErreur): ?>
    <div class="messages-systeme">
        <?php if ($messageSucces): ?>
            <div class="message-succes"><i class="fas fa-check-circle"></i> <?php echo $messageSucces; ?></div>
        <?php endif; ?>
        <?php if ($messageErreur): ?>
            <div class="message-erreur"><i class="fas fa-exclamation-circle"></i> <?php echo $messageErreur; ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Informations du match -->
    <div class="carte-info-match">
        <div class="info-match-header">
            <div>
                <h2><i class="fas fa-futbol"></i> <?php echo htmlspecialchars($match['equipe_adverse']); ?></h2>
                <p class="meta-match">
                    <i class="fas fa-calendar"></i> <?php echo $dateMatch; ?>
                    <span class="badge-lieu-petit badge-<?php echo strtolower($match['lieu_match']); ?>">
                        <?php echo $match['lieu_match'] == 'Domicile' ? '<i class="fas fa-home"></i>' : '<i class="fas fa-plane"></i>'; ?>
                        <?php echo $match['lieu_match']; ?>
                    </span>
                </p>
            </div>
            <a href="details-match.php?id=<?php echo $idMatch; ?>" class="bouton-secondaire">
                <i class="fas fa-eye"></i> Voir détails
            </a>
        </div>
    </div>

    <!-- Règles de sélection -->
    <div class="carte-regles">
        <h3><i class="fas fa-info-circle"></i> Règles de composition</h3>
        <div class="regles-grid">
            <div class="regle-item obligatoire">
                <i class="fas fa-user-check"></i>
                <strong><?php echo JOUEURS_TITULAIRES_MAX; ?> titulaires</strong>
                <span>obligatoires</span>
            </div>
            <div class="regle-item">
                <i class="fas fa-exchange-alt"></i>
                <strong>Max <?php echo JOUEURS_REMPLACANTS_MAX; ?> remplaçants</strong>
                <span>optionnels</span>
            </div>
            <div class="regle-item">
                <i class="fas fa-users"></i>
                <strong>Max <?php echo JOUEURS_TOTAL_MAX; ?> joueurs</strong>
                <span>au total</span>
            </div>
        </div>
    </div>

    <form method="POST" action="">
        <div class="entete-section">
            <h3><i class="fas fa-users"></i> Joueurs disponibles</h3>
            <span class="badge-count"><?php echo count($joueursActifs); ?> joueurs actifs</span>
        </div>

        <div class="info-aide">
            <i class="fas fa-lightbulb"></i>
            Cochez les joueurs que vous souhaitez sélectionner, puis choisissez leur statut (Titulaire ou Remplaçant).
        </div>

        <div class="liste-joueurs-disponibles">
            <?php if (!empty($joueursActifs)): ?>
                <?php foreach ($joueursActifs as $joueur):
                    // Utiliser la sélection en cours (en cas d'erreur) ou la sélection en BDD
                    $estSelectionne = !empty($selectionEnCours)
                        ? in_array($joueur['id_joueur'], $selectionEnCours)
                        : in_array($joueur['id_joueur'], $idsSelectionnes);

                    // Utiliser le statut en cours (en cas d'erreur) ou le statut en BDD
                    $statutActuel = !empty($statutsEnCours) && isset($statutsEnCours[$joueur['id_joueur']])
                        ? $statutsEnCours[$joueur['id_joueur']]
                        : ($statutsSelectionnes[$joueur['id_joueur']] ?? 'Remplaçant');
                ?>
                <div class="carte-joueur-dispo <?php echo $estSelectionne ? 'selectionne' : ''; ?>">
                    <div class="joueur-checkbox">
                        <input type="checkbox"
                               name="joueurs[<?php echo $joueur['id_joueur']; ?>][selected]"
                               id="joueur_<?php echo $joueur['id_joueur']; ?>"
                               value="1"
                               <?php echo $estSelectionne ? 'checked' : ''; ?>>
                    </div>

                    <label for="joueur_<?php echo $joueur['id_joueur']; ?>" class="joueur-info-label">
                        <div class="joueur-avatar-petit">
                            <?php echo strtoupper(substr($joueur['prenom_joueur'], 0, 1) . substr($joueur['nom_joueur'], 0, 1)); ?>
                        </div>
                        <div class="joueur-details">
                            <div class="joueur-nom-complet">
                                <?php echo htmlspecialchars($joueur['prenom_joueur'] . ' ' . $joueur['nom_joueur']); ?>
                            </div>
                            <div class="joueur-meta">
                                <span><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($joueur['numero_licence']); ?></span>
                                <span><i class="fas fa-ruler-vertical"></i> <?php echo $joueur['taille_cm']; ?> cm</span>
                                <span><i class="fas fa-weight"></i> <?php echo $joueur['poids_kg']; ?> kg</span>
                            </div>

                            <!-- Historique et statistiques du joueur -->
                            <div class="joueur-historique">
                                <div class="historique-stats">
                                    <span class="stat-item" title="Moyenne des évaluations">
                                        <i class="fas fa-star"></i>
                                        <strong><?php echo number_format($joueur['moyenne_notes'], 1); ?>/5</strong>
                                        <small>moy. notes</small>
                                    </span>
                                    <span class="stat-item" title="Nombre de matchs joués">
                                        <i class="fas fa-calendar-check"></i>
                                        <strong><?php echo $joueur['nb_matchs_joues']; ?></strong>
                                        <small>matchs</small>
                                    </span>
                                    <span class="stat-item" title="Nombre de victoires">
                                        <i class="fas fa-trophy"></i>
                                        <strong><?php echo $joueur['nb_victoires'] ?? 0; ?></strong>
                                        <small>victoires</small>
                                    </span>
                                    <span class="stat-item" title="Total de buts marqués">
                                        <i class="fas fa-futbol"></i>
                                        <strong><?php echo $joueur['total_buts'] ?? 0; ?></strong>
                                        <small>buts</small>
                                    </span>
                                </div>

                                <?php if (!empty($joueur['commentaires_joueur'])): ?>
                                <div class="historique-commentaires" title="<?php echo htmlspecialchars($joueur['commentaires_joueur']); ?>">
                                    <i class="fas fa-comment-dots"></i>
                                    <span class="commentaire-extrait">
                                        <?php
                                        $extrait = strlen($joueur['commentaires_joueur']) > 80
                                            ? substr($joueur['commentaires_joueur'], 0, 80) . '...'
                                            : $joueur['commentaires_joueur'];
                                        echo htmlspecialchars($extrait);
                                        ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>

                    <div class="joueur-statut-select">
                        <select name="joueurs[<?php echo $joueur['id_joueur']; ?>][statut]" class="select-statut">
                            <option value="Titulaire" <?php echo $statutActuel == 'Titulaire' ? 'selected' : ''; ?>>
                                Titulaire
                            </option>
                            <option value="Remplaçant" <?php echo $statutActuel == 'Remplaçant' ? 'selected' : ''; ?>>
                                Remplaçant
                            </option>
                        </select>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="aucune-donnee-inline">
                    <i class="fas fa-user-slash"></i>
                    <p>Aucun joueur actif disponible</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="actions-composition">
            <button type="submit" class="bouton-principal">
                <i class="fas fa-save"></i> Valider la composition
            </button>
            <a href="details-match.php?id=<?php echo $idMatch; ?>" class="bouton-secondaire">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
