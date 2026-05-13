<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register a new user and business.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'mobile_number' => 'required|string|max:20',
                'password' => 'required|string|min:8|confirmed',
                'business_name' => 'required|string|max:255',
                'business_email' => 'required|email|unique:businesses,business_email',
                'business_phone' => 'required|string|max:20',
                'business_address' => 'required|string|max:500',
                'br_number' => 'nullable|string|max:50', 
            ]);

            $result = $this->authService->registerUser($validatedData);

            return $this->successResponse([
                'user' => $result['user'],
                'business' => $result['business'],
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ], 'Registration successful!', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed.', 500, [], $e);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $result = $this->authService->loginUser($credentials);

            return $this->successResponse($result, 'Login successful!');

        } catch (\Exception $e) {
            return $this->errorResponse('Login failed.', 401, [], $e);
        }
    }
}