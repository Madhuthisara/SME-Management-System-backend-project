<?php

namespace App\Http\Controllers;

use App\Services\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    public function __construct(
        protected CustomerAuthService $authService
    ) {}

    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:customers,email',
                'mobile_number' => 'required|string|max:20',
                'password' => 'required|string|min:8',
                'address' => 'required|string',
            ]);

            $result = $this->authService->register($validatedData);

            return $this->successResponse($result, 'Customer registered successfully!', 201);
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

            $result = $this->authService->login($credentials);

            if (!$result) {
                return $this->errorResponse('Invalid credentials.', 401);
            }

            return $this->successResponse($result, 'Login successful!');
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed.', 401, [], $e);
        }
    }

    public function profileContent(Request $request): JsonResponse
    {
        try {
            // Guard already checked by middleware
            return $this->successResponse($request->user(), 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve profile', 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'mobile_number' => 'sometimes|string|max:20',
                'address' => 'sometimes|string',
                'password' => 'sometimes|string|min:8|confirmed',
            ]);

            $customer = $this->authService->updateProfile($request->user(), $validatedData);

            return $this->successResponse($customer, 'Profile updated successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse('Profile update failed', 500, [], $e);
        }
    }
}
