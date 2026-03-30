<?php

use PHPUnit\Framework\TestCase;

class AnnonceurTest extends TestCase
{
    public function test_model_properties_and_relation()
    {
        $ann = new \model\Annonceur();

        $this->assertEquals('annonceur', $ann->getTable());
        $this->assertEquals('id_annonceur', $ann->getKeyName());
        $this->assertFalse($ann->timestamps);

        $this->assertTrue(method_exists($ann, 'annonce'));
    }
}

