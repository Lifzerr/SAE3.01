<?php

/**
 * @file message.dao.php
 * 
 * @brief Classe MessageDAO
 * 
 * @details Classe permettant de gérer les accès à la base de données pour les messages
 * 
 * @date 12/11/2024
 * 
 * @version 1.0
 * 
 * @author Maxime Bourciez <maxime.bourciez@gmail.com>
 */
class MessageDAO
{
    // Attiributs 
    /**
     * @var PDO|null $pdo Connexion à la BD
     */
    private ?PDO $pdo;

    // Constructeur
    /**
     * @brief Constructeur de la classe MessageDAO
     * 
     * @param PDO|null $pdo Connexion à la base de données
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    // Encapsulation
    // Getter
    /**
     * @brief Getter de la connexion à la base de données
     *
     * @return PDO|null Connexion à la base de données
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    // Setter
    /**
     * @brief Setter de la connexion à la base de données
     * 
     * @param PDO $pdo Connexion à la base de données
     * @return self
     */
    public function setPdo(PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    // Méthodes
    // Méthodes d'hydratation
    /**
     * @brief Méthode d'hydratation d'un message
     * 
     * @param array $row Ligne de la base de données
     * @return Message Objet Message hydraté
     */
    public function hydrate(array $row): Message
    {
        $message = new Message();
        $message->setIdMessage($row['idMessage']);
        $message->setValeur($row['valeur']);
        $message->setDateC(new DateTime($row['dateC']));
        $message->setIdMessageParent($row['idMessageParent']);

        // Hydratation de l'utilisateur associé
        $user = new Utilisateur();
        $user->setId($row['idUtilisateur']);
        $user->setPseudo($row['pseudo']);
        $user->setUrlImageProfil($row['urlImageProfil']);
        $message->setUtilisateur($user);

        return $message;
    }
    /**
     * @brief Méthode d'hydratation de tous les messages avec gestion des réponses
     *
     * @param array $rows Tableau de lignes récupérées de la base de données
     * @return array<Message> Tableau d'objets Message hydratés, avec réponses associées
     */
    public function hydrateAll(array $rows): array
    {
        $messages = []; // Tous les messages indexés par leur ID
        $messagesParents = []; // Tableau pour stocker uniquement les messages principaux
    
        // Hydrater tous les messages et les indexer par ID
        foreach ($rows as $row) {
            $message = $this->hydrate($row);
            $messages[$message->getIdMessage()] = $message;
        }
    
        // Lier les réponses à leurs parents
        foreach ($messages as $message) {
            if ($message->getIdMessageParent() === null) {
                // Message sans parent -> c'est un message principal
                $messagesParents[] = $message;
            } else {
                // Message avec parent -> ajouter comme réponse
                $parentId = $message->getIdMessageParent();
                if (isset($messages[$parentId])) {
                    $messages[$parentId]->addReponse($message);
                }
            }
        }
    
        // Retourner uniquement les messages principaux (avec les réponses déjà attachées)
        return $messagesParents;
    }
    


    // Méthodes de recherche
    /**
     * @brief Méthode permettant de lister tous les messages
     * 
     * @return array<Message> Tableau d'objets Message
     * 
     */
    public function listerMessages(): array
    {
        $sql = "SELECT * FROM" . DB_PREFIX . "message";
        $stmt = $this->pdo->query($sql);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $this->hydrateAll($stmt->fetchAll());
    }

    /**
     * @brief Méthode permettant de chercher un message par son id
     *
     * @note useless pour le moment
     *
     * @param integer $id
     * @return Message|null
     */
    public function chercherMessageParId(int $id): ?Message
    {
        $sql = "SELECT * FROM" . DB_PREFIX . "message  WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $this->hydrate($stmt->fetch());
    }




    /**
     * @brief Méthode permettant de lister les messages d'un utilisateur par id_user
     *
     * @details Méthode permettant de lister les messages d'un utilisateur par son id pour les lui afficher sur son profil
     * 
     * @param integer $id_user
     * @return array
     */
    public function listerMessagesParIdUser( ?string $id_user): array
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "message M join " . DB_PREFIX . "utilisateur U ON  M.idUtilisateur=U.idUtilisateur WHERE M.idUtilisateur = :id_user ORDER BY dateC DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_user', $id_user, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $messages = $stmt->fetchAll();

        return ($this->hydrateAll($messages));
    }



