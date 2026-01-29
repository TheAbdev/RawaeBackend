<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Login with username/email and password.
     *
     * POST /api/auth/login
     *
     * Request:
     * {
     *   "username": "admin",
     *   "password": "admin123"
     * }
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Admin User",
     *       "email": "admin@example.com",
     *       "username": "admin",
     *       "role": "admin"
     *     },
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     *     "default_route": "/dashboard"
     *   }
     * }
     *
     * Response (401):
     * {
     *   "success": false,
     *   "message": "Invalid username or password"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('username', 'password');

        $user = User::where('email', $credentials['username'])
            ->orWhere('username', $credentials['username'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive',
            ], 401);
        }

        try {
            if (!$token = JWTAuth::fromUser($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role,
                ],
                'token' => $token,
                'default_route' => '/dashboard',
            ],
        ], 200);
    }

    /**
     * Register new donor account.
     *
     * POST /api/auth/register
     *
     * Request:
     * {
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "username": "johndoe",
     *   "password": "password123",
     *   "password_confirmation": "password123",
     *   "phone": "+966501234567"
     * }
     *
     * Response (201):
     * {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 10,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "username": "johndoe",
     *       "role": "donor"
     *     },
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
     *   }
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'donor',
            'is_active' => true,
        ]);

        try {
            if (!$token = JWTAuth::fromUser($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Refresh authentication token.
     *
     * POST /api/auth/refresh
     *
     * Headers: Authorization: Bearer {token}
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
     *   }
     * }
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Logout current user.
     *
     * POST /api/auth/logout
     *
     * Headers: Authorization: Bearer {token}
     *
     * Response (200):
     * {
     *   "success": true,
     *   "message": "Logged out successfully"
     * }
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (JWTException $e) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Request password reset.
     *
     * POST /api/auth/forgot-password
     *
     * Request:
     * {
     *   "email": "user@example.com"
     * }
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent.',
        ], 200);
    }

    /**
     * Reset password with token.
     *
     * POST /api/auth/reset-password
     *
     * Request:
     * {
     *   "token": "reset-token",
     *   "email": "user@example.com",
     *   "password": "newpassword123",
     *   "password_confirmation": "newpassword123"
     * }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired reset token.',
        ], 400);
    }


    public function createUser(Request $request): JsonResponse
    {

        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create users.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:admin,donor,mosque_admin,auditor,investor,logistics_supervisor,driver',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
            ],
        ], 201);
    }


    public function updateUser(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update users.',
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $id,
          //  'password' => 'sometimes|nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|required|string|in:admin,donor,mosque_admin,auditor,investor,logistics_supervisor,driver',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('username')) {
            $user->username = $request->username;
        }
        if ($request->has('password') && $request->password) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        if ($request->has('role')) {

            if ($user->id === $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own role.',
                ], 400);
            }
            $user->role = $request->role;
        }
        if ($request->has('is_active')) {

            if ($user->id === $request->user()->id && !$request->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account.',
                ], 400);
            }
            $user->is_active = $request->is_active;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
            ],
        ], 200);
    }

    public function updateUserRole(Request $request, $id): JsonResponse
    {

        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update user roles.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:admin,donor,mosque_admin,auditor,investor,logistics_supervisor,driver',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }


        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own role.',
            ], 400);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
            ],
        ], 200);
    }


    public function getUsers(Request $request): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can view users.',
            ], 403);
        }

        $query = User::query();

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $formattedUsers = collect($users->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $formattedUsers,
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ],
        ], 200);
    }

  
    public function showUser(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can view user details.',
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
                'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
            ],
        ], 200);
    }

    public function toggleUserStatus(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can change user status.',
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "User {$status} successfully",
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
            ],
        ], 200);
    }

    public function deleteUser(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete users.',
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ], 200);
    }
}

