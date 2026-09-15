<?php

namespace Tests\Feature;

use App\Services\ReadinessChecker;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_liveness_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_readiness_reports_success_without_session_or_connection_details(): void
    {
        $this->mock(ReadinessChecker::class)->shouldReceive('ready')->once()->andReturn(true);
        $response = $this->getJson('/health/ready');
        $response->assertOk()->assertExactJson(['application' => 'olxv2', 'status' => 'ok'])
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_readiness_returns_service_unavailable_when_a_dependency_fails(): void
    {
        $this->mock(ReadinessChecker::class)->shouldReceive('ready')->once()->andReturn(false);
        $this->getJson('/health/ready')->assertStatus(503)
            ->assertExactJson(['application' => 'olxv2', 'status' => 'unavailable'])->assertDontSee('password');
    }
}
