<?php
namespace tests;

use PHPUnit\Framework\TestCase;
use App\Model\Categorie;

class CategorieTest extends TestCase {
    public function testCategorieTableGood() {
        $categorie = new Categorie();

        $this->assertEquals('categorie', $categorie->getTable());
        $this->assertEquals('id_categorie', $categorie->getKeyName());
        $this->assertFalse($categorie->timestamps);
    }
}