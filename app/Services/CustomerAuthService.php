<?php

namespace App\Services;

use App\Repositories\CustomerRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CustomerAuthService
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepo
    ) {}

    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $customer = $this->customerRepo->create($data);

        $token = Auth::guard('customer')->login($customer);

        return [
            'customer' => $customer,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function login(array $credentials): ?array
    {
        if (!$token = Auth::guard('customer')->attempt($credentials)) {
            return null;
        }

        $customer = Auth::guard('customer')->user();

        return [
            'customer' => $customer,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function getProfile($customer)
    {
        return $customer;
    }

    public function updateProfile($customer, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $this->customerRepo->update($customer->id, $data);
        return $this->customerRepo->find($customer->id);
    }
}
