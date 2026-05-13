<?php

namespace App\Services;

use App\Repositories\BusinessRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected BusinessRepositoryInterface $businessRepo
    ) {}

    public function registerUser(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Create Business
            $business = $this->businessRepo->create([
                'business_name' => $data['business_name'],
                'business_address' => $data['business_address'],
                'business_email' => $data['business_email'],
                'business_phone' => $data['business_phone'],
                'br_number' => $data['br_number'] ?? null,
            ]);

            // Create User
            $user = $this->userRepo->create([
                'business_id' => $business->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'mobile_number' => $data['mobile_number'],
                'password' => Hash::make($data['password']),
            ]);

            $token = auth('api')->login($user);

            return [
                'user' => $user,
                'business' => $business,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];
        });
    }

    public function loginUser(array $credentials): ?array
    {
        if (!$token = auth('api')->attempt($credentials)) {
            return null;
        }

        $user = auth('api')->user();

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}