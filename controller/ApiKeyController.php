<?php

namespace App\Controller;

use App\Model\ApiKey;

class ApiKeyController {

    public function show($twig, $menu, $chemin, $cat) {
        $template = $twig->load("key-generator.html.twig");
        $menu = array(
            array('href' => $chemin,
                'text' => 'Acceuil'),
            array('href' => $chemin."/search",
                'text' => "Recherche")
        );
        echo $template->render(array("breadcrumb" => $menu, "chemin" => $chemin, "categories" => $cat));
    }

    public function generateKey($twig, $menu, $chemin, $cat, $nom) {
        $nospace_nom = str_replace(' ', '', $nom);

        if($nospace_nom === '') {
            $template = $twig->load("key-generator-error.html.twig");
            $menu = array(
                array('href' => $chemin,
                    'text' => 'Acceuil'),
                array('href' => $chemin."/search",
                    'text' => "Recherche")
            );

            echo $template->render(array("breadcrumb" => $menu, "chemin" => $chemin, "categories" => $cat));
        } else {
            $template = $twig->load("key-generator-result.html.twig");
            $menu = array(
                array('href' => $chemin,
                    'text' => 'Acceuil'),
                array('href' => $chemin."/search",
                    'text' => "Recherche")
            );

            // Génère clé unique
            $key = uniqid();
            // Ajouter clé dans la base
            $apikey = new ApiKey();

            $apikey->id_apikey = $key;
            $apikey->name_key = htmlentities($nom);
            $apikey->save();

            echo $template->render(array("breadcrumb" => $menu, "chemin" => $chemin, "categories" => $cat, "key" => $key));
        }

    }

}
