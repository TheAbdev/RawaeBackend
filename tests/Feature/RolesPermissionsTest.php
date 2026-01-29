<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use App\Models\Donation;
use App\Models\NeedRequest;
use App\Models\TankImage;
use App\Models\Truck;
use App\Models\Delivery;
use App\Models\Campaign;
use App\Models\Ad;
use Tests\TestCase;

class RolesPermissionsTest extends ApiTestCase
{
    /**
     * Test GET /api/mosques - All roles can access
     */
    public function test_get_mosques_allows_all_roles(): void
    {
        $roles = ['admin', 'auditor', 'investor', 'donor', 'mosque_admin', 'logistics_supervisor'];

        foreach ($roles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/mosques', $user);
            $response->assertStatus(200);
        }
    }

    /**
     * Test POST /api/mosques - Only admin can create
     */
    public function test_post_mosques_only_allows_admin(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->authenticatedJson('POST', '/api/mosques', $admin, [
            'name' => 'Test Mosque',
            'location' => 'Test Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
        ]);
        $response->assertStatus(201);

        $unauthorizedRoles = ['auditor', 'investor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('POST', '/api/mosques', $user, [
                'name' => 'Test Mosque',
                'location' => 'Test Location',
                'latitude' => 21.4225,
                'longitude' => 39.8262,
                'capacity' => 1000,
                'required_water_level' => 8000,
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/donations - Admin, Auditor, Investor can see all, Donor sees own only
     */
    public function test_get_donations_role_based_access(): void
    {
        $donor1 = $this->createUser('donor', ['id' => 1]);
        $donor2 = $this->createUser('donor', ['id' => 2]);
        $mosque = Mosque::factory()->create();

        Donation::factory()->create(['donor_id' => 1, 'mosque_id' => $mosque->id]);
        Donation::factory()->create(['donor_id' => 2, 'mosque_id' => $mosque->id]);

        // Admin can see all
        $admin = $this->createUser('admin');
        $response = $this->authenticatedJson('GET', '/api/donations', $admin);
        $response->assertStatus(200);

        // Donor can only see own
        $response = $this->authenticatedJson('GET', '/api/donations', $donor1);
        $response->assertStatus(200);
        $donations = $response->json('data.data');
        foreach ($donations as $donation) {
            $this->assertEquals(1, $donation['donor']['id']);
        }
    }

    /**
     * Test POST /api/donations - Only donor can create
     */
    public function test_post_donations_only_allows_donor(): void
    {
        $donor = $this->createUser('donor');
        $mosque = Mosque::factory()->create();

        $response = $this->authenticatedJson('POST', '/api/donations', $donor, [
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'payment_transaction_id' => 'TXN123456',
        ]);
        $response->assertStatus(201);

        $unauthorizedRoles = ['admin', 'auditor', 'investor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('POST', '/api/donations', $user, [
                'mosque_id' => $mosque->id,
                'amount' => 500.00,
                'payment_method' => 'mada',
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test PUT /api/donations/{id}/verify - Only admin and auditor can verify
     */
    public function test_verify_donation_only_allows_admin_and_auditor(): void
    {
        $donor = $this->createUser('donor');
        $mosque = Mosque::factory()->create();
        $donation = Donation::factory()->create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
        ]);

        $admin = $this->createUser('admin');
        $response = $this->authenticatedJson('PUT', "/api/donations/{$donation->id}/verify", $admin, [
            'verified' => true,
        ]);
        $response->assertStatus(200);

        $auditor = $this->createUser('auditor');
        $donation2 = Donation::factory()->create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
        ]);
        $response = $this->authenticatedJson('PUT', "/api/donations/{$donation2->id}/verify", $auditor, [
            'verified' => true,
        ]);
        $response->assertStatus(200);

        $unauthorizedRoles = ['investor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $donation3 = Donation::factory()->create([
                'donor_id' => $donor->id,
                'mosque_id' => $mosque->id,
            ]);
            $response = $this->authenticatedJson('PUT', "/api/donations/{$donation3->id}/verify", $user, [
                'verified' => true,
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/need-requests/my-mosque - Only mosque_admin can access
     */
    public function test_get_need_requests_my_mosque_only_allows_mosque_admin(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::factory()->create(['mosque_admin_id' => $mosqueAdmin->id]);

        $response = $this->authenticatedJson('GET', '/api/need-requests/my-mosque', $mosqueAdmin);
        $response->assertStatus(200);

        $unauthorizedRoles = ['admin', 'auditor', 'investor', 'donor', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/need-requests/my-mosque', $user);
            $response->assertStatus(403);
        }
    }

    /**
     * Test POST /api/need-requests - Only mosque_admin can create
     */
    public function test_post_need_requests_only_allows_mosque_admin(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::factory()->create(['mosque_admin_id' => $mosqueAdmin->id]);

        $response = $this->authenticatedJson('POST', '/api/need-requests', $mosqueAdmin, [
            'mosque_id' => $mosque->id,
            'water_quantity' => 10000,
        ]);
        $response->assertStatus(201);

        $unauthorizedRoles = ['admin', 'auditor', 'investor', 'donor', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('POST', '/api/need-requests', $user, [
                'mosque_id' => $mosque->id,
                'water_quantity' => 10000,
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test POST /api/tank-images - Only mosque_admin can upload
     */
    public function test_post_tank_images_only_allows_mosque_admin(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::factory()->create(['mosque_admin_id' => $mosqueAdmin->id]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('tank.jpg');

        $response = $this->authenticatedJson('POST', '/api/tank-images', $mosqueAdmin, [
            'mosque_id' => $mosque->id,
            'image' => $file,
        ]);
        $response->assertStatus(201);

        $unauthorizedRoles = ['admin', 'auditor', 'investor', 'donor', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $file2 = \Illuminate\Http\UploadedFile::fake()->image('tank.jpg');
            $response = $this->authenticatedJson('POST', '/api/tank-images', $user, [
                'mosque_id' => $mosque->id,
                'image' => $file2,
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/trucks - Admin, Auditor, Logistics Supervisor can access
     */
    public function test_get_trucks_allows_specific_roles(): void
    {
        $allowedRoles = ['admin', 'auditor', 'logistics_supervisor'];
        foreach ($allowedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/trucks', $user);
            $response->assertStatus(200);
        }

        $unauthorizedRoles = ['investor', 'donor', 'mosque_admin'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/trucks', $user);
            $response->assertStatus(403);
        }
    }

    /**
     * Test PUT /api/trucks/{id}/location - Admin and Logistics Supervisor can update
     */
    public function test_update_truck_location_allows_specific_roles(): void
    {
        $truck = Truck::factory()->create();

        $allowedRoles = ['admin', 'logistics_supervisor'];
        foreach ($allowedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('PUT', "/api/trucks/{$truck->id}/location", $user, [
                'latitude' => 21.4225,
                'longitude' => 39.8262,
            ]);
            $response->assertStatus(200);
        }

        $unauthorizedRoles = ['auditor', 'investor', 'donor', 'mosque_admin'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('PUT', "/api/trucks/{$truck->id}/location", $user, [
                'latitude' => 21.4225,
                'longitude' => 39.8262,
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test POST /api/deliveries - Admin and Logistics Supervisor can create
     */
    public function test_post_deliveries_allows_specific_roles(): void
    {
        $truck = Truck::factory()->create();
        $mosque = Mosque::factory()->create();

        $allowedRoles = ['admin', 'logistics_supervisor'];
        foreach ($allowedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('POST', '/api/deliveries', $user, [
                'truck_id' => $truck->id,
                'mosque_id' => $mosque->id,
                'liters_delivered' => 5000,
                'expected_delivery_date' => '2025-01-22',
            ]);
            $response->assertStatus(201);
        }

        $unauthorizedRoles = ['auditor', 'investor', 'donor', 'mosque_admin'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('POST', '/api/deliveries', $user, [
                'truck_id' => $truck->id,
                'mosque_id' => $mosque->id,
                'liters_delivered' => 5000,
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test POST /api/campaigns - Only admin can create
     */
    public function test_post_campaigns_only_allows_admin(): void
    {
        $admin = $this->createUser('admin');
        $response = $this->authenticatedJson('POST', '/api/campaigns', $admin, [
            'title' => 'Ramadan Water Drive',
            'description' => 'Help provide water during Ramadan',
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-30',
            'active' => true,
        ]);
        $response->assertStatus(201);

        $unauthorizedRoles = ['auditor', 'investor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('POST', '/api/campaigns', $user, [
                'title' => 'Test Campaign',
                'start_date' => '2025-03-01',
                'end_date' => '2025-04-30',
            ]);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/reports/* - Only admin and auditor can access
     */
    public function test_reports_endpoints_only_allow_admin_and_auditor(): void
    {
        $allowedRoles = ['admin', 'auditor'];
        foreach ($allowedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/reports/donation-ledger', $user);
            $response->assertStatus(200);
        }

        $unauthorizedRoles = ['investor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/reports/donation-ledger', $user);
            $response->assertStatus(403);
        }
    }

    /**
     * Test GET /api/investor-impact/* - Only admin and investor can access
     */
    public function test_investor_impact_endpoints_only_allow_admin_and_investor(): void
    {
        $allowedRoles = ['admin', 'investor'];
        foreach ($allowedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/investor-impact/metrics', $user);
            $response->assertStatus(200);
        }

        $unauthorizedRoles = ['auditor', 'donor', 'mosque_admin', 'logistics_supervisor'];
        foreach ($unauthorizedRoles as $role) {
            $user = $this->createUser($role);
            $response = $this->authenticatedJson('GET', '/api/investor-impact/metrics', $user);
            $response->assertStatus(403);
        }
    }
}

