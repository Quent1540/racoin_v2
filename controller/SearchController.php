<?php

namespace App\Controller;

use App\Model\Annonce;
use App\Model\Categorie;

class SearchController {

    public function show($twig, $menu, string $chemin, $cat): void {
        $template = $twig->load("search.html.twig");
        $menu = [['href' => $chemin, 'text' => 'Acceuil'], ['href' => $chemin . '/search', 'text' => 'Recherche']];
        echo $template->render(['breadcrumb' => $menu, 'chemin' => $chemin, 'categories' => $cat]);
    }

    public function research(array $array, $twig, $menu, string $chemin, $cat): void {
        $template = $twig->load("index.html.twig");
        $menu = [['href' => $chemin, 'text' => 'Acceuil'], ['href' => $chemin . '/search', 'text' => 'Résultats de la recherche']];

        $nospace_mc = str_replace(' ', '', (string) ($array['motclef'] ?? ''));
        $nospace_cp = str_replace(' ', '', (string) ($array['codepostal'] ?? ''));

        $query = Annonce::select();

        $categorieParam = $array['categorie'] ?? 'Toutes catégories';
        $prixMin = $array['prix-min'] ?? 'Min';
        $prixMax = $array['prix-max'] ?? 'Max';

        if ($nospace_mc === '' && $nospace_cp === '' && ($categorieParam === 'Toutes catégories' || $categorieParam === '-----') && $prixMin === 'Min' && ($prixMax === 'Max' || $prixMax === 'nolimit')) {
            $annonce = Annonce::all();
        } else {
            if ($nospace_mc !== '') {
                $query->where('description', 'like', '%' . $array['motclef'] . '%');
            }

            if ($nospace_cp !== '') {
                $query->where('ville', '=', $array['codepostal']);
            }

            if ($categorieParam !== 'Toutes catégories' && $categorieParam !== '-----') {
                $categ = Categorie::select('id_categorie')->where('id_categorie', '=', $categorieParam)->first()?->id_categorie;
                if ($categ !== null) {
                    $query->where('id_categorie', '=', $categ);
                }
            }

            if ($prixMin !== 'Min' && $prixMax !== 'Max') {
                if ($prixMax !== 'nolimit') {
                    $query->whereBetween('prix', [$prixMin, $prixMax]);
                } else {
                    $query->where('prix', '>=', $prixMin);
                }
            } elseif ($prixMax !== 'Max' && $prixMax !== 'nolimit') {
                $query->where('prix', '<=', $prixMax);
            } elseif ($prixMin !== 'Min') {
                $query->where('prix', '>=', $prixMin);
            }

            $annonce = $query->get();
        }

        echo $template->render(['breadcrumb' => $menu, 'chemin' => $chemin, 'annonces' => $annonce, 'categories' => $cat]);

    }

}
