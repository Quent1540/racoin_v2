<?php

namespace App\Controller;

use App\Model\Annonce;
use App\Model\Annonceur;

class AddController
{

    public function addItemView($twig, $menu, $chemin, $cat, $dpt): void
    {
        $template = $twig->load("add.html.twig");
        echo $template->render([
                "breadcrumb"   => $menu,
                "chemin"       => $chemin,
                "categories"   => $cat,
                "departements" => $dpt
            ]
        );

    }

    private function isEmail(string $email): int|false
    {
        return preg_match("/^[-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)$|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i", $email);
    }

    public function addNewItem($twig, $menu, $chemin, array $allPostVars): void
    {
        date_default_timezone_set('Europe/Paris');

        $nom              = trim((string) ($allPostVars['nom'] ?? ''));
        $email            = trim((string) ($allPostVars['email'] ?? ''));
        $phone            = trim((string) ($allPostVars['phone'] ?? ''));
        $ville            = trim((string) ($allPostVars['ville'] ?? ''));
        $departement      = trim((string) ($allPostVars['departement'] ?? ''));
        $categorie        = trim((string) ($allPostVars['categorie'] ?? ''));
        $title            = trim((string) ($allPostVars['title'] ?? ''));
        $description      = trim((string) ($allPostVars['description'] ?? ''));
        $price            = trim((string) ($allPostVars['price'] ?? ''));
        $password         = trim((string) ($allPostVars['psw'] ?? ''));
        $password_confirm = trim((string) ($allPostVars['confirm-psw'] ?? ''));

        $errors = [];
        if ($nom === '' || $nom === '0') {
            $errors[] = 'Veuillez entrer votre nom';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez entrer une adresse mail correcte';
        }
        if ($phone === '' || !is_numeric($phone)) {
            $errors[] = 'Veuillez entrer votre numéro de téléphone';
        }
        if ($ville === '' || $ville === '0') {
            $errors[] = 'Veuillez entrer votre ville';
        }
        if (!is_numeric($departement)) {
            $errors[] = 'Veuillez choisir un département';
        }
        if (!is_numeric($categorie)) {
            $errors[] = 'Veuillez choisir une catégorie';
        }
        if ($title === '' || $title === '0') {
            $errors[] = 'Veuillez entrer un titre';
        }
        if ($description === '' || $description === '0') {
            $errors[] = 'Veuillez entrer une description';
        }
        if ($price === '' || !is_numeric($price)) {
            $errors[] = 'Veuillez entrer un prix';
        }
        if ($password === '' || $password_confirm === '' || $password !== $password_confirm) {
            $errors[] = 'Les mots de passes ne sont pas identiques';
        }

        if ($errors !== []) {
            $template = $twig->load("add-error.html.twig");
            echo $template->render(['breadcrumb' => $menu, 'chemin' => $chemin, 'errors' => $errors]);
            return;
        }

        $annonce   = new Annonce();
        $annonceur = new Annonceur();

        $annonceur->email         = htmlentities($email);
        $annonceur->nom_annonceur = htmlentities($nom);
        $annonceur->telephone     = htmlentities($phone);

        $annonce->ville          = htmlentities($ville);
        $annonce->id_departement = (int) $departement;
        $annonce->prix           = (float) $price;
        $annonce->titre          = htmlentities($title);
        $annonce->description    = htmlentities($description);
        $annonce->id_categorie   = (int) $categorie;
        $annonce->date           = date('Y-m-d');

        if ($password !== '') {
            $annonce->mdp = password_hash($password, PASSWORD_DEFAULT);
        }

        $annonceur->save();
        $annonceur->annonce()->save($annonce);

        $template = $twig->load("add-confirm.html.twig");
        echo $template->render(['breadcrumb' => $menu, 'chemin' => $chemin]);
    }
}

