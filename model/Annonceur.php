<?php

namespace App\Model;

class Annonceur extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'annonceur';
    protected $primaryKey = 'id_annonceur';
    public $timestamps = false;

    public function annonce()
    {
        return $this->hasMany('App\\Model\\Annonce', 'id_annonceur');
    }
}
