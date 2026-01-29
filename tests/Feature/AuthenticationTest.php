<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends ApiTestCase
{
    /**
     * Test POST /api/auth/login - Success with username
     */
    public function test_login_with_username_returns_success_response(): void
    {
        $user = $this->createUser('admin', [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'username',
                    'role',
                ],
                'token',
                'default_route',
            ],
        ]);

        $this->assertEquals('admin', $response->json('data.user.role'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test POST /api/auth/login - Success with email
     */
    public function test_login_with_email_returns_success_response(): void
    {
        $user = $this->createUser('donor', [
            'username' => 'donor',
            'email' => 'donor@example.com',
            'name' => 'Donor User',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'donor@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test POST /api/auth/login - Invalid credentials returns 401
     */
    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid username or password',
        ]);
    }

    /**
     * Test POST /api/auth/login - Missing fields returns 422
     */
    public function test_login_with_missing_fields_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test POST /api/auth/login - Inactive user returns 401
     */
    public function test_login_with_inactive_user_returns_401(): void
    {
        $user = $this->createUser('admin', [
            'username' => 'admin',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Account is inactive',
        ]);
    }

    /**
     * Test POST /api/auth/register - Success creates donor account
     */
    public function test_register_creates_donor_account_with_success_response(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+966501234567',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'username',
                    'role',
                ],
                'token',
            ],
        ]);

        $this->assertEquals('donor', $response->json('data.user.role'));
        $this->assertEquals('John Doe', $response->json('data.user.name'));
        $this->assertEquals('john@example.com', $response->json('data.user.email'));
        $this->assertEquals('johndoe', $response->json('data.user.username'));
        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'role' => 'donor',
        ]);
    }

    /**
     * Test POST /api/auth/register - Validation errors return 422
     */
    public function test_register_with_validation_errors_returns_422(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test POST /api/auth/register - Duplicate email returns 422
     */
    public function test_register_with_duplicate_email_returns_422(): void
    {
        $this->createUser('donor', [
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'username' => 'johndoe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('errors', $response->json());
    }

    /**
     * Test POST /api/auth/logout - Success with authenticated user
     */
    public function test_logout_with_authenticated_user_returns_success(): void
    {
        $user = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/auth/logout', $user);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Test POST /api/auth/logout - Unauthenticated returns 401
     */
    public function test_logout_without_authentication_returns_401(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test POST /api/auth/refresh - Success refreshes token
     */
    public function test_refresh_token_returns_new_token(): void
    {
        $user = $this->createUser('admin');

        $response = $this->authenticatedJson('POST', '/api/auth/refresh', $user);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'token',
            ],
        ]);
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test POST /api/auth/refresh - Unauthenticated returns 401
     */
    public function test_refresh_token_without_authentication_returns_401(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    /**
     * Test POST /api/auth/forgot-password - Success sends reset email
     */
    public function test_forgot_password_with_valid_email_returns_success(): void
    {
        $user = $this->createUser('donor', [
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'user@example.com',
        ]);

        // Should return success (even if email doesn't exist for security)
        $response->assertStatus(200);
    }

    /**
     * Test POST /api/auth/reset-password - Success resets password
     */
    public function test_reset_password_with_valid_token_returns_success(): void
    {
        $user = $this->createUser('donor', [
            'email' => 'user@example.com',
        ]);

        // Create password reset token
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Should return success
        $response->assertStatus(200);
    }

    /**
     * Test POST /api/auth/reset-password - Invalid token returns error
     */
    public function test_reset_password_with_invalid_token_returns_error(): void
    {
        $user = $this->createUser('donor', [
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400);
    }
}

