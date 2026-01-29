<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use Tests\TestCase;

class MosquesApiTest extends ApiTestCase
{
    /**
     * Test GET /api/mosques - Returns paginated list with correct structure
     */
    public function test_get_mosques_returns_paginated_list(): void
    {
        Mosque::create([
            'name' => 'Masjid Al-Haram Area 1',
            'location' => 'Makkah, Saudi Arabia',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1500,
            'current_water_level' => 2000,
            'required_water_level' => 10000,
            'need_level' => 'High',
            'need_score' => 95,
            'description' => 'One of the most important mosques',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/mosques');

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
                        'name',
                        'location',
                        'latitude',
                        'longitude',
                        'capacity',
                        'current_water_level',
                        'required_water_level',
                        'need_level',
                        'need_score',
                        'description',
                        'mosque_admin',
                    ],
                ],
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ]);
    }

    /**
     * Test GET /api/mosques - Query parameters work correctly
     */
    public function test_get_mosques_with_query_parameters(): void
    {
        Mosque::create([
            'name' => 'Masjid Al-Haram Area 1',
            'location' => 'Makkah',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1500,
            'required_water_level' => 10000,
            'need_level' => 'High',
            'need_score' => 95,
            'is_active' => true,
        ]);

        Mosque::create([
            'name' => 'Masjid Al-Haram Area 2',
            'location' => 'Makkah',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 2000,
            'required_water_level' => 12000,
            'need_level' => 'Low',
            'need_score' => 30,
            'is_active' => true,
        ]);

        // Test search parameter
        $response = $this->getJson('/api/mosques?search=Area 1');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));

        // Test need_level filter
        $response = $this->getJson('/api/mosques?need_level=High');
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $mosque) {
            $this->assertEquals('High', $mosque['need_level']);
        }

        // Test min_need_score filter
        $response = $this->getJson('/api/mosques?min_need_score=50');
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $mosque) {
            $this->assertGreaterThanOrEqual(50, $mosque['need_score']);
        }

        // Test pagination
        $response = $this->getJson('/api/mosques?page=1&per_page=1');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals(1, $response->json('data.current_page'));

        // Test sort_by and sort_order
        $response = $this->getJson('/api/mosques?sort_by=need_score&sort_order=desc');
        $response->assertStatus(200);
        $mosques = $response->json('data.data');
        if (count($mosques) > 1) {
            $this->assertGreaterThanOrEqual($mosques[1]['need_score'], $mosques[0]['need_score']);
        }
    }

    /**
     * Test GET /api/mosques/{id} - Returns single mosque with correct structure
     */
    public function test_get_mosque_by_id_returns_correct_structure(): void
    {
        $mosque = Mosque::create([
            'name' => 'Masjid Al-Haram Area 1',
            'location' => 'Makkah, Saudi Arabia',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1500,
            'current_water_level' => 2000,
            'required_water_level' => 10000,
            'need_level' => 'High',
            'need_score' => 95,
            'description' => 'One of the most important mosques',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/mosques/{$mosque->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $mosque->id,
                'name' => 'Masjid Al-Haram Area 1',
                'location' => 'Makkah, Saudi Arabia',
                'latitude' => '21.4225',
                'longitude' => '39.8262',
                'capacity' => 1500,
                'current_water_level' => 2000,
                'required_water_level' => 10000,
                'need_level' => 'High',
                'need_score' => 95,
                'description' => 'One of the most important mosques',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'location',
                'latitude',
                'longitude',
                'capacity',
                'current_water_level',
                'required_water_level',
                'need_level',
                'need_score',
                'description',
                'mosque_admin',
                'recent_donations',
                'recent_deliveries',
            ],
        ]);
    }

    /**
     * Test GET /api/mosques/{id} - Returns 404 for non-existent mosque
     */
    public function test_get_mosque_by_id_returns_404_for_non_existent(): void
    {
        $response = $this->getJson('/api/mosques/99999');
        $response->assertStatus(404);
    }

    /**
     * Test POST /api/mosques - Creates mosque with correct structure (Admin only)
     */
    public function test_post_mosques_creates_mosque_with_correct_structure(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/mosques', $admin, [
            'name' => 'New Mosque',
            'location' => 'Riyadh, Saudi Arabia',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'description' => 'Mosque description',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'location',
                'latitude',
                'longitude',
                'capacity',
                'required_water_level',
                'description',
            ],
        ]);

        $this->assertDatabaseHas('mosques', [
            'name' => 'New Mosque',
            'location' => 'Riyadh, Saudi Arabia',
            'capacity' => 1000,
        ]);
    }

    /**
     * Test POST /api/mosques - Validation errors return 422
     */
    public function test_post_mosques_with_validation_errors_returns_422(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/mosques', $admin, [
            'name' => 'New Mosque',
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test PUT /api/mosques/{id} - Updates mosque correctly (Admin only)
     */
    public function test_put_mosques_updates_mosque_correctly(): void
    {
        $admin = $this->createUser('admin');
        $mosque = Mosque::create([
            'name' => 'Old Name',
            'location' => 'Old Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/mosques/{$mosque->id}", $admin, [
            'name' => 'Updated Mosque',
            'location' => 'Updated Location',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'capacity' => 2000,
            'required_water_level' => 10000,
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('mosques', [
            'id' => $mosque->id,
            'name' => 'Updated Mosque',
            'location' => 'Updated Location',
        ]);
    }

    /**
     * Test DELETE /api/mosques/{id} - Deletes mosque correctly (Admin only)
     */
    public function test_delete_mosques_deletes_mosque_correctly(): void
    {
        $admin = $this->createUser('admin');
        $mosque = Mosque::create([
            'name' => 'Mosque to Delete',
            'location' => 'Location',
            'latitude' => 21.4225,
            'longitude' => 39.8262,
            'capacity' => 1000,
            'required_water_level' => 8000,
            'is_active' => true,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/mosques/{$mosque->id}", $admin);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('mosques', [
            'id' => $mosque->id,
        ]);
    }

    /**
     * Test DELETE /api/mosques/{id} - Returns 404 for non-existent mosque
     */
    public function test_delete_mosques_returns_404_for_non_existent(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('DELETE', '/api/mosques/99999', $admin);

        $response->assertStatus(404);
    }
}

