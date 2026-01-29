<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class InvestorImpactApiTest extends ApiTestCase
{
    /**
     * Test GET /api/investor-impact/metrics - Returns metrics with correct structure
     */
    public function test_get_investor_impact_metrics_returns_metrics(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('GET', '/api/investor-impact/metrics', $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'roi',
                'total_impact',
                'mosques_served',
                'total_donations',
                'water_delivered',
            ],
        ]);

        // Verify all fields are numeric
        $data = $response->json('data');
        $this->assertIsNumeric($data['roi']);
        $this->assertIsNumeric($data['total_impact']);
        $this->assertIsNumeric($data['mosques_served']);
        $this->assertIsNumeric($data['total_donations']);
        $this->assertIsNumeric($data['water_delivered']);
    }

    /**
     * Test GET /api/investor-impact/metrics - Returns 403 for unauthorized roles
     */
    public function test_get_investor_impact_metrics_returns_403_for_unauthorized_roles(): void
    {
        $unauthorizedRoles = ['auditor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/investor-impact/metrics', $user);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/investor-impact/metrics - Investor can access
     */
    public function test_get_investor_impact_metrics_allows_investor(): void
    {
        $investor = $this->createUser('investor');

        $response = $this->authenticatedJson('GET', '/api/investor-impact/metrics', $investor);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * Test GET /api/investor-impact/funnel - Returns funnel data with correct structure
     */
    public function test_get_investor_impact_funnel_returns_funnel_data(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('GET', '/api/investor-impact/funnel', $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'stage' => [
                        'en',
                        'ar',
                    ],
                    'value',
                ],
            ],
        ]);

        // Verify funnel stages exist
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));

        // Verify each stage has required structure
        foreach ($data as $stage) {
            $this->assertArrayHasKey('stage', $stage);
            $this->assertArrayHasKey('en', $stage['stage']);
            $this->assertArrayHasKey('ar', $stage['stage']);
            $this->assertArrayHasKey('value', $stage);
            $this->assertIsNumeric($stage['value']);
        }
    }

    /**
     * Test GET /api/investor-impact/funnel - Returns 403 for unauthorized roles
     */
    public function test_get_investor_impact_funnel_returns_403_for_unauthorized_roles(): void
    {
        $unauthorizedRoles = ['auditor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/investor-impact/funnel', $user);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/investor-impact/funnel - Investor can access
     */
    public function test_get_investor_impact_funnel_allows_investor(): void
    {
        $investor = $this->createUser('investor');

        $response = $this->authenticatedJson('GET', '/api/investor-impact/funnel', $investor);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}

