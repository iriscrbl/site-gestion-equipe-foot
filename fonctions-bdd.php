<?php
// base-de-donnees/fonctions-bdd.php
require_once 'connexion-bdd.php';

class FonctionsBaseDeDonnees {
    private $connexion;

    public function __construct() {
        try {
            $instanceBDD = ConnexionBaseDeDonnees::obtenirInstance();
            $this->connexion = $instanceBDD->obtenirConnexion();
            error_log("FonctionsBaseDeDonnees initialisée avec succès");
        } catch (Exception $e) {
            error_log("Erreur initialisation FonctionsBaseDeDonnees: " . $e->getMessage());
            throw $e;
        }
    }

    // ==================== MÉTHODES GÉNÉRIQUES ====================

    /**
     * Exécuter une requête SQL sécurisée
     */
    public function executerRequete($sql, $parametres = []) {
        try {
            error_log("SQL préparé: " . $sql);
            error_log("Paramètres: " . print_r($parametres, true));

            $requete = $this->connexion->prepare($sql);

            if (!$requete) {
                $errorInfo = $this->connexion->errorInfo();
                error_log("Erreur préparation: " . print_r($errorInfo, true));
                return false;
            }

            $resultat = $requete->execute($parametres);

            if (!$resultat) {
                $errorInfo = $requete->errorInfo();
                error_log("Erreur exécution: " . print_r($errorInfo, true));
                return false;
            }

            error_log("Requête exécutée avec succès");
            return $requete;
        } catch (PDOException $e) {
            error_log("PDOException dans executerRequete: " . $e->getMessage());
            error_log("Requête: " . $sql);
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Récupérer un seul résultat
     */
    public function obtenirUnResultat($sql, $parametres = []) {
        $requete = $this->executerRequete($sql, $parametres);
        if ($requete) {
            $resultat = $requete->fetch();
            error_log("Résultat obtenu: " . print_r($resultat, true));
            return $resultat;
        }
        return false;
    }

    /**
     * Récupérer tous les résultats
     */
    public function obtenirTousResultats($sql, $parametres = []) {
        try {
            error_log("obtenirTousResultats - SQL: " . $sql);
            error_log("obtenirTousResultats - Paramètres: " . print_r($parametres, true));

            $requete = $this->executerRequete($sql, $parametres);

            if ($requete) {
                $resultats = $requete->fetchAll(PDO::FETCH_ASSOC);
                error_log("obtenirTousResultats - Résultats trouvés: " . count($resultats));
                return $resultats;
            }

            error_log("obtenirTousResultats - Requête échouée");
            return [];
        } catch (Exception $e) {
            error_log("Exception dans obtenirTousResultats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprimer des données
     */
    public function supprimerDonnees($table, $condition, $parametres = []) {
        $sql = "DELETE FROM $table WHERE $condition";
        $resultat = $this->executerRequete($sql, $parametres);
        return $resultat ? true : false;
    }

    // ==================== MÉTHODES JOUEURS ====================

    /**
     * Récupérer un joueur par son ID
     */
    public function obtenirJoueurParId($idJoueur) {
        $sql = "SELECT * FROM Joueur WHERE id_joueur = ?";
        return $this->obtenirUnResultat($sql, [$idJoueur]);
    }

    /**
     * Récupérer tous les joueurs
     */
    public function obtenirTousJoueurs() {
        $sql = "SELECT * FROM Joueur ORDER BY nom_joueur, prenom_joueur";
        return $this->obtenirTousResultats($sql);
    }

    /**
     * Récupérer tous les joueurs actifs avec leurs statistiques
     */
    public function obtenirJoueursActifsAvecStats() {
        $sql = "
            SELECT
                j.*,
                COALESCE(AVG(p.note_joueur), 0) as moyenne_notes,
                COUNT(p.id_participation) as nb_matchs_joues,
                SUM(CASE WHEN m.resultat_match = 'Victoire' THEN 1 ELSE 0 END) as nb_victoires,
                SUM(p.temps_joue_minutes) as total_minutes,
                SUM(p.points_marques) as total_buts
            FROM Joueur j
            LEFT JOIN Participer p ON j.id_joueur = p.id_joueur
            LEFT JOIN Matchs m ON p.id_match = m.id_match AND m.resultat_match != 'À venir'
            WHERE j.statut_joueur = 'Actif'
            GROUP BY j.id_joueur
            ORDER BY j.nom_joueur, j.prenom_joueur
        ";

        return $this->obtenirTousResultats($sql);
    }

    /**
     * Insérer un nouveau joueur
     */
    public function insererJoueur($donnees) {
        $sql = "INSERT INTO Joueur (
                    numero_licence, nom_joueur, prenom_joueur, date_naissance,
                    taille_cm, poids_kg, statut_joueur, commentaires_joueur
                ) VALUES (
                    :numero_licence, :nom_joueur, :prenom_joueur, :date_naissance,
                    :taille_cm, :poids_kg, :statut_joueur, :commentaires_joueur
                )";

        $requete = $this->executerRequete($sql, $donnees);

        if ($requete) {
            return $this->connexion->lastInsertId();
        }

        return false;
    }

    /**
     * Mettre à jour un joueur
     */
    public function mettreAJourJoueur($idJoueur, $parametres) {
        $sql = "UPDATE Joueur SET
                    numero_licence = :numero_licence,
                    nom_joueur = :nom_joueur,
                    prenom_joueur = :prenom_joueur,
                    date_naissance = :date_naissance,
                    taille_cm = :taille_cm,
                    poids_kg = :poids_kg,
                    statut_joueur = :statut_joueur,
                    commentaires_joueur = :commentaires_joueur
                WHERE id_joueur = :id_joueur";

        return $this->executerRequete($sql, $parametres);
    }

    /**
     * Vérifier si un numéro de licence existe déjà
     */
    public function verifierLicenceExiste($numeroLicence, $idJoueurExclu = null) {
        if ($idJoueurExclu) {
            $sql = "SELECT id_joueur FROM Joueur WHERE numero_licence = ? AND id_joueur != ?";
            $resultat = $this->obtenirUnResultat($sql, [$numeroLicence, $idJoueurExclu]);
        } else {
            $sql = "SELECT id_joueur FROM Joueur WHERE numero_licence = ?";
            $resultat = $this->obtenirUnResultat($sql, [$numeroLicence]);
        }
        return $resultat ? true : false;
    }

    /**
     * Récupérer les statistiques des matchs d'un joueur
     */
    public function obtenirStatsMatchsJoueur($idJoueur) {
        $sql = "
            SELECT
                COUNT(*) as total_matchs,
                SUM(CASE WHEN m.resultat_match = 'Victoire' THEN 1 ELSE 0 END) as victoires,
                SUM(CASE WHEN m.resultat_match = 'Défaite' THEN 1 ELSE 0 END) as defaites,
                SUM(CASE WHEN m.resultat_match = 'Nul' THEN 1 ELSE 0 END) as nuls,
                SUM(CASE WHEN m.lieu_match = 'Domicile' THEN 1 ELSE 0 END) as domicile,
                SUM(CASE WHEN m.lieu_match = 'Extérieur' THEN 1 ELSE 0 END) as exterieur
            FROM Participer p
            INNER JOIN Matchs m ON p.id_match = m.id_match
            WHERE p.id_joueur = ?
        ";
        return $this->obtenirUnResultat($sql, [$idJoueur]);
    }

    /**
    * Récupérer les performances individuelles d'un joueur
    */
    public function obtenirPerformancesJoueur($idJoueur) {
      $sql = "
        SELECT
            SUM(p.points_marques) as total_buts,
            SUM(p.passes_decisives) as total_passes,
            SUM(p.tirs_cadres) as total_tirs_cadres,
            SUM(p.tirs_non_cadres) as total_tirs_non_cadres,
            AVG(p.points_marques) as moyenne_buts,
            AVG(p.passes_decisives) as moyenne_passes,
            MAX(p.points_marques) as meilleur_score,
            COUNT(DISTINCT p.id_match) as total_matchs_joues
        FROM Participer p
        WHERE p.id_joueur = ? AND p.temps_joue_minutes > 0
        ";
      return $this->obtenirUnResultat($sql, [$idJoueur]);
    }

    /**
    * Récupérer les derniers matchs joués par un joueur
    */
    public function obtenirDerniersMatchsJoueur($idJoueur, $limite = 10) {
      $sql = "
        SELECT
            m.date_heure_match,
            m.equipe_adverse,
            m.lieu_match,
            m.resultat_match,
            m.score_equipe,
            m.score_adverse,
            p.points_marques,
            p.passes_decisives,
            p.temps_joue_minutes,
            p.tirs_cadres,
            p.tirs_non_cadres,
            p.statut_participation  -- AJOUTÉ ICI
        FROM Participer p
        INNER JOIN Matchs m ON p.id_match = m.id_match
        WHERE p.id_joueur = ? AND p.temps_joue_minutes > 0
        ORDER BY m.date_heure_match DESC
        LIMIT $limite
        ";
      return $this->obtenirTousResultats($sql, [$idJoueur]);
    }

    /**
    * Récupérer les statistiques complètes des joueurs
    */
    public function obtenirStatistiquesJoueurs() {
      $sql = "
        SELECT
            j.id_joueur,
            j.nom_joueur,
            j.prenom_joueur,
            j.numero_licence,
            j.statut_joueur,
            COUNT(p.id_participation) as total_selections,
            SUM(CASE WHEN p.statut_participation = 'Titulaire' THEN 1 ELSE 0 END) as nb_titularisations,
            SUM(CASE WHEN p.statut_participation = 'Remplaçant' THEN 1 ELSE 0 END) as nb_remplacements,
            COALESCE(AVG(p.note_joueur), 0) as moyenne_evaluations,
            SUM(p.points_marques) as total_buts,
            SUM(p.temps_joue_minutes) as total_minutes,
            SUM(p.passes_decisives) as total_passes,
            SUM(p.tirs_cadres) as total_tirs_cadres,
            SUM(p.tirs_non_cadres) as total_tirs_non_cadres,
            SUM(p.fautes_commises) as total_fautes,
            COUNT(DISTINCT CASE WHEN m.resultat_match = 'Victoire' THEN m.id_match END) as matchs_gagnes
        FROM Joueur j
        LEFT JOIN Participer p ON j.id_joueur = p.id_joueur
        LEFT JOIN Matchs m ON p.id_match = m.id_match
        WHERE j.statut_joueur = 'Actif'
        GROUP BY j.id_joueur
        ORDER BY moyenne_evaluations DESC, total_buts DESC, total_selections DESC
        ";
      return $this->obtenirTousResultats($sql);
    }

    // ==================== MÉTHODES MATCHS ====================

    /**
     * Récupérer un match par son ID
     */
    public function obtenirMatchParId($idMatch) {
        $sql = "SELECT * FROM Matchs WHERE id_match = ?";
        return $this->obtenirUnResultat($sql, [$idMatch]);
    }

    /**
     * Récupérer tous les matchs avec filtre optionnel
     */
    public function obtenirMatchs($filtre = 'tous') {
        $where = '';

        switch ($filtre) {
            case 'a-venir':
                $where = "WHERE date_heure_match >= NOW()";
                break;
            case 'passes':
                $where = "WHERE date_heure_match < NOW()";
                break;
            case 'victoires':
                $where = "WHERE resultat_match = 'Victoire'";
                break;
            case 'defaites':
                $where = "WHERE resultat_match = 'Défaite'";
                break;
            case 'nuls':
                $where = "WHERE resultat_match = 'Nul'";
                break;
            case 'domicile':
                $where = "WHERE lieu_match = 'Domicile'";
                break;
            case 'exterieur':
                $where = "WHERE lieu_match = 'Extérieur'";
                break;
        }

        $sql = "SELECT * FROM Matchs $where ORDER BY date_heure_match DESC";
        return $this->obtenirTousResultats($sql);
    }

    /**
     * Récupérer les matchs passés
     */
    public function obtenirMatchsPasses() {
        $sql = "
            SELECT * FROM Matchs
            WHERE date_heure_match < NOW()
            ORDER BY date_heure_match DESC
        ";
        return $this->obtenirTousResultats($sql);
    }

    /**
    * Récupérer les prochains matchs
    */
    public function obtenirProchainsMatchs($limite = 5) {
      $sql = "
        SELECT *
        FROM Matchs
        WHERE statut_match IN ('À venir', 'Préparé')
        ORDER BY date_heure_match ASC
        LIMIT ?
        ";

      $requete = $this->connexion->prepare($sql);
      $requete->bindValue(1, $limite, PDO::PARAM_INT);
      $requete->execute();

      return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les derniers résultats
     */
    public function obtenirDerniersResultats($limite = 5) {
        $sql = "
            SELECT * FROM Matchs
            WHERE statut_match = 'Terminé'
            ORDER BY date_heure_match DESC
            LIMIT ?
        ";

        $requete = $this->connexion->prepare($sql);
        $requete->bindValue(1, $limite, PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insérer un nouveau match
     */
    public function insererMatch($donnees) {
        $sql = "INSERT INTO Matchs (
                    equipe_adverse, date_heure_match, lieu_match,
                    score_equipe, score_adverse, resultat_match,
                    statut_match, commentaires_match
                ) VALUES (
                    :equipe_adverse, :date_heure_match, :lieu_match,
                    :score_equipe, :score_adverse, :resultat_match,
                    :statut_match, :commentaires_match
                )";

        $requete = $this->executerRequete($sql, $donnees);

        if ($requete) {
            return $this->connexion->lastInsertId();
        }

        return false;
    }

    /**
     * Mettre à jour un match
     */
    public function mettreAJourMatch($idMatch, $parametres) {
        $sql = "UPDATE Matchs SET
                    equipe_adverse = :equipe_adverse,
                    date_heure_match = :date_heure_match,
                    lieu_match = :lieu_match,
                    score_equipe = :score_equipe,
                    score_adverse = :score_adverse,
                    resultat_match = :resultat_match,
                    statut_match = :statut_match,
                    commentaires_match = :commentaires_match
                WHERE id_match = :id_match";

        return $this->executerRequete($sql, $parametres);
    }

    /**
     * Récupérer les statistiques globales des matchs
     */
    public function obtenirStatistiquesMatchs() {
        $sql = "
            SELECT
                COUNT(*) as total_matchs,
                SUM(CASE WHEN resultat_match = 'Victoire' THEN 1 ELSE 0 END) as victoires,
                SUM(CASE WHEN resultat_match = 'Défaite' THEN 1 ELSE 0 END) as defaites,
                SUM(CASE WHEN resultat_match = 'Nul' THEN 1 ELSE 0 END) as nuls,
                SUM(CASE WHEN date_heure_match >= NOW() THEN 1 ELSE 0 END) as a_venir,
                SUM(CASE WHEN date_heure_match < NOW() THEN 1 ELSE 0 END) as passes,
                SUM(CASE WHEN lieu_match = 'Domicile' THEN 1 ELSE 0 END) as domicile,
                SUM(CASE WHEN lieu_match = 'Extérieur' THEN 1 ELSE 0 END) as exterieur,
                SUM(score_equipe) as buts_marques,
                SUM(score_adverse) as buts_encaisses
            FROM Matchs
        ";
        return $this->obtenirUnResultat($sql);
    }

    /**
     * Supprimer un match (et ses participations associées)
     */
    public function supprimerMatch($idMatch) {
        // Supprimer d'abord les participations
        $sqlParticipations = "DELETE FROM Participer WHERE id_match = ?";
        $this->executerRequete($sqlParticipations, [$idMatch]);

        // Puis supprimer le match
        $sqlMatch = "DELETE FROM Matchs WHERE id_match = ?";
        $resultat = $this->executerRequete($sqlMatch, [$idMatch]);

        return $resultat ? true : false;
    }

    // ==================== MÉTHODES PARTICIPATIONS ====================

    /**
     * Récupérer les joueurs sélectionnés pour un match
     */
    public function obtenirJoueursMatch($idMatch) {
        $sql = "
            SELECT p.*, j.*
            FROM Participer p
            INNER JOIN Joueur j ON p.id_joueur = j.id_joueur
            WHERE p.id_match = ?
            ORDER BY
                CASE p.statut_participation
                    WHEN 'Titulaire' THEN 1
                    WHEN 'Remplaçant' THEN 2
                    ELSE 3
                END,
                j.nom_joueur
        ";
        return $this->obtenirTousResultats($sql, [$idMatch]);
    }

    /**
     * Compter le nombre de joueurs sélectionnés pour un match
     */
    public function compterJoueursMatch($idMatch) {
        $sql = "SELECT COUNT(*) as total FROM Participer WHERE id_match = ?";
        $resultat = $this->obtenirUnResultat($sql, [$idMatch]);
        return $resultat ? $resultat['total'] : 0;
    }

    /**
     * Insérer une participation
     */
    public function insererParticipation($donnees) {
        $sql = "INSERT INTO Participer (
                    id_joueur, id_match, statut_participation,
                    temps_joue_minutes, points_marques, fautes_commises, note_joueur
                ) VALUES (
                    :id_joueur, :id_match, :statut,
                    :temps_jeu, :buts, :fautes, :note
                )";

        return $this->executerRequete($sql, $donnees);
    }

    /**
     * Mettre à jour une participation (évaluation)
     */
    public function mettreAJourParticipation($idJoueur, $idMatch, $parametres) {
        $sql = "UPDATE Participer SET
                    temps_joue_minutes = :temps_jeu,
                    points_marques = :buts,
                    fautes_commises = :fautes,
                    note_joueur = :note,
                    commentaires_participation = :commentaire
                WHERE id_joueur = :id_joueur AND id_match = :id_match";

        return $this->executerRequete($sql, $parametres);
    }

    /**
     * Supprimer toutes les participations d'un match
     */
    public function supprimerParticipationsMatch($idMatch) {
        $sql = "DELETE FROM Participer WHERE id_match = ?";
        return $this->executerRequete($sql, [$idMatch]);
    }

    // ==================== MÉTHODES STATISTIQUES ====================

    /**
 * Récupérer les statistiques de sélection d'un joueur
 */
public function obtenirStatsSelectionsJoueur($idJoueur) {
    $sql = "
        SELECT
            COUNT(*) as total_selections,
            SUM(CASE WHEN statut_participation = 'Titulaire' THEN 1 ELSE 0 END) as titularisations,
            SUM(CASE WHEN statut_participation = 'Remplaçant' THEN 1 ELSE 0 END) as remplacements,
            SUM(CASE WHEN statut_participation = 'Blessé' THEN 1 ELSE 0 END) as blessures,
            SUM(CASE WHEN statut_participation = 'Suspendu' THEN 1 ELSE 0 END) as suspensions,
            SUM(CASE WHEN statut_participation = 'Absent' THEN 1 ELSE 0 END) as absences
        FROM Participer
        WHERE id_joueur = ? AND temps_joue_minutes >= 0
    ";
    return $this->obtenirUnResultat($sql, [$idJoueur]);
}

    /**
     * Récupérer le poste préféré d'un joueur
     */
    public function obtenirPostePreferJoueur($idJoueur) {
        $sql = "
            SELECT
                p.statut_participation as poste,
                AVG(p.note_joueur) as moyenne_note
            FROM Participer p
            WHERE p.id_joueur = ? AND p.note_joueur > 0
            GROUP BY p.statut_participation
            ORDER BY moyenne_note DESC
            LIMIT 1
        ";
        $resultat = $this->obtenirUnResultat($sql, [$idJoueur]);
        return $resultat ? $resultat['poste'] : 'N/A';
    }

    /**
     * Récupérer les sélections consécutives d'un joueur
     */
    public function obtenirSelectionsConsecutives($idJoueur) {
        $sql = "
            SELECT m.date_heure_match
            FROM Participer p
            INNER JOIN Matchs m ON p.id_match = m.id_match
            WHERE p.id_joueur = ?
            ORDER BY m.date_heure_match DESC
        ";
        return $this->obtenirTousResultats($sql, [$idJoueur]);
    }

    /**
     * Récupérer les statistiques du tableau de bord
     */
    public function obtenirStatistiquesDashboard() {
        $stats = [
            'total_joueurs' => 0,
            'joueurs_actifs' => 0,
            'joueurs_blesses' => 0,
            'total_matchs' => 0,
            'matchs_gagnes' => 0,
            'matchs_perdus' => 0,
            'matchs_nuls' => 0,
            'prochains_matchs' => 0
        ];

        try {
            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Joueur");
            $stats['total_joueurs'] = $resultat ? $resultat['total'] : 0;

            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Joueur WHERE statut_joueur = 'Actif'");
            $stats['joueurs_actifs'] = $resultat ? $resultat['total'] : 0;

            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Joueur WHERE statut_joueur = 'Blessé'");
            $stats['joueurs_blesses'] = $resultat ? $resultat['total'] : 0;

            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Matchs");
            $stats['total_matchs'] = $resultat ? $resultat['total'] : 0;

            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Matchs WHERE resultat_match = 'Victoire'");
            $stats['matchs_gagnes'] = $resultat ? $resultat['total'] : 0;

            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Matchs WHERE resultat_match = 'Défaite'");
            $stats['matchs_perdus'] = $resultat ? $resultat['total'] : 0;

            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Matchs WHERE resultat_match = 'Nul'");
            $stats['matchs_nuls'] = $resultat ? $resultat['total'] : 0;

            $resultat = $this->obtenirUnResultat("SELECT COUNT(*) as total FROM Matchs WHERE statut_match IN ('À venir', 'Préparé')");
            $stats['prochains_matchs'] = $resultat ? $resultat['total'] : 0;

        } catch (Exception $e) {
            error_log("Erreur statistiques dashboard: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Récupérer les derniers joueurs ajoutés
     */
    public function obtenirDerniersJoueurs($limite = 5) {
        $sql = "
            SELECT *
            FROM Joueur
            ORDER BY date_ajout DESC
            LIMIT ?
        ";

        $requete = $this->connexion->prepare($sql);
        $requete->bindValue(1, $limite, PDO::PARAM_INT);
        $requete->execute();

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les statistiques globales de l'équipe
     */
    public function obtenirStatistiquesGlobalesEquipe() {
        $sql = "
            SELECT
                COUNT(*) as total_matchs,
                SUM(CASE WHEN resultat_match = 'Victoire' THEN 1 ELSE 0 END) as victoires,
                SUM(CASE WHEN resultat_match = 'Défaite' THEN 1 ELSE 0 END) as defaites,
                SUM(CASE WHEN resultat_match = 'Nul' THEN 1 ELSE 0 END) as nuls,
                SUM(score_equipe) as buts_marques,
                SUM(score_adverse) as buts_encaisses
            FROM Matchs
            WHERE resultat_match != 'À venir'
        ";
        return $this->obtenirUnResultat($sql);
    }
}
?>
