<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $businessId = $request->query('business_id');
            $data = $this->profileService->getProfileData(
                $request->user(),
                $businessId ? $businessId : null
            );

            return $this->successResponse($data, 'Profile data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve profile data', 403, [], $e);
        }
    }

    public function updatePersonal(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($request->user()->id),
                ],
                'mobile_number' => 'required|string|max:20',
            ]);

            $user = $this->profileService->updatePersonalDetails(
                $request->user(),
                $validatedData
            );

            return $this->successResponse($user, 'Personal details updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update personal details', 500, [], $e);
        }
    }

    public function updateCompany(Request $request): JsonResponse
    {
        try {
            $businessId = $request->user()->business_id;

            $validatedData = $request->validate([
                'business_name' => 'required|string|max:255',
                'business_email' => [
                    'required',
                    'email',
                    Rule::unique('businesses', 'business_email')->ignore($businessId),
                ],
                'business_phone' => 'required|string|max:20',
                'tax_id' => 'nullable|string|max:50',
                'business_address' => 'required|string|max:500',
                'website' => 'nullable|url|max:255',
                'br_number' => 'nullable|string|max:50',
            ]);

            $business = $this->profileService->updateCompanyDetails(
                $request->user()->business,
                $validatedData
            );

            return $this->successResponse($business, 'Company details updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update company details', 500, [], $e);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'current_password' => 'required|current_password',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $this->profileService->changePassword(
                $request->user(),
                $validatedData
            );

            return $this->successResponse([], 'Password changed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to change password', 500, [], $e);
        }
    }
}
