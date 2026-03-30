<?php

namespace App\Controller;

use AllowDynamicProperties;
use App\Model\Annonce;
use App\Model\Annonceur;
use App\Model\Departement;
use App\Model\Photo;
use App\Model\Categorie;
use model\Annonce;

#[AllowDynamicProperties]
class ItemController {
    public function __construct(){
    }
    public function afficherItem($twig, $menu, $chemin, $n, $cat): void
    {

        $this->annonce = Annonce::find($n);
        if(!isset($this->annonce)){
            echo "404";
            return;
        }

        $menu = array(
            array('href' => $chemin,
                'text' => 'Acceuil'),
            array('href' => $chemin."/cat/".$n,
                'text' => Categorie::find($this->annonce->id_categorie)?->nom_categorie),
            array('href' => $chemin."/item/".$n,
            'text' => $this->annonce->titre)
        );

        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->departement = Departement::find($this->annonce->id_departement );
        $this->photo = Photo::where('id_annonce', '=', $n)->get();
        $template = $twig->load("item.html.twig");
        echo $template->render(array("breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "annonceur" => $this->annonceur,
            "dep" => $this->departement->nom_departement,
            "photo" => $this->photo,
            "categories" => $cat));
    }

    public function supprimerItemGet($twig, $menu, $chemin,$n){
        $this->annonce = Annonce::find($n);
        if(!isset($this->annonce)){
            echo "404";
            return;
        }
        $template = $twig->load("delGet.html.twig");
        echo $template->render(array("breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce));
    }


    public function supprimerItemPost($twig, $menu, $chemin, $n, $cat){
        $this->annonce = Annonce::find($n);
        $reponse = false;
        if(password_verify($_POST["pass"],$this->annonce->mdp)){
            $reponse = true;
            Photo::where('id_annonce', '=', $n)->delete();
            $this->annonce->delete();

        }

        $template = $twig->load("delPost.html.twig");
        echo $template->render(array("breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "pass" => $reponse,
            "categories" => $cat));
    }

    public function modifyGet($twig, $menu, $chemin, $id){
        $this->annonce = Annonce::find($id);
        if(!isset($this->annonce)){
            echo "404";
            return;
        }
        $template = $twig->load("modifyGet.html.twig");
        echo $template->render(array("breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce));
    }

    public function modifyPost($twig, $menu, $chemin, $n, $cat, $dpt){
        $this->annonce = Annonce::find($n);
        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->categItem = Categorie::find($this->annonce->id_categorie)->nom_categorie;
        $this->dptItem = Departement::find($this->annonce->id_departement)->nom_departement;

        $reponse = false;
        if(password_verify($_POST["pass"],$this->annonce->mdp)){
            $reponse = true;

        }

        $template = $twig->load("modifyPost.html.twig");
        echo $template->render(array("breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "annonceur" => $this->annonceur,
            "pass" => $reponse,
            "categories" => $cat,
            "departements" => $dpt,
            "dptItem" => $this->dptItem,
            "categItem" => $this->categItem));
    }

    public function edit($twig, $menu, $chemin, $allPostVars, $id){

        date_default_timezone_set('Europe/Paris');

        $nom = trim($allPostVars['nom'] ?? '');
        $email = trim($allPostVars['email'] ?? '');
        $phone = trim($allPostVars['phone'] ?? '');
        $ville = trim($allPostVars['ville'] ?? '');
        $departement = trim($allPostVars['departement'] ?? '');
        $categorie = trim($allPostVars['categorie'] ?? '');
        $title = trim($allPostVars['title'] ?? '');
        $description = trim($allPostVars['description'] ?? '');
        $price = trim($allPostVars['price'] ?? '');


        $errors = array_filter([
            'nameAdvertiser' => empty($nom) ? 'Veuillez entrer votre nom' : '',
            'emailAdvertiser' => (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) ? '' : 'Veuillez entrer une adresse mail correcte',
            'phoneAdvertiser' => (empty($phone) || !is_numeric($phone)) ? 'Veuillez entrer votre numéro de téléphone' : '',
            'villeAdvertiser' => empty($ville) ? 'Veuillez entrer votre ville' : '',
            'departmentAdvertiser' => !is_numeric($departement) ? 'Veuillez choisir un département' : '',
            'categorieAdvertiser' => !is_numeric($categorie) ? 'Veuillez choisir une catégorie' : '',
            'titleAdvertiser' => empty($title) ? 'Veuillez entrer un titre' : '',
            'descriptionAdvertiser' => empty($description) ? 'Veuillez entrer une description' : '',
            'priceAdvertiser' => (empty($price) || !is_numeric($price)) ? 'Veuillez entrer un prix' : ''
        ]);

        if (!empty($errors)) {

            $template = $twig->load("add-error.html.twig");
            echo $template->render(array(
                    "breadcrumb" => $menu,
                    "chemin" => $chemin,
                    "errors" => array_values($errors))
            );
            return;
        }

        $this->annonce = Annonce::find($id);
        $idannonceur = $this->annonce->id_annonceur;
        $this->annonceur = Annonceur::find($idannonceur);


        $this->annonceur->email = htmlentities($allPostVars['email']);
        $this->annonceur->nom_annonceur = htmlentities($allPostVars['nom']);
        $this->annonceur->telephone = htmlentities($allPostVars['phone']);
        $this->annonce->ville = htmlentities($allPostVars['ville']);
        $this->annonce->id_departement = $allPostVars['departement'];
        $this->annonce->prix = htmlentities($allPostVars['price']);
        $this->annonce->mdp = password_hash ($allPostVars['psw'], PASSWORD_DEFAULT);
        $this->annonce->titre = htmlentities($allPostVars['title']);
        $this->annonce->description = htmlentities($allPostVars['description']);
        $this->annonce->id_categorie = $allPostVars['categorie'];
        $this->annonce->date = date('Y-m-d');
        $this->annonceur->save();
        $this->annonceur->annonce()->save($this->annonce);


        $template = $twig->load("modif-confirm.html.twig");
        echo $template->render(array("breadcrumb" => $menu, "chemin" => $chemin));
    }
}

