<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiSurfaceTest extends TestCase
{
    public function test_unused_api_endpoints_are_not_exposed(): void
    {
        $this->postJson('/api/login', [
            'login' => 'unknown@example.com',
            'password' => 'invalid',
        ])->assertNotFound();

        $this->getJson('/api/courses')->assertNotFound();
        $this->postJson('/api/logout')->assertNotFound();
    }
}
