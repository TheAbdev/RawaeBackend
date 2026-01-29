<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use App\Models\Donation;
use App\Models\Delivery;
use App\Models\Truck;
use App\Models\ActivityLog;
use Tests\TestCase;

class DashboardApiTest extends ApiTestCase
{
    /**
     * Test GET /api/dashboard/stats - Returns admin stats with correct structure
     */
    public function test_get_dashboard_stats_returns_admin_stats(): void
    {
        $admin = $this->createUser('admin');

        // Create test data
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        Donation::create([
            'donor_id' => $this->createUser('donor')->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'completed',
        ]);

        Delivery::create([
            'truck_id' => Truck::create([
                'truck_id' => 'TR-001',
                'name' => 'Truck 001',
                'capacity' => 10000,
                'status' => 'active',
            ])->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'delivered',
        ]);

        $response = $this->authenticatedJson('GET', '/api/dashboard/stats', $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_donations',
                'water_delivered',
                'mosques_needing_supply',
                'active_fleet',
            ],
        ]);
    }

    /**
     * Test GET /api/dashboard/stats - Returns auditor stats with correct structure
     */
    public function test_get_dashboard_stats_returns_auditor_stats(): void
    {
        $auditor = $this->createUser('auditor');

        $response = $this->authenticatedJson('GET', '/api/dashboard/stats', $auditor);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_revenue',
                'verified_donations',
                'pending_verification',
                'compliance_score',
            ],
        ]);
    }

    /**
     * Test GET /api/dashboard/stats - Returns investor stats with correct structure
     */
    public function test_get_dashboard_stats_returns_investor_stats(): void
    {
        $investor = $this->createUser('investor');

        $response = $this->authenticatedJson('GET', '/api/dashboard/stats', $investor);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_impact',
                'mosques_served',
                'water_delivered',
                'roi',
            ],
        ]);
    }

    /**
     * Test GET /api/dashboard/stats - Returns 401 for unauthorized roles
     */
    public function test_get_dashboard_stats_returns_401_for_unauthorized_roles(): void
    {
        $unauthorizedRoles = ['donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/dashboard/stats', $user);
            $response->assertStatus(401);
        }
    }

    /**
     * Test GET /api/dashboard/activities - Returns recent activities with correct structure
     */
    public function test_get_dashboard_activities_returns_activities(): void
    {
        $admin = $this->createUser('admin');

        ActivityLog::create([
            'type' => 'donation',
            'user_id' => $admin->id,
            'message_ar' => 'تبرع جديد بقيمة 1,500 دولار من نور الشمري',
            'message_en' => 'New donation of $1,500 from Noor Al-Shammari',
        ]);

        $response = $this->authenticatedJson('GET', '/api/dashboard/activities', $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'message_ar',
                    'message_en',
                    'created_at',
                ],
            ],
        ]);
    }

    /**
     * Test GET /api/dashboard/activities - Query parameters work correctly
     */
    public function test_get_dashboard_activities_with_query_parameters(): void
    {
        $admin = $this->createUser('admin');

        for ($i = 0; $i < 15; $i++) {
            ActivityLog::create([
                'type' => 'donation',
                'user_id' => $admin->id,
                'message_ar' => "Activity {$i}",
                'message_en' => "Activity {$i}",
            ]);
        }

        // Test limit parameter
        $response = $this->authenticatedJson('GET', '/api/dashboard/activities?limit=10', $admin);
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json('data')));
    }

    /**
     * Test GET /api/dashboard/donation-activity - Returns chart data with correct structure
     */
    public function test_get_dashboard_donation_activity_returns_chart_data(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('GET', '/api/dashboard/donation-activity', $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'labels',
                'values',
            ],
        ]);
    }

    /**
     * Test GET /api/dashboard/donation-activity - Query parameters work correctly
     */
    public function test_get_dashboard_donation_activity_with_query_parameters(): void
    {
        $admin = $this->createUser('admin');

        $periods = ['week', 'month', 'year'];
        foreach ($periods as $period) {
            $response = $this->authenticatedJson('GET', "/api/dashboard/donation-activity?period={$period}", $admin);
            $response->assertStatus(200);
            $this->assertIsArray($response->json('data.labels'));
            $this->assertIsArray($response->json('data.values'));
        }
    }
}

