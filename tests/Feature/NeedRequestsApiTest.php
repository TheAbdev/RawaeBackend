<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use App\Models\NeedRequest;
use Tests\TestCase;

class NeedRequestsApiTest extends ApiTestCase
{
    /**
     * Test GET /api/need-requests - Returns paginated list with correct structure
     */
    public function test_get_need_requests_returns_paginated_list(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'mosque_admin_id' => $mosqueAdmin->id,
            'is_active' => true,
        ]);

        NeedRequest::create([
            'mosque_id' => $mosque->id,
            'requested_by' => $mosqueAdmin->id,
            'water_quantity' => 10000,
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('GET', '/api/need-requests', $mosqueAdmin);

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
                        'mosque_id',
                        'water_quantity',
                        'status',
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
     * Test GET /api/need-requests - Query parameters work correctly
     */
    public function test_get_need_requests_with_query_parameters(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque1 = Mosque::create([
            'name' => 'Mosque 1',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'mosque_admin_id' => $mosqueAdmin->id,
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

        NeedRequest::create([
            'mosque_id' => $mosque1->id,
            'requested_by' => $mosqueAdmin->id,
            'water_quantity' => 10000,
            'status' => 'pending',
        ]);

        NeedRequest::create([
            'mosque_id' => $mosque2->id,
            'requested_by' => $mosqueAdmin->id,
            'water_quantity' => 5000,
            'status' => 'approved',
        ]);

        // Test mosque_id filter
        $response = $this->authenticatedJson('GET', "/api/need-requests?mosque_id={$mosque1->id}", $mosqueAdmin);
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $request) {
            $this->assertEquals($mosque1->id, $request['mosque_id']);
        }

        // Test status filter
        $response = $this->authenticatedJson('GET', '/api/need-requests?status=pending', $mosqueAdmin);
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $request) {
            $this->assertEquals('pending', $request['status']);
        }
    }

    /**
     * Test GET /api/need-requests/my-mosque - Returns requests for user's mosque only
     */
    public function test_get_need_requests_my_mosque_returns_own_mosque_requests(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::create([
            'name' => 'My Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'mosque_admin_id' => $mosqueAdmin->id,
            'is_active' => true,
        ]);

        NeedRequest::create([
            'mosque_id' => $mosque->id,
            'requested_by' => $mosqueAdmin->id,
            'water_quantity' => 10000,
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('GET', '/api/need-requests/my-mosque', $mosqueAdmin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        foreach ($response->json('data.data') as $request) {
            $this->assertEquals($mosque->id, $request['mosque_id']);
        }
    }

    /**
     * Test POST /api/need-requests - Creates need request with correct structure (Mosque Admin only)
     */
    public function test_post_need_requests_creates_request_with_correct_structure(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'mosque_admin_id' => $mosqueAdmin->id,
            'is_active' => true,
        ]);

        $response = $this->authenticatedJson('POST', '/api/need-requests', $mosqueAdmin, [
            'mosque_id' => $mosque->id,
            'water_quantity' => 10000,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'mosque_id',
                'water_quantity',
                'status',
                'created_at',
            ],
        ]);

        $this->assertEquals('pending', $response->json('data.status'));
        $this->assertEquals($mosque->id, $response->json('data.mosque_id'));
        $this->assertEquals(10000, $response->json('data.water_quantity'));

        $this->assertDatabaseHas('need_requests', [
            'mosque_id' => $mosque->id,
            'requested_by' => $mosqueAdmin->id,
            'water_quantity' => 10000,
            'status' => 'pending',
        ]);
    }

    /**
     * Test POST /api/need-requests - Validation errors return 422
     */
    public function test_post_need_requests_with_validation_errors_returns_422(): void
    {
        $mosqueAdmin = $this->createUser('mosque_admin');

        $response = $this->authenticatedJson('POST', '/api/need-requests', $mosqueAdmin, [
            'water_quantity' => 10000,
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test PUT /api/need-requests/{id}/approve - Approves need request (Admin only)
     */
    public function test_put_need_requests_approve_approves_request(): void
    {
        $admin = $this->createUser('admin');
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'mosque_admin_id' => $mosqueAdmin->id,
            'is_active' => true,
        ]);

        $needRequest = NeedRequest::create([
            'mosque_id' => $mosque->id,
            'requested_by' => $mosqueAdmin->id,
            'water_quantity' => 10000,
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/need-requests/{$needRequest->id}/approve", $admin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('need_requests', [
            'id' => $needRequest->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    /**
     * Test PUT /api/need-requests/{id}/reject - Rejects need request (Admin only)
     */
    public function test_put_need_requests_reject_rejects_request(): void
    {
        $admin = $this->createUser('admin');
        $mosqueAdmin = $this->createUser('mosque_admin');
        $mosque = Mosque::create([
            'name' => 'Test Mosque',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'mosque_admin_id' => $mosqueAdmin->id,
            'is_active' => true,
        ]);

        $needRequest = NeedRequest::create([
            'mosque_id' => $mosque->id,
            'requested_by' => $mosqueAdmin->id,
            'water_quantity' => 10000,
            'status' => 'pending',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/need-requests/{$needRequest->id}/reject", $admin, [
            'rejection_reason' => 'Insufficient funds',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('need_requests', [
            'id' => $needRequest->id,
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient funds',
        ]);
    }
}

