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
use App\Models\ContentText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user with a specific role
     */
    protected function createUser(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Get JWT token for a user
     */
    protected function getToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /**
     * Make authenticated request with JWT token
     */
    protected function authenticatedJson(string $method, string $uri, User $user, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        $token = $this->getToken($user);
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $token,
        ], $headers);
        
        // Handle file uploads - use postJson/putJson with multipart
        $hasFiles = false;
        foreach ($data as $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $hasFiles = true;
                break;
            }
        }
        
        if ($hasFiles) {
            // For file uploads, use call() method
            $files = [];
            $parameters = [];
            foreach ($data as $key => $value) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $files[$key] = $value;
                } else {
                    $parameters[$key] = $value;
                }
            }
            return $this->withHeaders($headers)->call($method, $uri, $parameters, [], $files, [], []);
        }
        
        return $this->json($method, $uri, $data, $headers);
    }

    /**
     * Assert response matches specification format
     */
    protected function assertSuccessResponse(\Illuminate\Testing\TestResponse $response, int $statusCode = 200): void
    {
        $response->assertStatus($statusCode);
        $response->assertJsonStructure([
            'success' => true,
        ]);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Assert error response matches specification format
     */
    protected function assertErrorResponse(\Illuminate\Testing\TestResponse $response, int $statusCode): void
    {
        $response->assertStatus($statusCode);
        $response->assertJsonStructure([
            'success' => false,
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Assert pagination structure matches specification
     */
    protected function assertPaginationStructure(\Illuminate\Testing\TestResponse $response): void
    {
        $data = $response->json('data');
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('current_page', $data);
        $this->assertArrayHasKey('per_page', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('last_page', $data);
    }
}

