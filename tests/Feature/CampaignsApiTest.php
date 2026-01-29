<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Campaign;
use Tests\TestCase;

class CampaignsApiTest extends ApiTestCase
{
    /**
     * Test GET /api/campaigns - Returns paginated list with correct structure
     */
    public function test_get_campaigns_returns_paginated_list(): void
    {
        Campaign::create([
            'title' => 'Ramadan Water Drive',
            'description' => 'Help provide water during Ramadan',
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-30',
            'active' => true,
        ]);

        $response = $this->getJson('/api/campaigns');

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
                        'title',
                        'description',
                        'start_date',
                        'end_date',
                        'active',
                        'created_at',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test GET /api/campaigns - Query parameters work correctly
     */
    public function test_get_campaigns_with_query_parameters(): void
    {
        Campaign::create([
            'title' => 'Active Campaign',
            'description' => 'Description',
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-30',
            'active' => true,
        ]);

        Campaign::create([
            'title' => 'Inactive Campaign',
            'description' => 'Description',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'active' => false,
        ]);

        // Test active filter
        $response = $this->getJson('/api/campaigns?active=true');
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $campaign) {
            $this->assertTrue($campaign['active']);
        }
    }

    /**
     * Test GET /api/campaigns/{id} - Returns single campaign with correct structure
     */
    public function test_get_campaign_by_id_returns_correct_structure(): void
    {
        $campaign = Campaign::create([
            'title' => 'Ramadan Water Drive',
            'description' => 'Help provide water during Ramadan',
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-30',
            'active' => true,
        ]);

        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $campaign->id,
                'title' => 'Ramadan Water Drive',
                'description' => 'Help provide water during Ramadan',
                'active' => true,
            ],
        ]);
    }

    /**
     * Test POST /api/campaigns - Creates campaign with correct structure (Admin only)
     */
    public function test_post_campaigns_creates_campaign_with_correct_structure(): void
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
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'title',
                'description',
                'start_date',
                'end_date',
                'active',
            ],
        ]);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Ramadan Water Drive',
            'description' => 'Help provide water during Ramadan',
            'active' => true,
        ]);
    }

    /**
     * Test POST /api/campaigns - Validation errors return 422
     */
    public function test_post_campaigns_with_validation_errors_returns_422(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/campaigns', $admin, [
            'title' => 'Campaign',
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test PUT /api/campaigns/{id} - Updates campaign correctly (Admin only)
     */
    public function test_put_campaigns_updates_campaign_correctly(): void
    {
        $admin = $this->createUser('admin');
        $campaign = Campaign::create([
            'title' => 'Old Title',
            'description' => 'Old Description',
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-30',
            'active' => true,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/campaigns/{$campaign->id}", $admin, [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-30',
            'active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'title' => 'Updated Title',
            'active' => false,
        ]);
    }

    /**
     * Test DELETE /api/campaigns/{id} - Deletes campaign correctly (Admin only)
     */
    public function test_delete_campaigns_deletes_campaign_correctly(): void
    {
        $admin = $this->createUser('admin');
        $campaign = Campaign::create([
            'title' => 'Campaign to Delete',
            'description' => 'Description',
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-30',
            'active' => true,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/campaigns/{$campaign->id}", $admin);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id,
        ]);
    }
}

