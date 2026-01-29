<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ContentText;
use Tests\TestCase;

class ContentTextsApiTest extends ApiTestCase
{
    /**
     * Test GET /api/content-texts - Returns all content texts with correct structure
     */
    public function test_get_content_texts_returns_all_texts(): void
    {
        ContentText::create([
            'key' => 'homepage.welcome',
            'value_ar' => 'مرحباً بكم في رواء الحرم',
            'value_en' => 'Welcome to Rawae Al Haram',
        ]);

        ContentText::create([
            'key' => 'homepage.subtitle',
            'value_ar' => 'دعم المساجد بالمياه النظيفة',
            'value_en' => 'Supporting mosques with clean water',
        ]);

        $response = $this->getJson('/api/content-texts');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'homepage.welcome' => [
                    'key',
                    'value_ar',
                    'value_en',
                ],
                'homepage.subtitle' => [
                    'key',
                    'value_ar',
                    'value_en',
                ],
            ],
        ]);

        $this->assertEquals('Welcome to Rawae Al Haram', $response->json('data.homepage.welcome.value_en'));
        $this->assertEquals('مرحباً بكم في رواء الحرم', $response->json('data.homepage.welcome.value_ar'));
    }

    /**
     * Test GET /api/content-texts/{key} - Returns single content text by key
     */
    public function test_get_content_text_by_key_returns_correct_structure(): void
    {
        ContentText::create([
            'key' => 'homepage.welcome',
            'value_ar' => 'مرحباً بكم في رواء الحرم',
            'value_en' => 'Welcome to Rawae Al Haram',
        ]);

        $response = $this->getJson('/api/content-texts/homepage.welcome');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'key' => 'homepage.welcome',
                'value_ar' => 'مرحباً بكم في رواء الحرم',
                'value_en' => 'Welcome to Rawae Al Haram',
            ],
        ]);
    }

    /**
     * Test GET /api/content-texts/{key} - Returns 404 for non-existent key
     */
    public function test_get_content_text_by_key_returns_404_for_non_existent(): void
    {
        $response = $this->getJson('/api/content-texts/non.existent.key');
        $response->assertStatus(404);
    }

    /**
     * Test POST /api/content-texts - Creates or updates content text (Admin only)
     */
    public function test_post_content_texts_creates_or_updates_text(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/content-texts', $admin, [
            'key' => 'homepage.welcome',
            'value_ar' => 'مرحباً بكم في رواء الحرم',
            'value_en' => 'Welcome to Rawae Al Haram',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'key',
                'value_ar',
                'value_en',
            ],
        ]);

        $this->assertDatabaseHas('content_texts', [
            'key' => 'homepage.welcome',
            'value_ar' => 'مرحباً بكم في رواء الحرم',
            'value_en' => 'Welcome to Rawae Al Haram',
        ]);
    }

    /**
     * Test POST /api/content-texts - Validation errors return 422
     */
    public function test_post_content_texts_with_validation_errors_returns_422(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/content-texts', $admin, [
            'key' => 'homepage.welcome',
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test PUT /api/content-texts/{key} - Updates content text (Admin only)
     */
    public function test_put_content_texts_updates_text(): void
    {
        $admin = $this->createUser('admin');
        ContentText::create([
            'key' => 'homepage.welcome',
            'value_ar' => 'Old Arabic',
            'value_en' => 'Old English',
        ]);

        $response = $this->authenticatedJson('PUT', '/api/content-texts/homepage.welcome', $admin, [
            'key' => 'homepage.welcome',
            'value_ar' => 'مرحباً بكم في رواء الحرم',
            'value_en' => 'Welcome to Rawae Al Haram',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('content_texts', [
            'key' => 'homepage.welcome',
            'value_ar' => 'مرحباً بكم في رواء الحرم',
            'value_en' => 'Welcome to Rawae Al Haram',
        ]);
    }

    /**
     * Test DELETE /api/content-texts/{key} - Deletes content text (Admin only)
     */
    public function test_delete_content_texts_deletes_text(): void
    {
        $admin = $this->createUser('admin');
        ContentText::create([
            'key' => 'homepage.welcome',
            'value_ar' => 'مرحباً بكم في رواء الحرم',
            'value_en' => 'Welcome to Rawae Al Haram',
        ]);

        $response = $this->authenticatedJson('DELETE', '/api/content-texts/homepage.welcome', $admin);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('content_texts', [
            'key' => 'homepage.welcome',
        ]);
    }
}

