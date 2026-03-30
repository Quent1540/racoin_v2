<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use App\Model\Departement;

class DepartementTest extends TestCase
{
    public function test_model_properties()
    {
        $d = new Departement();

        $this->assertEquals('departement', $d->getTable());
        $this->assertEquals('id_departement', $d->getKeyName());
        $this->assertFalse($d->timestamps);
    }
}
