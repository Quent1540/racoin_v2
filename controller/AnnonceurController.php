<?php

namespace App\Controller;

use App\Model\Annonce;
use App\Model\Annonceur;
use App\Model\Photo;

class AnnonceurController {
    public ?Annonceur $annonceur = null;

    public function afficherAnnonceur($twig, $menu, string $chemin, $n, $cat): void {
        $this->annonceur = Annonceur::find($n);
        if(!property_exists($this, 'annonceur') || $this->annonceur === null){
            echo "404";
            return;
        }
        $tmp = Annonce::where('id_annonceur','=',$n)->get();

        $annonces = [];
        foreach ($tmp as $a) {
            $a->nb_photo = Photo::where('id_annonce', '=', $a->id_annonce)->count();
            if($a->nb_photo>0){
                $a->url_photo = Photo::select('url_photo')
                    ->where('id_annonce', '=', $a->id_annonce)
                    ->first()->url_photo;
            }else{
                $a->url_photo = $chemin.'/img/noimg.png';
            }

            $annonces[] = $a;
        }
        $template = $twig->load("annonceur.html.twig");
        echo $template->render(['nom' => $this->annonceur, "chemin" => $chemin, "annonces" => $annonces, "categories" => $cat]);
    }
}

