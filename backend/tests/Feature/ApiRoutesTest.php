<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    /**
     * Test API health endpoint exists.
     */
    public function test_api_health_route_exists(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
    }

    /**
     * Test API returns JSON response.
     */
    public function test_api_returns_json(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertHeader('content-type', 'application/json');
    }
}
