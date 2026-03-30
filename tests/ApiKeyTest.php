<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use model\ApiKey;

class ApiKeyTest extends TestCase {
    public function testApiKeyTableGood() {
        $apiKey = new ApiKey();

        $this->assertEquals('apikey', $apiKey->getTable());
        $this->assertEquals('id_key', $apiKey->getKeyName());
        $this->assertFalse($apiKey->timestamps);
    }
}