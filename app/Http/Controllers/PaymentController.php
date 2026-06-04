<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentSetting;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}
    
    /**
     * Get all active payment gateways for a business.
     * GET /api/payments/methods/{businessId}
     */
    public function activeMethods(string $businessId): JsonResponse
    {
        try {
            $methods = $this->paymentService->getActiveMethods($businessId);
            return response()->json([
                'success' => true,
                'output'  => $methods
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to fetch active methods: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment methods.'
            ], 500);
        }
    }

    /**
     * Standard initiation for all gateways (Stripe, PayPal, etc.)
     */
    public function initiate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_id'         => 'required|string',
                'gateway_name'        => 'required|string',
                'amount'              => 'required|numeric|min:0.01',
                'currency'            => 'required|string|size:3',
                'order_id'            => 'nullable|string',
                'return_url'          => 'required|url',
                'cancel_url'          => 'required|url',
                'notify_url'          => 'nullable|url',
            ]);

            $result = $this->paymentService->initiatePayment($validated);
            return response()->json([
                'success' => true,
                'status'  => 'success',
                'params'  => $result,
                'action'  => $result['payment_url'] ?? ''
            ], 201);
        } catch (\Exception $e) {
            Log::error('Gateway Initiation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to initiate payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unified Webhook endpoint for gateways.
     */
    public function webhook(Request $request, string $businessId, string $gatewayName): JsonResponse
    {
        try {
            $gateway = $this->paymentService->factory->make($businessId, $gatewayName);
            
            if (!$gateway->verifyWebhookSignature($request)) {
                Log::warning("{$gatewayName} Webhook signature verification failed.", ['business' => $businessId]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $webhookData = $gateway->handleWebhook($request);
            
            // Optionally update database transaction status here based on $webhookData
            Log::info("{$gatewayName} Webhook Processed", $webhookData);

            return response()->json(['message' => 'Webhook handled successfully'], 200);
        } catch (\Exception $e) {
            Log::error("{$gatewayName} Webhook Error: " . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}