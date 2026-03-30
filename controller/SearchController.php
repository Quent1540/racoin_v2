<?php

namespace App\Controller;

use App\Model\Annonce;
use App\Model\Categorie;

class SearchController {

    private function buildBreadcrumb(string $chemin, string $label = 'Recherche'): array {
        return [['href' => $chemin, 'text' => 'Acceuil'], ['href' => $chemin . '/search', 'text' => $label]];
    }

    private function renderTwig($twig, string $template, array $context = []): void {
        echo $twig->load($template)->render($context);
    }

    public function show($twig, $menu, string $chemin, $categories): void {
        $template = $twig->load("search.html.twig");
        $breadcrumb = $this->buildBreadcrumb($chemin, 'Recherche');
        $this->renderTwig($twig, 'search.html.twig', ['breadcrumb' => $breadcrumb, 'chemin' => $chemin, 'categories' => $categories]);
    }

    public function research(array $criteria, $twig, $menu, string $chemin, $categories): void {
        $template = $twig->load("index.html.twig");
        $breadcrumb = $this->buildBreadcrumb($chemin, 'Résultats de la recherche');

        // normalize incoming criteria
        $motClef    = trim((string) ($criteria['motclef'] ?? ''));
        $codePostal = trim((string) ($criteria['codepostal'] ?? ''));
        $categorie  = trim((string) ($criteria['categorie'] ?? ''));
        $prixMin    = trim((string) ($criteria['prix-min'] ?? ''));
        $prixMax    = trim((string) ($criteria['prix-max'] ?? ''));

        $query = Annonce::query();

        if ($motClef === '' && $codePostal === '' && ($categorie === 'Toutes catégories' || $categorie === '-----') && $prixMin === 'Min' && ($prixMax === 'Max' || $prixMax === 'nolimit')) {
            $result = Annonce::all();
            $this->renderTwig($twig, 'index.html.twig', ['breadcrumb' => $breadcrumb, 'chemin' => $chemin, 'annonces' => $result, 'categories' => $categories]);
            return;
        }

        if ($motClef !== '') {
            $query->where(function($q) use ($motClef) {
                $q->where('description', 'like', '%' . $motClef . '%')
                  ->orWhere('titre', 'like', '%' . $motClef . '%');
            });
        }

        if ($codePostal !== '') {
            $query->where('ville', '=', $codePostal);
        }

        if ($categorie !== 'Toutes catégories' && $categorie !== '-----' && is_numeric($categorie)) {
            $query->where('id_categorie', '=', (int)$categorie);
        }

        if ($prixMin !== 'Min' && is_numeric($prixMin)) {
            $query->where('prix', '>=', (float)$prixMin);
        }

        if ($prixMax !== 'Max' && $prixMax !== 'nolimit' && is_numeric($prixMax)) {
            $query->where('prix', '<=', (float)$prixMax);
        }

        $result = $query->get();
        $this->renderTwig($twig, 'index.html.twig', ['breadcrumb' => $breadcrumb, 'chemin' => $chemin, 'annonces' => $result, 'categories' => $categories]);
    }
}
