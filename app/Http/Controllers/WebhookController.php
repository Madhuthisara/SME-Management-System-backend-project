<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayFactory $factory
    ) {}

    /**
     * Handle Stripe webhook callbacks.
     * POST /api/webhooks/stripe/{businessId}
     */
    public function handleStripe(Request $request, string $businessId): Response
    {
        return $this->processWebhook($request, $businessId, 'stripe');
    }

    /**
     * Handle PayPal webhook callbacks.
     * POST /api/webhooks/paypal/{businessId}
     */
    public function handlePaypal(Request $request, string $businessId): Response
    {
        return $this->processWebhook($request, $businessId, 'paypal');
    }

    /**
     * Handle PayHere IPN (Instant Payment Notification) callbacks.
     * POST /api/webhooks/payhere/{businessId}
     */
    public function handlePayhere(Request $request, string $businessId): Response
    {
        return $this->processWebhook($request, $businessId, 'payhere');
    }

    /**
     * Shared webhook processing logic with security + idempotency checks.
     *
     * Security flow:
     *  1. Load the active gateway setting (403 if not found)
     *  2. Verify the request signature (403 if invalid)
     *  3. Parse the payload to extract txn_id and new status
     *  4. Idempotency check: if already completed/failed → return 200 immediately
     *  5. Update transaction status
     *  6. (Optional) Trigger order fulfillment
     *  7. Return 200 — critical to prevent gateway retry storms
     */
    private function processWebhook(Request $request, string $businessId, string $gatewayName): Response
    {
        // Step 1: Find the active gateway setting
        $setting = PaymentSetting::where('business_id', $businessId)
            ->where('gateway_name', $gatewayName)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            return response('Gateway not found or inactive.', 403);
        }

        try {
            // Step 2: Resolve the driver and verify signature
            $gateway = $this->factory->make($businessId, $gatewayName);

            if (!$gateway->verifyWebhookSignature($request)) {
                return response('Invalid webhook signature.', 403);
            }

            // Step 3: Parse the payload
            $webhookData = $gateway->handleWebhook($request);
            $txnId       = $webhookData['txn_id'] ?? null;
            $newStatus   = $webhookData['status'] ?? null;

            if (!$txnId || !$newStatus) {
                // Payload is valid but event type is not actionable — acknowledge it
                return response('Webhook received.', 200);
            }

            // Step 4: Idempotency check — prevent double-processing
            $transaction = PaymentTransaction::where('gateway_txn_id', $txnId)
                ->where('business_id', $businessId)
                ->first();

            if (!$transaction) {
                // Transaction may not exist yet for gateway-initiated flows (e.g., PayHere redirect)
                // Create it so we have a record
                $transaction = PaymentTransaction::create([
                    'business_id'    => $businessId,
                    'gateway_name'   => $gatewayName,
                    'gateway_txn_id' => $txnId,
                    'order_id'       => $webhookData['order_id'] ?? null,
                    'amount'         => $webhookData['payhere_amount'] ?? 0,
                    'currency'       => $webhookData['payhere_currency'] ?? 'LKR',
                    'status'         => 'pending',
                    'metadata'       => $webhookData,
                ]);
            }

            // If already in a terminal state, return 200 without reprocessing
            if (in_array($transaction->status, ['completed', 'failed', 'refunded'])) {
                return response('Already processed.', 200);
            }

            // Step 5: Update transaction status
            $transaction->update([
                'status'   => $newStatus,
                'metadata' => array_merge($transaction->metadata ?? [], ['webhook' => $webhookData]),
            ]);

            // Step 6: If completed, you can dispatch an order fulfillment job here
            // Example: if ($newStatus === 'completed' && $transaction->order_id) {
            //     ProcessOrderFulfillment::dispatch($transaction->order_id);
            // }

            // Step 7: Always return 200 to prevent gateway from retrying
            return response('Webhook processed.', 200);

        } catch (\Exception $e) {
            // Log internally but still return 200 to prevent retry loops
            \Illuminate\Support\Facades\Log::error("Webhook processing error [{$gatewayName}]: " . $e->getMessage(), [
                'business_id' => $businessId,
                'payload'     => $request->all(),
            ]);

            return response('Webhook received with errors.', 200);
        }
    }
}
