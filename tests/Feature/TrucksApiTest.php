<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Truck;
use Tests\TestCase;

class TrucksApiTest extends ApiTestCase
{
    /**
     * Test GET /api/trucks - Returns paginated list with correct structure
     */
    public function test_get_trucks_returns_paginated_list(): void
    {
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
            'current_latitude' => 21.4225,
            'current_longitude' => 39.8262,
        ]);

        $admin = $this->createUser('admin');
        $response = $this->authenticatedJson('GET', '/api/trucks', $admin);

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
                        'truck_id',
                        'name',
                        'capacity',
                        'status',
                        'current_latitude',
                        'current_longitude',
                        'last_location_update',
                        'assigned_driver',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test GET /api/trucks - Query parameters work correctly
     */
    public function test_get_trucks_with_query_parameters(): void
    {
        Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        Truck::create([
            'truck_id' => 'TR-002',
            'name' => 'Truck 002',
            'capacity' => 12000,
            'status' => 'inactive',
        ]);

        $admin = $this->createUser('admin');

        // Test status filter
        $response = $this->authenticatedJson('GET', '/api/trucks?status=active', $admin);
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $truck) {
            $this->assertEquals('active', $truck['status']);
        }
    }

    /**
     * Test GET /api/trucks/{id} - Returns single truck with correct structure
     */
    public function test_get_truck_by_id_returns_correct_structure(): void
    {
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
            'current_latitude' => 21.4225,
            'current_longitude' => 39.8262,
        ]);

        $admin = $this->createUser('admin');
        $response = $this->authenticatedJson('GET', "/api/trucks/{$truck->id}", $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $truck->id,
                'truck_id' => 'TR-001',
                'name' => 'Truck 001',
                'capacity' => 10000,
                'status' => 'active',
            ],
        ]);
    }

    /**
     * Test POST /api/trucks - Creates truck with correct structure (Admin only)
     */
    public function test_post_trucks_creates_truck_with_correct_structure(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/trucks', $admin, [
            'truck_id' => 'TR-010',
            'name' => 'Truck 010',
            'capacity' => 10000,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'truck_id',
                'name',
                'capacity',
                'status',
            ],
        ]);

        $this->assertDatabaseHas('trucks', [
            'truck_id' => 'TR-010',
            'name' => 'Truck 010',
            'capacity' => 10000,
        ]);
    }

    /**
     * Test POST /api/trucks - Validation errors return 422
     */
    public function test_post_trucks_with_validation_errors_returns_422(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/trucks', $admin, [
            'name' => 'Truck 010',
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test PUT /api/trucks/{id} - Updates truck correctly (Admin only)
     */
    public function test_put_trucks_updates_truck_correctly(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Old Name',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/trucks/{$truck->id}", $admin, [
            'truck_id' => 'TR-001',
            'name' => 'Updated Name',
            'capacity' => 12000,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('trucks', [
            'id' => $truck->id,
            'name' => 'Updated Name',
            'capacity' => 12000,
        ]);
    }

    /**
     * Test PUT /api/trucks/{id}/location - Updates truck location (Admin/Logistics Supervisor only)
     */
    public function test_put_trucks_location_updates_location(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/trucks/{$truck->id}/location", $admin, [
            'latitude' => 21.4225,
            'longitude' => 39.8262,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('trucks', [
            'id' => $truck->id,
            'current_latitude' => '21.4225',
            'current_longitude' => '39.8262',
        ]);
    }

    /**
     * Test PUT /api/trucks/{id}/location - Validates latitude and longitude
     */
    public function test_put_trucks_location_validates_coordinates(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/trucks/{$truck->id}/location", $admin, [
            'latitude' => 'invalid',
            'longitude' => 'invalid',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
    }
}

