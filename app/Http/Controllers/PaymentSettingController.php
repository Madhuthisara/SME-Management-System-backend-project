<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Models\PaymentSetting;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentSettingController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Get all gateway configurations for the authenticated business (credentials masked).
     */
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        if (!$businessId) {
            return $this->errorResponse('Business ID is required.', 422);
        }

        try {
            $settings = $this->paymentService->getGatewaySettings($businessId);
            return $this->successResponse($settings, 'Payment settings retrieved successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Save (create or update) a single gateway's configuration for a business.
     * Does NOT deactivate other gateways — multi-active design.
     */
    public function save(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_id'                => 'required|exists:businesses,id',
                'gateway_name'               => 'required|in:stripe,paypal,payhere',
                'credentials'                => 'required|array',
                // Stripe fields
                'credentials.secret_key'       => 'required_if:gateway_name,stripe|nullable|string',
                'credentials.publishable_key'  => 'required_if:gateway_name,stripe|nullable|string',
                'credentials.webhook_secret'   => 'required_if:gateway_name,stripe|nullable|string',
                // PayPal fields
                'credentials.client_id'        => 'required_if:gateway_name,paypal|nullable|string',
                'credentials.client_secret'    => 'required_if:gateway_name,paypal|nullable|string',
                // PayHere fields
                'credentials.merchant_id'      => 'required_if:gateway_name,payhere|nullable|string',
                'credentials.merchant_secret'  => 'required_if:gateway_name,payhere|nullable|string',
                'credentials.app_id'           => 'nullable|string',
                'credentials.app_secret'       => 'nullable|string',
                'is_active'                  => 'sometimes|boolean',
                'display_order'              => 'sometimes|integer|min:0',
                'environment'                => 'sometimes|in:sandbox,production',
            ]);

            $setting = $this->paymentService->saveGatewaySettings(
                $validated['business_id'],
                $validated
            );

            return $this->successResponse(
                ['id' => $setting->id, 'gateway_name' => $setting->gateway_name],
                'Payment gateway settings saved successfully.',
                201
            );
        } catch (PaymentGatewayException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Toggle the is_active status of a specific gateway independently.
     */
    public function toggle(string $id): JsonResponse
    {
        $setting = PaymentSetting::find($id);

        if (!$setting) {
            return $this->errorResponse('Payment setting not found.', 404);
        }

        try {
            $updated = $this->paymentService->toggleGateway($setting);
            return $this->successResponse(
                ['id' => $updated->id, 'is_active' => $updated->is_active],
                "Gateway '{$updated->gateway_name}' " . ($updated->is_active ? 'activated' : 'deactivated') . '.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Remove a gateway configuration for a business.
     */
    public function destroy(string $id): JsonResponse
    {
        $setting = PaymentSetting::find($id);

        if (!$setting) {
            return $this->errorResponse('Payment setting not found.', 404);
        }

        try {
            $setting->delete();
            return $this->successResponse([], 'Payment gateway removed successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
