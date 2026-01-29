<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mosque;
use App\Models\TankImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TankImagesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test GET /api/tank-images - Returns paginated list with correct structure
     */
    public function test_get_tank_images_returns_paginated_list(): void
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

        TankImage::create([
            'mosque_id' => $mosque->id,
            'uploaded_by' => $mosqueAdmin->id,
            'image_path' => 'tanks/test.jpg',
            'image_url' => 'https://storage.example.com/tanks/test.jpg',
            'description' => 'Tank status',
        ]);

        $response = $this->getJson('/api/tank-images');

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
                        'image_url',
                        'description',
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
     * Test GET /api/tank-images - Query parameters work correctly
     */
    public function test_get_tank_images_with_query_parameters(): void
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

        TankImage::create([
            'mosque_id' => $mosque1->id,
            'uploaded_by' => $mosqueAdmin->id,
            'image_path' => 'tanks/test1.jpg',
            'image_url' => 'https://storage.example.com/tanks/test1.jpg',
        ]);

        TankImage::create([
            'mosque_id' => $mosque2->id,
            'uploaded_by' => $mosqueAdmin->id,
            'image_path' => 'tanks/test2.jpg',
            'image_url' => 'https://storage.example.com/tanks/test2.jpg',
        ]);

        // Test mosque_id filter
        $response = $this->getJson("/api/tank-images?mosque_id={$mosque1->id}");
        $response->assertStatus(200);
        foreach ($response->json('data.data') as $image) {
            $this->assertEquals($mosque1->id, $image['mosque_id']);
        }
    }

    /**
     * Test GET /api/tank-images/my-mosque - Returns images for user's mosque only
     */
    public function test_get_tank_images_my_mosque_returns_own_mosque_images(): void
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

        TankImage::create([
            'mosque_id' => $mosque->id,
            'uploaded_by' => $mosqueAdmin->id,
            'image_path' => 'tanks/test.jpg',
            'image_url' => 'https://storage.example.com/tanks/test.jpg',
        ]);

        $response = $this->authenticatedJson('GET', '/api/tank-images/my-mosque', $mosqueAdmin);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        foreach ($response->json('data.data') as $image) {
            $this->assertEquals($mosque->id, $image['mosque_id']);
        }
    }

    /**
     * Test POST /api/tank-images - Uploads tank image with correct structure (Mosque Admin only)
     */
    public function test_post_tank_images_uploads_image_with_correct_structure(): void
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

        $file = UploadedFile::fake()->image('tank.jpg', 800, 600);

        $response = $this->authenticatedJson('POST', '/api/tank-images', $mosqueAdmin, [
            'mosque_id' => $mosque->id,
            'image' => $file,
            'description' => 'Tank status',
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
                'image_url',
                'description',
                'created_at',
            ],
        ]);

        $this->assertNotEmpty($response->json('data.image_url'));
        $this->assertEquals($mosque->id, $response->json('data.mosque_id'));
    }

    /**
     * Test POST /api/tank-images - Validates file type (jpg, jpeg, png, gif, webp)
     */
    public function test_post_tank_images_validates_file_type(): void
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

        // Test valid image types
        $validTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        foreach ($validTypes as $ext) {
            $file = UploadedFile::fake()->image("tank.{$ext}");
            $response = $this->authenticatedJson('POST', '/api/tank-images', $mosqueAdmin, [
                'mosque_id' => $mosque->id,
                'image' => $file,
            ]);
            $response->assertStatus(201);
        }

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->authenticatedJson('POST', '/api/tank-images', $mosqueAdmin, [
            'mosque_id' => $mosque->id,
            'image' => $invalidFile,
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test POST /api/tank-images - Validates file size (max 5MB)
     */
    public function test_post_tank_images_validates_file_size(): void
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

        // Test file exceeding 5MB
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(6000); // 6MB
        $response = $this->authenticatedJson('POST', '/api/tank-images', $mosqueAdmin, [
            'mosque_id' => $mosque->id,
            'image' => $largeFile,
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test DELETE /api/tank-images/{id} - Deletes tank image (Mosque Admin only)
     */
    public function test_delete_tank_images_deletes_image(): void
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

        $tankImage = TankImage::create([
            'mosque_id' => $mosque->id,
            'uploaded_by' => $mosqueAdmin->id,
            'image_path' => 'tanks/test.jpg',
            'image_url' => 'https://storage.example.com/tanks/test.jpg',
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/tank-images/{$tankImage->id}", $mosqueAdmin);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tank_images', [
            'id' => $tankImage->id,
        ]);
    }
}

