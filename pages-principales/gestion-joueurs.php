<?php
// pages-principales/gestion-joueurs.php
require_once '../configuration/config.php';
require_once '../base-de-donnees/fonctions-bdd.php';
require_once '../securite/authentification.php';

// vérifie que l'utilisateur est connecté, sinon redirection
Authentification::verifierConnexion();

//titre et description 
$titrePage = 'Gestion des Joueurs';
$descriptionPage = 'Gérer les joueurs de l\'équipe';

// création de l'objet d'accès à la base de données
$fonctionsBDD = new FonctionsBaseDeDonnees();

// récupération de tous les joueurs enregistrés
$joueurs = $fonctionsBDD->obtenirTousJoueurs();

// messages destinés à l'utilisateur
$messageSucces = '';
$messageErreur = '';

// Gérer la suppression
if (isset($_GET['supprimer'])) {
    // récupération et sécurisation de l'id du joueur à supprimer
    $id = intval($_GET['supprimer']);

    try {
        // Vérifier si le joueur existe
        $joueur = $fonctionsBDD->obtenirJoueurParId($id);

        if ($joueur) {  //s'il existe 
            // alors suppression du joueur dans la table joueur
            $resultat = $fonctionsBDD->supprimerDonnees('Joueur', 'id_joueur = ?', [$id]);

            if ($resultat) {
                // message de confirmation en cas de succes 
                $messageSucces = 'Le joueur ' . $joueur['nom_joueur'] . ' ' . $joueur['prenom_joueur'] . ' a été supprimé avec succès.';
                // Recharger la liste de joueurs
                $joueurs = $fonctionsBDD->obtenirTousJoueurs();
            } else {
                // message en cas d'échec de la suppression
                $messageErreur = 'Erreur lors de la suppression du joueur.';
            }
        } else {
            // message si l'id ne correspond à aucun joueur
            $messageErreur = 'Joueur non trouvé.';
        }
    } catch (Exception $e) {
        // message en cas d'erreur technique
        $messageErreur = 'Erreur technique: ' . $e->getMessage();
    }
}

// inclusion de l'entête du site
include CHEMIN_INCLUDES . '/entete.php';
?>

<div class="conteneur-gestion">
    <div class="entete-gestion">
        <h1><i class="fas fa-users"></i> Gestion des Joueurs</h1>
        <div class="actions-gestion">
            <a href="ajouter-joueur.php" class="bouton-ajouter">
                <i class="fas fa-user-plus"></i> Ajouter un joueur
            </a>
            <div class="statistiques">
                <span class="stat">
                    <i class="fas fa-user-check"></i>
                    <?php echo count(array_filter($joueurs, function($j) { return $j['statut_joueur'] == 'Actif'; })); ?> Actifs
                </span>
                <span class="stat">
                    <i class="fas fa-user-injured"></i>
                    <?php echo count(array_filter($joueurs, function($j) { return $j['statut_joueur'] == 'Blessé'; })); ?> Blessés
                </span>
                <span class="stat">
                    <i class="fas fa-user"></i>
                    <?php echo count($joueurs); ?> Total
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($joueurs)): ?>
      <div class="tableau-donnees">
      <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Licence</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Âge</th>
                    <th>Taille</th>
                    <th>Poids</th>
                    <th>Statut</th>
                    <th>Date d'ajout</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($joueurs as $joueur):
                    // Calculer l'âge
                    $dateNaissance = new DateTime($joueur['date_naissance']);
                    $aujourdhui = new DateTime();
                    $age = $dateNaissance->diff($aujourdhui)->y;

                    // Classe CSS pour le statut
                    $classeStatut = 'statut-' . strtolower($joueur['statut_joueur']);
                ?>
                <tr>
                    <td class="col-id"><?php echo $joueur['id_joueur']; ?></td>
                    <td class="col-licence"><?php echo htmlspecialchars($joueur['numero_licence']); ?></td>
                    <td class="col-nom"><?php echo htmlspecialchars($joueur['nom_joueur']); ?></td>
                    <td class="col-prenom"><?php echo htmlspecialchars($joueur['prenom_joueur']); ?></td>
                    <td class="col-age"><?php echo $age; ?> ans</td>
                    <td class="col-taille"><?php echo $joueur['taille_cm']; ?> cm</td>
                    <td class="col-poids"><?php echo $joueur['poids_kg']; ?> kg</td>
                    <td class="col-statut">
                        <span class="badge-statut <?php echo $classeStatut; ?>">
                            <?php echo $joueur['statut_joueur']; ?>
                        </span>
                    </td>
                    <td class="col-date"><?php echo date('d/m/Y', strtotime($joueur['date_ajout'])); ?></td>
                    <td class="col-actions">
                        <div class="actions-tableau">
                          <a href="modifier-joueur.php?id=<?php echo $joueur['id_joueur']; ?>"
                            class="bouton-action bouton-modifier" title="Modifier">
                            <i class="fas fa-edit"></i>
                          </a>
                          <a href="details-joueur.php?id=<?php echo $joueur['id_joueur']; ?>"
                            class="bouton-action bouton-voir" title="Détails">
                            <i class="fas fa-eye"></i>
                          </a>
                          <a href="?supprimer=<?php echo $joueur['id_joueur']; ?>"
                            class="bouton-action bouton-supprimer"
                            title="Supprimer"
                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce joueur ?');">
                            <i class="fas fa-trash"></i>
                          </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="aucune-donnee">
        <div class="illustration-vide">
            <i class="fas fa-users-slash"></i>
        </div>
        <h3>Aucun joueur enregistré</h3>
        <p>Commencez par ajouter des joueurs à votre équipe.</p>
        <a href="ajouter-joueur.php" class="bouton-principal">
            <i class="fas fa-user-plus"></i> Ajouter votre premier joueur
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include CHEMIN_INCLUDES . '/pied-de-page.php'; ?>
