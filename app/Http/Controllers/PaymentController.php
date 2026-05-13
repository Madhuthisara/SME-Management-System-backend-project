<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * PUBLIC endpoint — returns active gateways for a business (no credentials).
     * Used by the checkout UI to render gateway selector tiles.
     * Ordered by display_order.
     *
     * GET /api/payments/methods/{businessId}
     */
    public function activeMethods(string $businessId): JsonResponse
    {
        try {
            $methods = $this->paymentService->getActiveMethods($businessId);
            return $this->successResponse($methods, 'Active payment methods retrieved successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Initiate a payment session with the customer's chosen gateway.
     * Returns payment_url (and form_params for PayHere) for the frontend to redirect.
     *
     * POST /api/payments/initiate
     * Required fields: business_id, gateway_name, amount, currency, order_id, return_url, cancel_url
     */
    public function initiate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_id'         => 'required|exists:businesses,id',
                'gateway_name'        => 'required|string',
                'amount'              => 'required|numeric|min:0.01',
                'currency'            => 'required|string|size:3',
                'order_id'            => 'nullable|exists:orders,id',
                'return_url'          => 'required|url',
                'cancel_url'          => 'required|url',
                'notify_url'          => 'nullable|url',
                // Customer details (for PayHere)
                'first_name'          => 'nullable|string|max:50',
                'last_name'           => 'nullable|string|max:50',
                'email'               => 'nullable|email',
                'phone'               => 'nullable|string|max:20',
                'address'             => 'nullable|string',
                'city'                => 'nullable|string|max:100',
                'country'             => 'nullable|string|max:100',
                'items_description'   => 'nullable|string|max:255',
            ]);

            $result = $this->paymentService->initiatePayment($validated);

            return $this->successResponse($result, 'Payment initiated successfully.', 201);
        } catch (PaymentGatewayException $e) {
            // User-friendly error — misconfigured/inactive gateway, unsupported gateway name, etc.
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to initiate payment. Please try again.', 500);
        }
    }

    /**
     * Verify a transaction status by its gateway transaction ID.
     *
     * GET /api/payments/verify?business_id=...&txn_id=...
     */
    public function verify(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');
        $txnId      = $request->query('txn_id');

        if (!$businessId || !$txnId) {
            return $this->errorResponse('business_id and txn_id are required.', 422);
        }

        try {
            $result = $this->paymentService->verifyPayment($businessId, $txnId);
            return $this->successResponse($result, 'Payment status verified.');
        } catch (PaymentGatewayException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Verification failed. Please try again.', 500);
        }
    }
}