    /**
     * @brief Méthode de listing des messages par fil
     * 
     * @details Méthode permettant de lister les messages d'un fil de discussion par son id
     * 
     * @param integer $id_fil Identifiant du fil
     * 
     * @return array<Message> Tableau d'objets Message
     */

    public function listerMessagesParFil(int $idFil): array
    {
        // $sql = "SELECT
        //         m1.*,
        //         COALESCE(m1.idMessageParent, m1.idMessage) AS thread_id,
        //         u1.idUtilisateur AS auteur_id,
        //         u1.pseudo AS auteur_pseudo,
        //         u1.urlImageProfil AS auteur_urlImageProfil,
        //         m2.idMessage AS reponse_id,
        //         m2.valeur AS reponse_valeur,
        //         m2.dateC AS reponse_dateC,
        //         m2.idUtilisateur AS reponse_utilisateur_id,
        //         u2.pseudo AS reponse_pseudo,
        //         u2.urlImageProfil AS reponse_urlImageProfil,
        //         ld1.like_count AS like_count,
        //         ld1.dislike_count AS dislike_count,
        //         ld2.like_count AS reponse_like_count,
        //         ld2.dislike_count AS reponse_dislike_count
        //     FROM (
        //         SELECT
        //             m.*,
        //             COALESCE(m.idMessageParent, m.idMessage) AS thread_id
        //         FROM " . DB_PREFIX . "message m
        //         WHERE m.idFil = :idFil
        //     ) AS m1
        //     INNER JOIN " . DB_PREFIX . "utilisateur u1 ON m1.idUtilisateur = u1.idUtilisateur
        //     LEFT JOIN " . DB_PREFIX . "message m2 ON m1.idMessage = m2.idMessageParent
        //     LEFT JOIN " . DB_PREFIX . "utilisateur u2 ON m2.idUtilisateur = u2.idUtilisateur
        //     LEFT JOIN (
        //         SELECT
        //             idMessage,
        //             SUM(CASE WHEN `reaction` = true THEN 1 ELSE 0 END) AS like_count,
        //             SUM(CASE WHEN `reaction` = false THEN 1 ELSE 0 END) AS dislike_count
        //         FROM " . DB_PREFIX . "reagir
        //         GROUP BY idMessage
        //     ) AS ld1 ON m1.idMessage = ld1.idMessage
        //     LEFT JOIN (
        //         SELECT
        //             idMessage,
        //             SUM(CASE WHEN `reaction` = true THEN 1 ELSE 0 END) AS like_count,
        //             SUM(CASE WHEN `reaction` = false THEN 1 ELSE 0 END) AS dislike_count
        //         FROM " . DB_PREFIX . "reagir
        //         GROUP BY idMessage
        //     ) AS ld2 ON m2.idMessage = ld2.idMessage
        //     ORDER BY m1.thread_id ASC, m1.dateC ASC;";

        $sql = "SELECT m.*, u.*, like_count, dislike_count
                FROM vhs_message m
                LEFT JOIN (
                                SELECT
                                    idMessage,
                                    SUM(CASE WHEN `reaction` = true THEN 1 ELSE 0 END) AS like_count,
                                    SUM(CASE WHEN `reaction` = false THEN 1 ELSE 0 END) AS dislike_count
                                FROM vhs_reagir
                                GROUP BY idMessage
                            ) AS ld1 ON m.idMessage = ld1.idMessage
                LEFT JOIN vhs_utilisateur u ON m.idUtilisateur = u.idUtilisateur
                WHERE m.idFil = :idFil;";


        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idFil', $idFil, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $messages = $stmt->fetchAll();

        // Hydrate les messages
        return $this->hydrateAll($messages);
    }

    // Nouvelle méthode pour récupérer le pseudo de l'utilisateur de la réponse
    private function getUserPseudoById(string $userId): string
    {
        $sql = "SELECT pseudo FROM " . DB_PREFIX . "utilisateur WHERE idUtilisateur = :idUtilisateur";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idUtilisateur', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['pseudo'] : 'Utilisateur inconnu';
    }
}
