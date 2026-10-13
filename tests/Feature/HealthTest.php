<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class HealthTest extends TestCase
{
    public function test_health_returns_ok_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()->assertExactJson(['status' => 'ok']);
    }
}
