<?php
// pages-principales/evaluations-joueurs.php

// activer l'affichage de toutes les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

// vérifie que l'utilisateur est connecté, sinon redirection
Authentification::verifierConnexion();

//titre et description 
$titrePage = 'Évaluations des Joueurs';
$descriptionPage = 'Évaluer les performances des joueurs après chaque match';

// création de l'objet d'accès à la base de données

$fonctionsBDD = new FonctionsBaseDeDonnees();

// messages affichés à l'utilisateur
$messageSucces = '';
$messageErreur = '';

// récupération de l'id du match sélectionné via l'url (get)
$idMatchSelectionne = isset($_GET['match']) ? intval($_GET['match']) : null;
// initialisation des variables
$matchSelectionne = null;
$joueurs = [];

// Récupérer tous les matchs passés
$matchsPasses = $fonctionsBDD->obtenirMatchsPasses();

// Si un match est sélectionné
if ($idMatchSelectionne) {
    // récupération des informations du match sélectionné
    $matchSelectionne = $fonctionsBDD->obtenirMatchParId($idMatchSelectionne);

    if ($matchSelectionne) {
        // Récupérer les joueurs qui ont participé
        $joueurs = $fonctionsBDD->obtenirJoueursMatch($idMatchSelectionne);
    }
}

// traitement du formulaire lors de la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idMatchSelectionne) {
    //log pour le debogage
    error_log("=== DÉBUT ÉVALUATION JOUEURS ===");

    // récupération des évaluations envoyées par le formulaire
    $evaluations = $_POST['evaluations'] ?? [];

    // vérification qu'il y a des données à enregistrer
    if (empty($evaluations)) {
        $messageErreur = 'Aucune évaluation à enregistrer.';
    } else {
        try {
            // compteur du nombre de mises à jour effectuées
            $nbMaj = 0;
              // boucle sur chaque joueur évalué          
            foreach ($evaluations as $idJoueur => $donnees) {
                // préparation des paramètres sécurisés                
                $params = [
                    ':temps_jeu' => intval($donnees['temps_jeu'] ?? 0),
                    ':buts' => intval($donnees['buts'] ?? 0),
                    ':fautes' => intval($donnees['fautes'] ?? 0),
                    ':note' => floatval($donnees['note'] ?? 0),
                    ':commentaire' => trim($donnees['commentaire'] ?? ''),
                    ':id_joueur' => intval($idJoueur),
                    ':id_match' => $idMatchSelectionne
                ];

                // mise à jour de la participation du joueur pour ce match
                $resultat = $fonctionsBDD->mettreAJourParticipation(intval($idJoueur), $idMatchSelectionne, $params);
                // si la mise à jour a réussi, on incrémente le compteur
                if ($resultat) $nbMaj++;
            }
            // si au moins une évaluation a été enregistrée
            if ($nbMaj > 0) {
                $messageSucces = "$nbMaj joueur(s) évalué(s) avec succès !";

                // rechargement des joueurs avec les nouvelles données
                $joueurs = $fonctionsBDD->obtenirJoueursMatch($idMatchSelectionne);
            }

        } catch (Exception $e) {
            // log de l'erreur technique
            error_log("Exception: " . $e->getMessage());
            $messageErreur = 'Erreur technique: ' . $e->getMessage();
        }
    }

    //fin du traitement 
    error_log("=== FIN ÉVALUATION JOUEURS ===");
}

include CHEMIN_INCLUDES . '/entete.php';
?>

