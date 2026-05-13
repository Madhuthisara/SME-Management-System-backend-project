<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Repositories\BusinessRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected BusinessRepositoryInterface $businessRepo
    ) {}

    public function getProfileData(User $user, ?string $businessId = null): array
    {
        $business = $user->business;

        if ($businessId && $user->business_id !== $businessId) {
            throw new \Exception('Unauthorized access to business data.');
        }

        return [
            'user' => $user,
            'business' => $business,
        ];
    }

    public function updatePersonalDetails(User $user, array $data): User
    {
        $this->userRepo->update($user->id, $data);
        return $user->fresh();
    }

    public function updateCompanyDetails(Business $business, array $data): Business
    {
        $this->businessRepo->update($business->id, $data);
        return $business->fresh();
    }

    public function changePassword(User $user, array $data): void
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            throw new \Exception('Current password is incorrect');
        }

        $this->userRepo->update($user->id, [
            'password' => Hash::make($data['new_password']),
        ]);
    }
}
