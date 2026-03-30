<?php

use PHPUnit\Framework\TestCase;

class AnnonceTest extends TestCase
{
    public function test_model_relations_and_properties()
    {
        $a = new \model\Annonce();

        $this->assertEquals('annonce', $a->getTable());
        $this->assertEquals('id_annonce', $a->getKeyName());
        $this->assertFalse($a->timestamps);

        $this->assertTrue(method_exists($a, 'annonceur'));
        $this->assertTrue(method_exists($a, 'photo'));
    }
}

