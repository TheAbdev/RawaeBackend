<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use App\Models\Truck;
use App\Models\Delivery;
use App\Models\NeedRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeliveriesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test GET /api/deliveries - Returns paginated list with correct structure
     */
    public function test_get_deliveries_returns_paginated_list(): void
    {
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $delivery = Delivery::create([
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'delivered',
            'expected_delivery_date' => '2025-01-22',
        ]);

        $response = $this->getJson('/api/deliveries');

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
                        'truck' => [
                            'id',
                            'truck_id',
                            'name',
                        ],
                        'mosque' => [
                            'id',
                            'name',
                        ],
                        'liters_delivered',
                        'proof_image_url',
                        'status',
                        'expected_delivery_date',
                        'actual_delivery_date',
                        'created_at',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test GET /api/deliveries - Query parameters work correctly
     */
    public function test_get_deliveries_with_query_parameters(): void
    {
        $truck1 = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);
        $truck2 = Truck::create([
            'truck_id' => 'TR-002',
            'name' => 'Truck 002',
            'capacity' => 12000,
            'status' => 'active',
        ]);

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

        Delivery::create([
            'truck_id' => $truck1->id,
            'mosque_id' => $mosque1->id,
            'liters_delivered' => 5000,
            'status' => 'delivered',
        ]);

        Delivery::create([
            'truck_id' => $truck2->id,
            'mosque_id' => $mosque2->id,
            'liters_delivered' => 3000,
            'status' => 'pending',
        ]);

        // Test truck_id filter
        $response = $this->getJson("/api/deliveries?truck_id={$truck1->id}");
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $delivery) {
            $this->assertEquals($truck1->id, $delivery['truck']['id']);
        }

        // Test mosque_id filter
        $response = $this->getJson("/api/deliveries?mosque_id={$mosque1->id}");
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $delivery) {
            $this->assertEquals($mosque1->id, $delivery['mosque']['id']);
        }

        // Test status filter
        $response = $this->getJson('/api/deliveries?status=delivered');
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $delivery) {
            $this->assertEquals('delivered', $delivery['status']);
        }
    }

    /**
     * Test GET /api/deliveries/{id} - Returns single delivery with correct structure
     */
    public function test_get_delivery_by_id_returns_correct_structure(): void
    {
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $delivery = Delivery::create([
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'delivered',
        ]);

        $response = $this->getJson("/api/deliveries/{$delivery->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'truck',
                'mosque',
                'liters_delivered',
                'status',
            ],
        ]);
    }

    /**
     * Test POST /api/deliveries - Creates delivery with correct structure (Admin/Logistics Supervisor only)
     */
    public function test_post_deliveries_creates_delivery_with_correct_structure(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $needRequest = NeedRequest::create([
            'mosque_id' => $mosque->id,
            'requested_by' => $this->createUser('mosque_admin')->id,
            'water_quantity' => 10000,
            'status' => 'approved',
        ]);

        $response = $this->authenticatedJson('POST', '/api/deliveries', $admin, [
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'need_request_id' => $needRequest->id,
            'liters_delivered' => 5000,
            'expected_delivery_date' => '2025-01-22',
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
                'mosque_id',
                'liters_delivered',
                'status',
                'created_at',
            ],
        ]);

        $this->assertEquals('pending', $response->json('data.status'));
        $this->assertEquals($truck->id, $response->json('data.truck_id'));
        $this->assertEquals($mosque->id, $response->json('data.mosque_id'));

        $this->assertDatabaseHas('deliveries', [
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'pending',
        ]);
    }

    /**
     * Test PUT /api/deliveries/{id}/status - Updates delivery status
     */
    public function test_put_deliveries_status_updates_status(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $delivery = Delivery::create([
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/deliveries/{$delivery->id}/status", $admin, [
            'status' => 'in-transit',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'status' => 'in-transit',
        ]);
    }

    /**
     * Test PUT /api/deliveries/{id}/status - Validates status enum values
     */
    public function test_put_deliveries_status_validates_status_enum(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $delivery = Delivery::create([
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/deliveries/{$delivery->id}/status", $admin, [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test POST /api/deliveries/{id}/proof - Uploads delivery proof image
     */
    public function test_post_deliveries_proof_uploads_proof_image(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $delivery = Delivery::create([
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'in-transit',
        ]);

        $file = UploadedFile::fake()->image('proof.jpg', 800, 600);

        $response = $this->authenticatedJson('POST', "/api/deliveries/{$delivery->id}/proof", $admin, [
            'image' => $file,
            'delivery_latitude' => 21.4225,
            'delivery_longitude' => 39.8262,
            'notes' => 'Delivery completed',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('data.proof_image_url'));
    }

    /**
     * Test POST /api/deliveries/{id}/proof - Validates file type and size
     */
    public function test_post_deliveries_proof_validates_file_constraints(): void
    {
        $admin = $this->createUser('admin');
        $truck = Truck::create([
            'truck_id' => 'TR-001',
            'name' => 'Truck 001',
            'capacity' => 10000,
            'status' => 'active',
        ]);

        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $delivery = Delivery::create([
            'truck_id' => $truck->id,
            'mosque_id' => $mosque->id,
            'liters_delivered' => 5000,
            'status' => 'in-transit',
        ]);

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->authenticatedJson('POST', "/api/deliveries/{$delivery->id}/proof", $admin, [
            'image' => $invalidFile,
        ]);
        $response->assertStatus(422);

        // Test file exceeding 5MB
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(6000);
        $response = $this->authenticatedJson('POST', "/api/deliveries/{$delivery->id}/proof", $admin, [
            'image' => $largeFile,
        ]);
        $response->assertStatus(422);
    }
}

