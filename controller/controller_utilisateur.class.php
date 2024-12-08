<?php


class ControllerUtilisateur extends Controller
{
    public function __construct(\Twig\Environment $twig, \Twig\Loader\FilesystemLoader $loader)
    {
        parent::__construct($twig, $loader);
    }


    /**
     * @bref permet d'afficher la page de connection
     *
     * @return void
     */
    public function connexion(): void
    {

        //Génération de la vue
        $template = $this->getTwig()->load('connection.html.twig');
        echo $template->render(array());


    }

    /**
     * @brief permet d'afficher la page d'inscription
     * 
     * @return void
     */
    public function inscription(): void
    {

        //Génération de la vue
        $template = $this->getTwig()->load('inscription.html.twig');
        echo $template->render(array(
            'description' => "Je fais mes tests"
        ));


    }

    /**
     * @brief vérifie les informations saisies lors de la connection et connecte l'utilisateur et revoit sur la page d'acceuil si les informations sont correctes  sinon renvoie sur la page de connection
     *
     * @return void
     */
    public function checkInfoConnecter()
    {

        $mail = isset($_POST['mail']) ? $_POST['mail'] : null;
        $mdp = isset($_POST['pwd']) ? $_POST['pwd'] : null;

        $mail = str_replace(' ', '', $mail);

        $managerutilisateur = new UtilisateurDAO($this->getPdo());

        $utilisateur = $managerutilisateur->findByMail($mail);

        if ($utilisateur != null && password_verify($mdp, $utilisateur->getMdp())) {
            $_SESSION['utilisateur'] = serialize($utilisateur);
            //Génération de la vue
            $this->show();
        } else {
            //Génération de la vue
            $template = $this->getTwig()->load('connection.html.twig');
            echo $template->render(array());

        }


    }
    /**
     * @brief vérifie les informations saisies lors de l'inscription et crée un utilisateur si les informations sont correctes et renvoie sur la page de connection   sinon renvoie sur la page d'inscription
     * 
     * @return void
     */
    public function checkInfoInscription()
    {

        //réccupération des données du formulaire
        $id = isset($_POST['idantifiant']) ? $_POST['idantifiant'] : null;
        $pseudo = isset($_POST['pseudo']) ? $_POST['pseudo'] : null;
        $mail = isset($_POST['mail']) ? $_POST['mail'] : null;
        $date = isset($_POST['date']) ? $_POST['date'] : null;
        $mdp = isset($_POST['mdp']) ? $_POST['mdp'] : null;
        $vmdp = isset($_POST['vmdp']) ? $_POST['vmdp'] : null;
        $nom = isset($_POST['nom']) ? $_POST['nom'] : null;
        //supprimer les espaces

        $id = str_replace(' ', '', $id);
        $mail = str_replace(' ', '', $mail);

        $managerutilisateur = new UtilisateurDAO($this->getPdo());
        // vérification que la personne est assez vieille
        if (strtotime($date) < strtotime(date("Y-m-d") . " -156 month")) {




            // verifier si l'id n'est pas déjà utilisé
            if ($managerutilisateur->find($id) == null) {
                // verifier si le mail est valide 
                if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                    // verifier si le mail n'est pas déjà utilisé
                    if ($managerutilisateur->findByMail($mail) == null) {
                        // verifier si les deux mots de passe sont identiques
                        if ($mdp == $vmdp) {
                            //cripter le mot de passe
                            $mdp = password_hash($mdp, PASSWORD_DEFAULT);


                            //création de l'utilisateur
                            $newUtilisateur = new Utilisateur($id, $pseudo, $nom, $mail, $mdp, "Utilisateur", "images/image_de_profil_de_base.svg", "images/Baniere_de_base.png");
                            $managerutilisateur->create($newUtilisateur);
                            //Génération de la vue
                            $template = $this->getTwig()->load('connection.html.twig');
                            echo $template->render(array(
                                'description' => "Je fais mes tests"
                            ));
                            return;
                        }
                    }
                }
            }
        }
        //erreur de saisie
        //Génération de la vue
        $template = $this->getTwig()->load('inscription.html.twig');
        echo $template->render(array());
    }

    /**
     * @brief afficher la page ou l'utilisateur rensegner son adre mail pour changer le mot de passe
     * 
     * @return void
     */
    public function afficherpageMDPOublier(): void
    {

        $template = $this->getTwig()->load('motDePasseOublie.html.twig');
        echo $template->render(array());

    }
    /**
     * @brief envoie un mail à l'utilisateur pour qu'il puisse changer son mot de passe
     * @todo cryptage de id dans le lien du mail
     * @return void
     */
    public function envoieMailMDPOublie(): void
    {
        $mail = isset($_POST['email']) ? $_POST['email'] : null;
        $mail = str_replace(' ', '', $mail);
        $managerutilisateur = new UtilisateurDAO($this->getPdo());
        $utilisateur = $managerutilisateur->findByMail($mail);
        if ($utilisateur != null) {
            // crypter le id
            $id = $utilisateur->getId();
            $message = "Bonjour, \n\n Vous avez demandé à réinitialiser votre mot de passe. Voici votre lien pour changer de mot de passe : " . WEBSITE_LINK . "index.php?controller=utilisateur&methode=afficherchangerMDP&id=" . $id . " \n\n Cordialement, \n\n L'équipe de la plateforme de vhs";
            mail($mail, "Réinitialisation de votre mot de passe", $message);
            $template = $this->getTwig()->load('connection.html.twig');
            echo $template->render(array());
        } else {
            //générer une popup pour dire que le mail n'est pas valide

            $template = $this->getTwig()->load('inscription.html.twig');
            echo $template->render(array());
        }
    }

    /**
     * @brief affiche la page ou l'utilisateur peut changer son mot de passe
     * @todo decryptage de id dans GET
     * @return void
     */
    public function afficherchangerMDP(): void
    {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $managerutilisateur = new UtilisateurDAO($this->getPdo());
        if ($id != null) {


            $utilisateur = $managerutilisateur->find($id);
            if ($utilisateur != null) {


                $template = $this->getTwig()->load('changerMDP.html.twig');
                echo $template->render(array('id' => $id));

            }
        }
    }

    /**
     * @brief permet de changer le mot de passe de l'utilisateur et met à jour la base de données
     * 
     * @return void
     */
    public function changerMDP(): void
    {
        // récupération des données du formulaire
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        $mdp = isset($_POST['mdp']) ? $_POST['mdp'] : null;
        $vmdp = isset($_POST['vmdp']) ? $_POST['vmdp'] : null;
        $managerutilisateur = new UtilisateurDAO($this->getPdo());
        $utilisateur = $managerutilisateur->find($id);
        if ($utilisateur != null) {
            if (strlen($mdp) <= 100 && strlen($vmdp) <= 100) {


                if ($mdp == $vmdp) {
                    //crypter le mot de passe
                    $mdp = password_hash($mdp, PASSWORD_DEFAULT);
                    $utilisateur->setMdp($mdp);

                    // modifier le mot de passe dans la base de données
                    $managerutilisateur->update($utilisateur);
                    $template = $this->getTwig()->load('connection.html.twig');
                    echo $template->render(array());
                } else {
                    $template = $this->getTwig()->load('changerMDP.html.twig');
                    echo $template->render(array('id' => $id));
                }
            }
        }
    }

    /**
     *  @brief permet d'afficher lapage d'un utilisateur 
     */
    public function show(): void
    {
        // Récupère id_utlisateur dans $_GET et affiche la page du profil de l'utilisateur
        $id = isset($_GET['id_utilisateur']) ? $_GET['id_utilisateur'] : null;



        // recuper que si l'utilisateur est connecter
        if (isset($_SESSION['utilisateur'])) {
            //recuperer l'utilisateur connecter if
            $personneConnect = unserialize($_SESSION['utilisateur']);
            if ($id == null) {
                $id = $personneConnect->getId();
            }
        } else {
            $personneConnect = null;
        }

        // instanciation des managers de base de données
        $managerutilisateur = new UtilisateurDAO($this->getPdo());
        $managermesage = new MessageDAO($this->getPdo());

        //récupération de l'utilisateur à partir de id dans l'url
        $utilisateur = $managerutilisateur->find($id);

        //récupération des messages de l'utilisateur
        $messages = $managermesage->listerMessagesParIdUser($id);
        if ($utilisateur != null) {
            $template = $this->getTwig()->load('profilUtilisateur.html.twig');
            echo $template->render(array('utilisateur' => $utilisateur, 'messages' => $messages, 'utilisateurConnecter' => $personneConnect));
            return;
        }

        //Génération de la vue
        $template = $this->getTwig()->load('connection.html.twig');
        echo $template->render(array());

    }

    /**
     * @brief permet de modifier les informations de l'utilisateur
     */
    public function edit(): void
    {
        if (isset($_SESSION['utilisateur'])) {
            //recuperer l'utilisateur connecter if
            $utilisateur = unserialize($_SESSION['utilisateur']);
        } else {
            $utilisateur = null;
        }

        if ($utilisateur != null) {
            //Génération de la vue
            $template = $this->getTwig()->load('modifierUtilisateur.html.twig');
            echo $template->render(array('utilisateur' => $utilisateur));
        } else {
            //Génération de la vue
            $template = $this->getTwig()->load('index.html.twig');
            echo $template->render(array());
        }

    }



    /**
     * @brief permet de verifier modifier les informations de l'utilisateur dans la base de données et renvoie sur la page de edition
     */
    public function modificationprofil(): void
    {
        // récupération des données du formulaire
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $pseudo = isset($_POST['pseudo']) ? $_POST['pseudo'] : null;
        $nom = isset($_POST['nom']) ? $_POST['nom'] : null;


        $utilisateur = unserialize($_SESSION['utilisateur']);

        //supprimer les espaces
        $id = str_replace(' ', '', $id);
        $pseudo = str_replace(' ', '', $pseudo);


        $managerutilisateur = new UtilisateurDAO($this->getPdo());

        if (strlen($pseudo) <= 50 && strlen($id) <= 20 && strlen($nom) <= 50) {
            // verifier si l'id n'est pas déjà utilisé
            if ($managerutilisateur->find($id) == null || $id == $utilisateur->getId()) {
                //création de l'utilisateur
                $utilisateur->setId($id);
                $utilisateur->setPseudo($pseudo);
                $utilisateur->setNom($nom);
                // récupérer les fichiers images  de profil
                if (isset(($_FILES['urlImageProfil']))) {

                    if ($_FILES['urlImageProfil']['name'] != '') {
                        //supprimer l'ancienne image
                        if (file_exists($utilisateur->getUrlImageProfil()) && $utilisateur->getUrlImageProfil() != "images/image_de_profil_de_base.svg") {
                            unlink($utilisateur->getUrlImageProfil());
                        }
                        //récupérer le fichier image
                        $urlImageProfil = isset($_FILES['urlImageProfil']) ? $_FILES['urlImageProfil'] : null;
                        // donne le bon nom à l'image
                        $urlImageProfil['name'] = "images/imageProfil_" . $utilisateur->getId() . "." . pathinfo($urlImageProfil['name'], PATHINFO_EXTENSION);
                        //telecharger l'image
                        move_uploaded_file($urlImageProfil["tmp_name"], $urlImageProfil['name']);
                        // mettre à jour l'url de l'image dans l'ojet utilisateur
                        $utilisateur->setUrlImageProfil($urlImageProfil['name']);

                    }
                }
                if (isset(($_FILES['urlImageBaniere']))) {
                    if ($_FILES['urlImageBaniere']['name'] != '') {
                        //supprimer l'ancienne image
                        if (file_exists($utilisateur->getUrlImageBaniere()) && $utilisateur->getUrlImageProfil() != "images/Baniere_de_base.png") {
                            unlink($utilisateur->getUrlImageBaniere());
                        }
                        //récupérer le fichier image
                        $urlImageBaniere = isset($_FILES['urlImageBaniere']) ? $_FILES['urlImageBaniere'] : null;
                        // donne le bon nom à l'image
                        $urlImageBaniere['name'] = "images/imageBaniere_" . $utilisateur->getId() . "." . pathinfo($urlImageBaniere['name'], PATHINFO_EXTENSION);
                        //telecharger l'image
                        move_uploaded_file($urlImageBaniere["tmp_name"], $urlImageBaniere['name']);
                        // mettre à jour l'url de l'image dans l'ojet utilisateur
                        $utilisateur->setUrlImageBaniere($urlImageBaniere['name']);
                    }
                }
                // mettre à jour l'utilisateur dans la base de données
                $managerutilisateur->update($utilisateur);
                $_SESSION['utilisateur'] = serialize($utilisateur);
                //Génération de la vue
                $template = $this->getTwig()->load('modifierUtilisateur.html.twig');
                echo $template->render(array('utilisateur' => $utilisateur));
                return;
            }
        }

    }

    /**
     * @brief permet de changer le mail de l'utilisateur
     */
    public function changerMail(): void
    {
        // récupération des données du formulaire
        $mail = isset($_POST['mail']) ? $_POST['mail'] : null;
        $mailconf = isset($_POST['mailconf']) ? $_POST['mailconf'] : null;

        //supprimer les espaces
        $mail = str_replace(' ', '', $mail);
        $mailconf = str_replace(' ', '', $mailconf);
        // vérifier si les deux mails sont identiques
        if ($mail == $mailconf) {
            // vérifier si le mail est valide
            if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {

                $utilisateur = unserialize($_SESSION['utilisateur']);
                $managerutilisateur = new UtilisateurDAO($this->getPdo());
                // vérifier si le mail n'est pas déjà utilisé
                if ($managerutilisateur->findByMail($mail) == null) {
                    // mettre à jour le mail de l'utilisateur
                    $utilisateur->setMail($mail);
                    $managerutilisateur->update($utilisateur);
                    $_SESSION['utilisateur'] = serialize($utilisateur);

                }
            }
        }
        //Génération de la vue
        $template = $this->getTwig()->load('modifierUtilisateur.html.twig');
        echo $template->render(array('utilisateur' => $utilisateur));


    }
    /**
     * @bref affiche le notification de l'utilisateur
     * @return void
     */
    public function notification(): void
    {

        $template = $this->getTwig()->load('enConstruction.htlm.twig');
        echo $template->render(array('fontionliter' => "notification"));
    }


}