<?php

namespace App\Controller;

use AllowDynamicProperties;
use App\Model\Annonce;
use App\Model\Annonceur;
use App\Model\Departement;
use App\Model\Photo;
use App\Model\Categorie;

#[AllowDynamicProperties]
class ItemController {
    public function __construct(){
    }
    public function afficherItem($twig, $menu, string $chemin, string $n, $cat): void
    {

        $this->annonce = Annonce::find($n);
        if(!property_exists($this, 'annonce') || $this->annonce === null){
            echo "404";
            return;
        }

        $menu = [['href' => $chemin, 'text' => 'Acceuil'], ['href' => $chemin . '/cat/' . $n, 'text' => Categorie::find($this->annonce->id_categorie)?->nom_categorie], ['href' => $chemin . '/item/' . $n, 'text' => $this->annonce->titre]];

        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->departement = Departement::find($this->annonce->id_departement );
        $this->photo = Photo::where('id_annonce', '=', $n)->get();
        $template = $twig->load("item.html.twig");
        echo $template->render(["breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "annonceur" => $this->annonceur,
            "dep" => $this->departement->nom_departement,
            "photo" => $this->photo,
            "categories" => $cat]);
    }

    public function supprimerItemGet($twig, $menu, $chemin,string $n): void{
        $this->annonce = Annonce::find($n);
        if(!property_exists($this, 'annonce') || $this->annonce === null){
            echo "404";
            return;
        }
        $template = $twig->load("delGet.html.twig");
        echo $template->render(["breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce]);
    }


    public function supprimerItemPost($twig, $menu, $chemin, string $n, $cat): void{
        $this->annonce = Annonce::find($n);
        $reponse = false;
        $pass = $_POST["pass"] ?? null;
        if($pass !== null && password_verify((string) $pass, (string) $this->annonce->mdp)){
            $reponse = true;
            Photo::where('id_annonce', '=', $n)->delete();
            $this->annonce->delete();

        }

        $template = $twig->load("delPost.html.twig");
        echo $template->render(["breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "pass" => $reponse,
            "categories" => $cat]);
    }

    public function modifyGet($twig, $menu, $chemin, $id): void{
        $this->annonce = Annonce::find($id);
        if(!property_exists($this, 'annonce') || $this->annonce === null){
            echo "404";
            return;
        }
        $template = $twig->load("modifyGet.html.twig");
        echo $template->render(["breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce]);
    }

    public function modifyPost($twig, $menu, $chemin, $n, $cat, $dpt): void{
        $this->annonce = Annonce::find($n);
        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->categItem = Categorie::find($this->annonce->id_categorie)?->nom_categorie;
        $this->dptItem = Departement::find($this->annonce->id_departement)?->nom_departement;

        $reponse = false;
        $pass = $_POST["pass"] ?? null;
        if($pass !== null && password_verify((string) $pass, (string) $this->annonce->mdp)){
            $reponse = true;

        }

        $template = $twig->load("modifyPost.html.twig");
        echo $template->render(["breadcrumb" => $menu,
            "chemin" => $chemin,
            "annonce" => $this->annonce,
            "annonceur" => $this->annonceur,
            "pass" => $reponse,
            "categories" => $cat,
            "departements" => $dpt,
            "dptItem" => $this->dptItem,
            "categItem" => $this->categItem]);
    }

    public function edit($twig, $menu, $chemin, array $allPostVars, $id): void{

        date_default_timezone_set('Europe/Paris');

        $nom = trim((string) ($allPostVars['nom'] ?? ''));
        $email = trim((string) ($allPostVars['email'] ?? ''));
        $phone = trim((string) ($allPostVars['phone'] ?? ''));
        $ville = trim((string) ($allPostVars['ville'] ?? ''));
        $departement = trim((string) ($allPostVars['departement'] ?? ''));
        $categorie = trim((string) ($allPostVars['categorie'] ?? ''));
        $title = trim((string) ($allPostVars['title'] ?? ''));
        $description = trim((string) ($allPostVars['description'] ?? ''));
        $price = trim((string) ($allPostVars['price'] ?? ''));


        $errors = array_filter([
            'nameAdvertiser' => $nom === '' || $nom === '0' ? 'Veuillez entrer votre nom' : '',
            'emailAdvertiser' => (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) ? '' : 'Veuillez entrer une adresse mail correcte',
            'phoneAdvertiser' => (($phone === '' || $phone === '0') || !is_numeric($phone)) ? 'Veuillez entrer votre numéro de téléphone' : '',
            'villeAdvertiser' => $ville === '' || $ville === '0' ? 'Veuillez entrer votre ville' : '',
            'departmentAdvertiser' => !is_numeric($departement) ? 'Veuillez choisir un département' : '',
            'categorieAdvertiser' => !is_numeric($categorie) ? 'Veuillez choisir une catégorie' : '',
            'titleAdvertiser' => $title === '' || $title === '0' ? 'Veuillez entrer un titre' : '',
            'descriptionAdvertiser' => $description === '' || $description === '0' ? 'Veuillez entrer une description' : '',
            'priceAdvertiser' => ($price === '' || $price === '0' || !is_numeric($price)) ? 'Veuillez entrer un prix' : ''
        ]);

        if ($errors !== []) {

            $template = $twig->load("add-error.html.twig");
            echo $template->render([
                    "breadcrumb" => $menu,
                    "chemin" => $chemin,
                    "errors" => array_values($errors)]
            );
            return;
        }

        $this->annonce = Annonce::find($id);
        $idannonceur = $this->annonce->id_annonceur;
        $this->annonceur = Annonceur::find($idannonceur);


        $this->annonceur->email = htmlentities((string) ($allPostVars['email'] ?? ''));
        $this->annonceur->nom_annonceur = htmlentities((string) ($allPostVars['nom'] ?? ''));
        $this->annonceur->telephone = htmlentities((string) ($allPostVars['phone'] ?? ''));
        $this->annonce->ville = htmlentities((string) ($allPostVars['ville'] ?? ''));
        $this->annonce->id_departement = $allPostVars['departement'] ?? null;
        $this->annonce->prix = htmlentities((string) ($allPostVars['price'] ?? ''));
        $this->annonce->mdp = password_hash ((string) ($allPostVars['psw'] ?? ''), PASSWORD_DEFAULT);
        $this->annonce->titre = htmlentities((string) ($allPostVars['title'] ?? ''));
        $this->annonce->description = htmlentities((string) ($allPostVars['description'] ?? ''));
        $this->annonce->id_categorie = $allPostVars['categorie'] ?? null;
        $this->annonce->date = date('Y-m-d');
        $this->annonceur->save();
        $this->annonceur->annonce()->save($this->annonce);


        $template = $twig->load("modif-confirm.html.twig");
        echo $template->render(["breadcrumb" => $menu, "chemin" => $chemin]);
    }
}

