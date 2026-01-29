<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use App\Models\Donation;
use Tests\TestCase;

class DonationsApiTest extends ApiTestCase
{
    /**
     * Test GET /api/donations - Returns paginated list with correct structure
     */
    public function test_get_donations_returns_paginated_list(): void
    {
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'payment_transaction_id' => 'TXN123456',
            'status' => 'completed',
            'verified' => true,
        ]);

        $response = $this->getJson('/api/donations');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'donor' => [
                            'id',
                            'name',
                            'email',
                        ],
                        'mosque' => [
                            'id',
                            'name',
                        ],
                        'amount',
                        'payment_method',
                        'payment_transaction_id',
                        'status',
                        'verified',
                        'created_at',
                    ],
                ],
                'current_page',
                'per_page',
                'total',
            ],
        ]);
    }

    /**
     * Test GET /api/donations - Query parameters work correctly
     */
    public function test_get_donations_with_query_parameters(): void
    {
        $donor = $this->createUser('donor');
        $mosque1 = Mosque::create([
            'name' => 'Mosque 1',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);
        $mosque2 = Mosque::create([
            'name' => 'Mosque 2',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque1->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'completed',
        ]);

        Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque2->id,
            'amount' => 300.00,
            'payment_method' => 'stc_pay',
            'status' => 'pending',
        ]);

        // Test donor_id filter
        $response = $this->getJson("/api/donations?donor_id={$donor->id}");
        $response->assertStatus(200);

        // Test mosque_id filter
        $response = $this->getJson("/api/donations?mosque_id={$mosque1->id}");
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $donation) {
            $this->assertEquals($mosque1->id, $donation['mosque']['id']);
        }

        // Test status filter
        $response = $this->getJson('/api/donations?status=completed');
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $donation) {
            $this->assertEquals('completed', $donation['status']);
        }

        // Test date_from and date_to
        $response = $this->getJson('/api/donations?date_from=2025-01-01&date_to=2025-12-31');
        $response->assertStatus(200);
    }

    /**
     * Test GET /api/donations/{id} - Returns single donation with correct structure
     */
    public function test_get_donation_by_id_returns_correct_structure(): void
    {
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'payment_transaction_id' => 'TXN123456',
            'status' => 'completed',
            'verified' => true,
        ]);

        $response = $this->getJson("/api/donations/{$donation->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'donor',
                'mosque',
                'amount',
                'payment_method',
                'status',
            ],
        ]);
    }

    /**
     * Test POST /api/donations - Creates donation with correct structure (Donor only)
     */
    public function test_post_donations_creates_donation_with_correct_structure(): void
    {
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $response = $this->authenticatedJson('POST', '/api/donations', $donor, [
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'payment_transaction_id' => 'TXN123456',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'donor_id',
                'mosque_id',
                'amount',
                'payment_method',
                'status',
                'created_at',
            ],
        ]);

        $this->assertEquals('pending', $response->json('data.status'));
        $this->assertEquals($donor->id, $response->json('data.donor_id'));
        $this->assertEquals($mosque->id, $response->json('data.mosque_id'));

        $this->assertDatabaseHas('donations', [
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'pending',
        ]);
    }

    /**
     * Test POST /api/donations - Validation errors return 422
     */
    public function test_post_donations_with_validation_errors_returns_422(): void
    {
        $donor = $this->createUser('donor');

        $response = $this->authenticatedJson('POST', '/api/donations', $donor, [
            'amount' => 500.00,
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test GET /api/donations/my-history - Returns donor's own donations only
     */
    public function test_get_donations_my_history_returns_own_donations_only(): void
    {
        $donor1 = $this->createUser('donor');
        $donor2 = $this->createUser('donor');
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
            'donor_id' => $donor1->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'completed',
        ]);

        Donation::create([
            'donor_id' => $donor2->id,
            'mosque_id' => $mosque->id,
            'amount' => 300.00,
            'payment_method' => 'stc_pay',
            'status' => 'completed',
        ]);

        $response = $this->authenticatedJson('GET', '/api/donations/my-history', $donor1);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        foreach ($response->json('data.data') as $donation) {
            $this->assertEquals($donor1->id, $donation['donor']['id']);
        }
    }

    /**
     * Test PUT /api/donations/{id}/verify - Verifies donation (Admin/Auditor only)
     */
    public function test_put_donations_verify_verifies_donation(): void
    {
        $admin = $this->createUser('admin');
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'pending',
            'verified' => false,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/donations/{$donation->id}/verify", $admin, [
            'verified' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'verified' => true,
            'verified_by' => $admin->id,
        ]);
    }

    /**
     * Test PUT /api/donations/{id}/status - Updates donation status (Admin only)
     */
    public function test_put_donations_status_updates_status(): void
    {
        $admin = $this->createUser('admin');
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/donations/{$donation->id}/status", $admin, [
            'status' => 'completed',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Test PUT /api/donations/{id}/status - Validates status enum values
     */
    public function test_put_donations_status_validates_status_enum(): void
    {
        $admin = $this->createUser('admin');
        $donor = $this->createUser('donor');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $donation = Donation::create([
            'donor_id' => $donor->id,
            'mosque_id' => $mosque->id,
            'amount' => 500.00,
            'payment_method' => 'mada',
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/donations/{$donation->id}/status", $admin, [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
    }
}

