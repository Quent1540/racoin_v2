<?php

use PHPUnit\Framework\TestCase;

class DepartementTest extends TestCase
{
    public function test_model_properties()
    {
        $d = new \model\Departement();

        $this->assertEquals('departement', $d->getTable());
        $this->assertEquals('id_departement', $d->getKeyName());
        $this->assertFalse($d->timestamps);
    }
}