<div class="conteneur-evaluations">
    <!-- Sélection du match -->
    <div class="carte-selection-match">
        <h3><i class="fas fa-futbol"></i> Sélectionner un match</h3>
        <form method="GET" action="" class="form-selection">
            <select name="match" id="select-match" class="select-match" onchange="this.form.submit()">
                <option value="">-- Choisir un match --</option>
                <?php foreach ($matchsPasses as $match):
                    $dateHeure = new DateTime($match['date_heure_match']);
                    $dateFormatee = $dateHeure->format('d/m/Y à H:i');
                ?>
                <option value="<?php echo $match['id_match']; ?>"
                        <?php echo ($idMatchSelectionne == $match['id_match']) ? 'selected' : ''; ?>>
                    <?php echo $dateFormatee; ?> - <?php echo htmlspecialchars($match['equipe_adverse']); ?>
                    (<?php echo $match['score_equipe']; ?>-<?php echo $match['score_adverse']; ?>)
                    - <?php echo $match['resultat_match']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

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

    <?php if ($matchSelectionne && !empty($joueurs)):
        $dateHeure = new DateTime($matchSelectionne['date_heure_match']);
        $dateMatch = $dateHeure->format('d/m/Y à H:i');
    ?>
    <!-- Informations du match sélectionné -->
    <div class="carte-info-match-eval">
        <div class="info-match-content">
            <h2><?php echo htmlspecialchars($matchSelectionne['equipe_adverse']); ?></h2>
            <div class="meta-match-eval">
                <span><i class="fas fa-calendar"></i> <?php echo $dateMatch; ?></span>
                <span class="badge-lieu-eval badge-<?php echo strtolower($matchSelectionne['lieu_match']); ?>">
                    <?php echo $matchSelectionne['lieu_match']; ?>
                </span>
                <span class="badge-resultat-eval badge-<?php echo strtolower($matchSelectionne['resultat_match']); ?>">
                    <?php echo $matchSelectionne['resultat_match']; ?>
                </span>
                <span class="score-eval">
                    Score: <?php echo $matchSelectionne['score_equipe']; ?> - <?php echo $matchSelectionne['score_adverse']; ?>
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="?match=<?php echo $idMatchSelectionne; ?>" id="form-evaluation">
        <div class="grille-evaluations">
            <?php
            $titulaires = array_filter($joueurs, function($j) { return $j['statut_participation'] == 'Titulaire'; });
            $remplacants = array_filter($joueurs, function($j) { return $j['statut_participation'] == 'Remplaçant'; });

            foreach (['Titulaires' => $titulaires, 'Remplaçants' => $remplacants] as $type => $liste):
                if (empty($liste)) continue;
            ?>
            <div class="section-evaluation">
                <h3 class="titre-section-eval">
                    <i class="fas <?php echo $type == 'Titulaires' ? 'fa-user-check' : 'fa-exchange-alt'; ?>"></i>
                    <?php echo $type; ?> (<?php echo count($liste); ?>)
                </h3>

                <?php foreach ($liste as $joueur): ?>
                <div class="carte-eval-joueur <?php echo $type == 'Remplaçants' ? 'remplacant' : ''; ?>">
                    <div class="entete-eval-joueur">
                        <div class="joueur-avatar-eval">
                            <?php echo strtoupper(substr($joueur['prenom_joueur'], 0, 1) . substr($joueur['nom_joueur'], 0, 1)); ?>
                        </div>
                        <div class="joueur-info-eval">
                            <h4><?php echo htmlspecialchars($joueur['prenom_joueur'] . ' ' . $joueur['nom_joueur']); ?></h4>
                            <span class="licence-eval">#<?php echo htmlspecialchars($joueur['numero_licence']); ?></span>
                        </div>
                    </div>

                    <div class="grille-stats-eval">
                        <div class="stat-eval">
                            <label><i class="fas fa-clock"></i> Temps (min)</label>
                            <input type="number"
                                   name="evaluations[<?php echo $joueur['id_joueur']; ?>][temps_jeu]"
                                   value="<?php echo $joueur['temps_joue_minutes'] ?? 0; ?>"
                                   min="0" max="120" class="input-stat">
                        </div>

                        <div class="stat-eval">
                            <label><i class="fas fa-futbol"></i> Buts</label>
                            <input type="number"
                                   name="evaluations[<?php echo $joueur['id_joueur']; ?>][buts]"
                                   value="<?php echo $joueur['points_marques'] ?? 0; ?>"
                                   min="0" max="20" class="input-stat">
                        </div>

                        <div class="stat-eval">
                            <label><i class="fas fa-exclamation-triangle"></i> Fautes</label>
                            <input type="number"
                                   name="evaluations[<?php echo $joueur['id_joueur']; ?>][fautes]"
                                   value="<?php echo $joueur['fautes_commises'] ?? 0; ?>"
                                   min="0" max="10" class="input-stat">
                        </div>
                    </div>

                    <div class="note-eval">
                        <label><i class="fas fa-star"></i> Note du joueur</label>
                        <div class="etoiles-rating" data-joueur="<?php echo $joueur['id_joueur']; ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="etoile <?php echo ($i <= ($joueur['note_joueur'] ?? 0)) ? 'active' : ''; ?>"
                                  data-note="<?php echo $i; ?>">★</span>
                            <?php endfor; ?>
                            <input type="hidden"
                                   name="evaluations[<?php echo $joueur['id_joueur']; ?>][note]"
                                   value="<?php echo $joueur['note_joueur'] ?? 0; ?>"
                                   class="input-note">
                            <span class="note-affichee"><?php echo number_format($joueur['note_joueur'] ?? 0, 1); ?>/5</span>
                        </div>
                    </div>

                    <div class="commentaire-eval">
                        <label><i class="fas fa-comment"></i> Commentaire</label>
                        <textarea name="evaluations[<?php echo $joueur['id_joueur']; ?>][commentaire]"
                                  rows="2"
                                  placeholder="Observations sur la performance..."
                                  maxlength="500"><?php echo htmlspecialchars($joueur['commentaires_participation'] ?? ''); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="actions-evaluation">
            <button type="submit" class="bouton-principal btn-large">
                <i class="fas fa-save"></i> Enregistrer les évaluations
            </button>
        </div>
    </form>

    <?php elseif ($matchSelectionne && empty($joueurs)): ?>
    <div class="aucune-donnee">
        <i class="fas fa-users-slash"></i>
        <h3>Aucun joueur à évaluer</h3>
        <p>Aucun joueur n'a été sélectionné pour ce match.</p>
    </div>

    <?php elseif (!$idMatchSelectionne): ?>
    <div class="aucune-donnee">
        <i class="fas fa-hand-pointer"></i>
        <h3>Sélectionnez un match</h3>
        <p>Choisissez un match dans la liste ci-dessus pour évaluer les joueurs.</p>
    </div>
    <?php endif; ?>
</div>

<script>
// Gestion du rating par étoiles
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.etoiles-rating').forEach(function(container) {
        const etoiles = container.querySelectorAll('.etoile');
        const input = container.querySelector('.input-note');
        const affichage = container.querySelector('.note-affichee');

        etoiles.forEach(function(etoile) {
            etoile.addEventListener('click', function() {
                const note = parseInt(this.dataset.note);
                input.value = note;
                affichage.textContent = note + '/5';

                // Mettre à jour l'affichage des étoiles
                etoiles.forEach(function(e, index) {
                    if (index < note) {
                        e.classList.add('active');
                    } else {
                        e.classList.remove('active');
                    }
                });
            });

            // Effet hover
            etoile.addEventListener('mouseenter', function() {
                const note = parseInt(this.dataset.note);
                etoiles.forEach(function(e, index) {
                    if (index < note) {
                        e.classList.add('hover');
                    } else {
                        e.classList.remove('hover');
                    }
                });
            });
        });

        container.addEventListener('mouseleave', function() {
            etoiles.forEach(e => e.classList.remove('hover'));
        });
    });
});
</script>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
