<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test the health endpoint returns healthy status.
     */
    public function test_health_endpoint_returns_healthy(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'backend',
            ])
            ->assertJsonStructure([
                'status',
                'service',
                'timestamp',
                'checks' => [
                    'database',
                    'redis',
                    'storage',
                ],
            ]);
    }

    /**
     * Test health endpoint includes database check.
     */
    public function test_health_endpoint_checks_database(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('checks.database.status', 'ok');
    }
}
