<?php

namespace App\Controller;

use App\Model\Annonce;
use App\Model\Annonceur;

class AddController
{

    public function addItemView($twig, $menu, $chemin, $cat, $dpt)
    {
        $template = $twig->load("add.html.twig");
        echo $template->render(array(
                "breadcrumb"   => $menu,
                "chemin"       => $chemin,
                "categories"   => $cat,
                "departements" => $dpt
            )
        );

    }

    private function isEmail($email)
    {
        return (preg_match("/^[-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)$|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i", $email));
    }

    public function addNewItem($twig, $menu, $chemin, $allPostVars)
    {

        date_default_timezone_set('Europe/Paris');

        $nom              = trim($allPostVars['nom'] ?? '');
        $email            = trim($allPostVars['email'] ?? '');
        $phone            = trim($allPostVars['phone'] ?? '');
        $ville            = trim($allPostVars['ville'] ?? '');
        $departement      = trim($allPostVars['departement'] ?? '');
        $categorie        = trim($allPostVars['categorie'] ?? '');
        $title            = trim($allPostVars['title'] ?? '');
        $description      = trim($allPostVars['description'] ?? '');
        $price            = trim($allPostVars['price'] ?? '');
        $password         = trim($allPostVars['psw'] ?? '');
        $password_confirm = trim($allPostVars['confirm-psw'] ?? '');

        $errors = array_filter([
            'nameAdvertiser' => empty($nom) ? 'Veuillez entrer votre nom' : '',
            'emailAdvertiser' => $this->isEmail($email) ? '' : 'Veuillez entrer une adresse mail correcte',
            'phoneAdvertiser' => (empty($phone) || !is_numeric($phone)) ? 'Veuillez entrer votre numéro de téléphone' : '',
            'villeAdvertiser' => empty($ville) ? 'Veuillez entrer votre ville' : '',
            'departmentAdvertiser' => !is_numeric($departement) ? 'Veuillez choisir un département' : '',
            'categorieAdvertiser' => !is_numeric($categorie) ? 'Veuillez choisir une catégorie' : '',
            'titleAdvertiser' => empty($title) ? 'Veuillez entrer un titre' : '',
            'descriptionAdvertiser' => empty($description) ? 'Veuillez entrer une description' : '',
            'priceAdvertiser' => (empty($price) || !is_numeric($price)) ? 'Veuillez entrer un prix' : '',
            'passwordAdvertiser' => (empty($password) || empty($password_confirm) || $password != $password_confirm) ? 'Les mots de passes ne sont pas identiques' : ''
        ]);

        if (!empty($errors)) {
            $template = $twig->load("add-error.html.twig");
            echo $template->render(array(
                    "breadcrumb" => $menu,
                    "chemin"     => $chemin,
                    "errors"     => array_values($errors)
                )
            );
            return;
        }

        $annonce   = new Annonce();
        $annonceur = new Annonceur();

        $annonceur->email         = htmlentities($allPostVars['email']);
        $annonceur->nom_annonceur = htmlentities($allPostVars['nom']);
        $annonceur->telephone     = htmlentities($allPostVars['phone']);

        $annonce->ville          = htmlentities($allPostVars['ville']);
        $annonce->id_departement = $allPostVars['departement'];
        $annonce->prix           = htmlentities($allPostVars['price']);
        $annonce->mdp            = password_hash($allPostVars['psw'], PASSWORD_DEFAULT);
        $annonce->titre          = htmlentities($allPostVars['title']);
        $annonce->description    = htmlentities($allPostVars['description']);
        $annonce->id_categorie   = $allPostVars['categorie'];
        $annonce->date           = date('Y-m-d');

        $annonceur->save();
        $annonceur->annonce()->save($annonce);

        $template = $twig->load("add-confirm.html.twig");
        echo $template->render(array("breadcrumb" => $menu, "chemin" => $chemin));
    }
}

