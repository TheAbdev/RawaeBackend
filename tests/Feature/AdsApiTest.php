<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ad;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test GET /api/ads - Returns paginated list with correct structure
     */
    public function test_get_ads_returns_paginated_list(): void
    {
        Ad::create([
            'title' => 'Test Ad',
            'content' => 'Ad content',
            'position' => 'homepage-top',
            'active' => true,
        ]);

        $response = $this->getJson('/api/ads');

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
                        'content',
                        'position',
                        'image_url',
                        'link_url',
                        'active',
                        'created_at',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test GET /api/ads - Query parameters work correctly
     */
    public function test_get_ads_with_query_parameters(): void
    {
        Ad::create([
            'title' => 'Active Ad',
            'content' => 'Content',
            'position' => 'homepage-top',
            'active' => true,
        ]);

        Ad::create([
            'title' => 'Inactive Ad',
            'content' => 'Content',
            'position' => 'sidebar',
            'active' => false,
        ]);

        // Test position filter
        $response = $this->getJson('/api/ads?position=homepage-top');
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $ad) {
            $this->assertEquals('homepage-top', $ad['position']);
        }

        // Test active filter
        $response = $this->getJson('/api/ads?active=true');
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $ad) {
            $this->assertTrue($ad['active']);
        }
    }

    /**
     * Test GET /api/ads/{id} - Returns single ad with correct structure
     */
    public function test_get_ad_by_id_returns_correct_structure(): void
    {
        $ad = Ad::create([
            'title' => 'Test Ad',
            'content' => 'Ad content',
            'position' => 'homepage-top',
            'active' => true,
        ]);

        $response = $this->getJson("/api/ads/{$ad->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $ad->id,
                'title' => 'Test Ad',
                'content' => 'Ad content',
                'position' => 'homepage-top',
                'active' => true,
            ],
        ]);
    }

    /**
     * Test POST /api/ads - Creates ad with correct structure (Admin only)
     */
    public function test_post_ads_creates_ad_with_correct_structure(): void
    {
        $admin = $this->createUser('admin');
        $file = UploadedFile::fake()->image('ad.jpg', 800, 600);

        $response = $this->authenticatedJson('POST', '/api/ads', $admin, [
            'title' => 'New Ad',
            'content' => 'Ad content',
            'position' => 'homepage-top',
            'image' => $file,
            'link_url' => 'https://example.com',
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
                'content',
                'position',
                'image_url',
                'link_url',
                'active',
            ],
        ]);

        $this->assertDatabaseHas('ads', [
            'title' => 'New Ad',
            'content' => 'Ad content',
            'position' => 'homepage-top',
            'active' => true,
        ]);
    }

    /**
     * Test POST /api/ads - Validates file type and size
     */
    public function test_post_ads_validates_file_constraints(): void
    {
        $admin = $this->createUser('admin');

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->authenticatedJson('POST', '/api/ads', $admin, [
            'title' => 'Ad',
            'content' => 'Content',
            'position' => 'homepage-top',
            'image' => $invalidFile,
        ]);
        $response->assertStatus(422);

        // Test file exceeding 5MB
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(6000);
        $response = $this->authenticatedJson('POST', '/api/ads', $admin, [
            'title' => 'Ad',
            'content' => 'Content',
            'position' => 'homepage-top',
            'image' => $largeFile,
        ]);
        $response->assertStatus(422);
    }

    /**
     * Test PUT /api/ads/{id} - Updates ad correctly (Admin only)
     */
    public function test_put_ads_updates_ad_correctly(): void
    {
        $admin = $this->createUser('admin');
        $ad = Ad::create([
            'title' => 'Old Title',
            'content' => 'Old Content',
            'position' => 'homepage-top',
            'active' => true,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/ads/{$ad->id}", $admin, [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'position' => 'sidebar',
            'active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('ads', [
            'id' => $ad->id,
            'title' => 'Updated Title',
            'active' => false,
        ]);
    }

    /**
     * Test DELETE /api/ads/{id} - Deletes ad correctly (Admin only)
     */
    public function test_delete_ads_deletes_ad_correctly(): void
    {
        $admin = $this->createUser('admin');
        $ad = Ad::create([
            'title' => 'Ad to Delete',
            'content' => 'Content',
            'position' => 'homepage-top',
            'active' => true,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/ads/{$ad->id}", $admin);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ads', [
            'id' => $ad->id,
        ]);
    }
}

