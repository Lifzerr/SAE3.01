<?php

class ThemeDAO{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null){
        $this->pdo = $pdo;
    }

    //Encapsulation
    //Getter
    public function getPdo(): ?PDO{
        return $this->pdo;
    }

    //Setter
    public function setPdo($pdo): void{
        $this->pdo = $pdo;
    }

    //Methodes
    //A tester quand la BD sera mise en place
    //Methodes find
    public function findAssoc(?int $id): ?array
    {
        $sql="SELECT * FROM ".DB_PREFIX."theme WHERE id= :id";
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->execute(array("id"=>$id));
        $pdoStatement->setFetchMode(PDO::FETCH_ASSOC);
        $theme = $pdoStatement->fetch();
        return $theme;
    }
    //But : Trouve une theme en fonction de son identifiant - Version Assoc

    public function findAllAssoc(){
        $sql="SELECT * FROM ".DB_PREFIX."theme";
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->execute();
        $pdoStatement->setFetchMode(PDO::FETCH_ASSOC);
        $theme = $pdoStatement->fetchAll();
        return $theme;
    }
    //But : Trouve toutes les themes - Version Assoc

    public function findThemesByContenuId(int $contenuId): array {
        $sql = "SELECT t.* 
                FROM " . DB_PREFIX . "theme t
                INNER JOIN " . DB_PREFIX . "caracteriserContenu cc ON t.idTheme = cc.idTheme
                WHERE cc.idContenu = :contenuId";
    
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->execute(['contenuId' => $contenuId]);
        $pdoStatement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Theme');
        $themes = $pdoStatement->fetchAll();
    
        return $themes;
    }
    //But : Trouve les themes en fonction de l'identifiant du contenu

    public function findThemesByCollectionId(int $collectionId): array {
        $sql = "SELECT t.* 
                FROM " . DB_PREFIX . "theme t
                INNER JOIN " . DB_PREFIX . "caracteriserCollection cc ON t.idTheme = cc.idTheme
                WHERE cc.idCollection = :collectionId";
    
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->execute(['collectionId' => $collectionId]);
        $pdoStatement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Theme');
        $themes = $pdoStatement->fetchAll();
    
        return $themes;
    }

    //Methodes hydrate
    public function hydrate($tableauAssoc): ?Theme
    {
        $theme = new Theme($tableauAssoc['id'], $tableauAssoc['nom']);

        return $theme;
    }
    //But : Créer une theme avec les valeurs assignées aux attributs correspondants

    public function hydrateAll($tableau): ?array{
        $themes = [];
        foreach($tableau as $tableauAssoc){
            $theme = $this->hydrate($tableauAssoc);
            $themes[] = $theme;
        }

        return $themes;
    }
    //But : Créer les themes avec les valeurs assignées aux attributs correspondants

    public function createIfNotExists(Theme $theme): ?Theme {
        // Vérifie si le thème existe déjà
        $sql = "SELECT * FROM " . DB_PREFIX . "theme WHERE nom = :nom";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['nom' => $theme->getNom()]);
        $existingTheme = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingTheme) {
            return new Theme($existingTheme['idTheme'], $existingTheme['nom']);
        }

        // Si le thème n'existe pas, on le crée
        $sql = "INSERT INTO " . DB_PREFIX . "theme (nom) VALUES (:nom)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['nom' => $theme->getNom()]);
        
        return new Theme($this->pdo->lastInsertId(), $theme->getNom());
    }

    public function associateThemeWithContenu(int $themeId, int $contenuId): bool {
        $sql = "INSERT INTO " . DB_PREFIX . "caracteriserContenu (idTheme, idContenu) 
                VALUES (:themeId, :contenuId)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'themeId' => $themeId,
            'contenuId' => $contenuId
        ]);
    }
}

?>