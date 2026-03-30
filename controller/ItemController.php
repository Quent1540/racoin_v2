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
    public function __construct(){ }

    private function buildBreadcrumb(string $chemin, array $extra = []): array {
        $base = [['href' => $chemin, 'text' => 'Acceuil']];
        return array_merge($base, $extra);
    }

    private function renderTwig($twig, string $template, array $context = []): void {
        echo $twig->load($template)->render($context);
    }

    public function afficherItem($twig, $menu, string $chemin, string $id, $categories): void
    {
        $this->annonce = Annonce::find($id);
        if (!property_exists($this, 'annonce') || $this->annonce === null) {
            echo "404";
            return;
        }

        $breadcrumb = $this->buildBreadcrumb($chemin, [
            ['href' => $chemin . '/cat/' . $id, 'text' => Categorie::find($this->annonce->id_categorie)?->nom_categorie],
            ['href' => $chemin . '/item/' . $id, 'text' => $this->annonce->titre]
        ]);

        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->departement = Departement::find($this->annonce->id_departement);
        $this->photo = Photo::where('id_annonce', '=', $id)->get();

        $this->renderTwig($twig, 'item.html.twig', [
            'breadcrumb' => $breadcrumb,
            'chemin' => $chemin,
            'annonce' => $this->annonce,
            'annonceur' => $this->annonceur,
            'dep' => $this->departement?->nom_departement,
            'photo' => $this->photo,
            'categories' => $categories
        ]);
    }

    public function supprimerItemGet($twig, $menu, $chemin, string $id): void {
        $this->annonce = Annonce::find($id);
        if (!property_exists($this, 'annonce') || $this->annonce === null) {
            echo "404";
            return;
        }

        $this->renderTwig($twig, 'delGet.html.twig', [
            'breadcrumb' => $menu,
            'chemin' => $chemin,
            'annonce' => $this->annonce
        ]);
    }

    public function supprimerItemPost($twig, $menu, $chemin, string $id, $categories): void {
        $this->annonce = Annonce::find($id);
        $password = $_POST['pass'] ?? null;
        $deleted = false;

        if ($password !== null && isset($this->annonce) && password_verify((string)$password, (string)$this->annonce->mdp)) {
            $deleted = true;
            Photo::where('id_annonce', '=', $id)->delete();
            $this->annonce->delete();
        }

        $this->renderTwig($twig, 'delPost.html.twig', [
            'breadcrumb' => $menu,
            'chemin' => $chemin,
            'annonce' => $this->annonce,
            'pass' => $deleted,
            'categories' => $categories
        ]);
    }

    public function modifyGet($twig, $menu, $chemin, $id): void {
        $this->annonce = Annonce::find($id);
        if (!property_exists($this, 'annonce') || $this->annonce === null) {
            echo "404";
            return;
        }

        $this->renderTwig($twig, 'modifyGet.html.twig', [
            'breadcrumb' => $menu,
            'chemin' => $chemin,
            'annonce' => $this->annonce
        ]);
    }

    public function modifyPost($twig, $menu, $chemin, $id, $categories, $departments): void {
        $this->annonce = Annonce::find($id);
        $this->annonceur = Annonceur::find($this->annonce->id_annonceur);
        $this->categItem = Categorie::find($this->annonce->id_categorie)?->nom_categorie;
        $this->dptItem = Departement::find($this->annonce->id_departement)?->nom_departement;

        $password = $_POST['pass'] ?? null;
        $verified = $password !== null && password_verify((string)$password, (string)$this->annonce->mdp);

        $this->renderTwig($twig, 'modifyPost.html.twig', [
            'breadcrumb' => $menu,
            'chemin' => $chemin,
            'annonce' => $this->annonce,
            'annonceur' => $this->annonceur,
            'pass' => (bool)$verified,
            'categories' => $categories,
            'departements' => $departments,
            'dptItem' => $this->dptItem,
            'categItem' => $this->categItem
        ]);
    }

    public function edit($twig, $menu, $chemin, array $formData, $id): void {
        date_default_timezone_set('Europe/Paris');

        $nom = trim((string) ($formData['nom'] ?? ''));
        $email = trim((string) ($formData['email'] ?? ''));
        $phone = trim((string) ($formData['phone'] ?? ''));
        $ville = trim((string) ($formData['ville'] ?? ''));
        $departement = trim((string) ($formData['departement'] ?? ''));
        $categorie = trim((string) ($formData['categorie'] ?? ''));
        $title = trim((string) ($formData['title'] ?? ''));
        $description = trim((string) ($formData['description'] ?? ''));
        $price = trim((string) ($formData['price'] ?? ''));
        $password = trim((string) ($formData['psw'] ?? ''));

        $errors = [];
        if ($nom === '' || $nom === '0') { $errors[] = 'Veuillez entrer votre nom'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Veuillez entrer une adresse mail correcte'; }
        if (($phone === '' || $phone === '0') || !is_numeric($phone)) { $errors[] = 'Veuillez entrer votre numéro de téléphone'; }
        if ($ville === '' || $ville === '0') { $errors[] = 'Veuillez entrer votre ville'; }
        if (!is_numeric($departement)) { $errors[] = 'Veuillez choisir un département'; }
        if (!is_numeric($categorie)) { $errors[] = 'Veuillez choisir une catégorie'; }
        if ($title === '' || $title === '0') { $errors[] = 'Veuillez entrer un titre'; }
        if ($description === '' || $description === '0') { $errors[] = 'Veuillez entrer une description'; }
        if ($price === '' || !is_numeric($price)) { $errors[] = 'Veuillez entrer un prix'; }

        if ($errors !== []) {
            $this->renderTwig($twig, 'add-error.html.twig', ['breadcrumb' => $menu, 'chemin' => $chemin, 'errors' => $errors]);
            return;
        }

        $this->annonce = Annonce::find($id);
        $idAnnonceur = $this->annonce->id_annonceur;
        $this->annonceur = Annonceur::find($idAnnonceur);

        $this->annonceur->email = htmlentities($email);
        $this->annonceur->nom_annonceur = htmlentities($nom);
        $this->annonceur->telephone = htmlentities($phone);

        $this->annonce->ville = htmlentities($ville);
        $this->annonce->id_departement = (int) $departement;
        if ($password !== '') {
            $this->annonce->mdp = password_hash($password, PASSWORD_DEFAULT);
        }
        $this->annonce->prix = (float) $price;
        $this->annonce->titre = htmlentities($title);
        $this->annonce->description = htmlentities($description);
        $this->annonce->id_categorie = (int) $categorie;
        $this->annonce->date = date('Y-m-d');

        $this->annonceur->save();
        $this->annonceur->annonce()->save($this->annonce);

        $this->renderTwig($twig, 'modif-confirm.html.twig', ['breadcrumb' => $menu, 'chemin' => $chemin]);
    }
}

