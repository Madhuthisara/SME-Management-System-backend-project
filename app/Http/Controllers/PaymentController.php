<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Initiate a payment session specifically for PayHere Sandbox.
     * Returns a JSON containing all required parameters for a direct
     * frontend-side form submission to PayHere.
     * 
     * POST /api/payment/initiate
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|string',
                'amount'   => 'required|numeric',
            ]);

            $original_order_id = trim($validated['order_id']);
            $amount = (float) $validated['amount'];

           // 1. Credentials from .env
            $merchant_id = trim(env('PAYHERE_MERCHANT_ID', '1235978'));
            $merchant_secret_raw = trim(env('PAYHERE_MERCHANT_SECRET'));
            $currency = 'LKR';

            // Base64 Decode the secret first because PayHere dashboard provides it encoded
            $merchant_secret = base64_decode($merchant_secret_raw);

            // 2. Shorten Order ID for PayHere (max 20 characters limit)
            $payhere_order_id = 'ORD-' . rand(100000, 999999);

            // 3. Mapping: Store mapping from short ID back to original ID in Cache
            $mappingKey = 'payhere_mapping_' . $payhere_order_id;
            Cache::put($mappingKey, [
                'original_id' => $original_order_id,
                'short_id'    => $payhere_order_id
            ], now()->addMinutes(120));

            // 4. Format amount to exactly 2 decimals
            $amount_formatted = number_format($amount, 2, '.', '');

            // 5. Calculate MD5 Hash correctly for PayHere
            // Formula: Merchant ID + Order ID + Amount + Currency + MD5(Plain Secret) in Uppercase
            $merchant_md5_secret = strtoupper(md5($merchant_secret));
            $hash_string = $merchant_id . $payhere_order_id . $amount_formatted . $currency . $merchant_md5_secret;
            $hash = strtoupper(md5($hash_string));

            // Optional: URL configuration
            $ngrokUrl = trim(env('NGROK_URL', ''));
            $frontendUrl = trim(env('FRONTEND_URL', 'http://localhost:3000'));

            $paymentParams = [
                'merchant_id'  => $merchant_id,
                'return_url'   => $frontendUrl . '/order-confirmation',
                'cancel_url'   => $frontendUrl . '/checkout',
                'notify_url'   => $ngrokUrl . '/api/payment/notify',
                'order_id'     => $payhere_order_id,
                'items'        => 'EStore Order ' . $payhere_order_id,
                'currency'     => $currency,
                'amount'       => $amount_formatted,
                'first_name'   => 'Customer',
                'last_name'    => 'User',
                'email'        => 'customer@example.com',
                'phone'        => '0771234567',
                'address'      => 'No 1, Main Street',
                'city'         => 'Colombo',
                'country'      => 'Sri Lanka',
                'hash'         => $hash,
            ];

            Log::info("PayHere Initiation Success:", [
                'payhere_id'  => $payhere_order_id,
                'original_id' => $original_order_id,
                'amount'      => $amount_formatted
            ]);

            return response()->json([
                'status' => 'success',
                'params' => $paymentParams,
                'action' => 'https://sandbox.payhere.lk/pay/checkout',
                'sandbox' => true
            ]);

        } catch (\Exception $e) {
            Log::error("PayHere Initiation Failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate payment. Please try again.'
            ], 500);
        }
    }

    /**
     * PayHere Notify Webhook
     * Processes the asynchronous callback from PayHere.
     * 
     * POST /api/payment/notify
     */
    public function notify(Request $request): JsonResponse
    {
        $merchant_id = $request->input('merchant_id');
        $payhere_order_id = $request->input('order_id');
        $payhere_amount = $request->input('payhere_amount');
        $payhere_currency = $request->input('payhere_currency');
        $status_code = $request->input('status_code');
        $md5sig = $request->input('md5sig');

      $merchant_secret_raw = trim(env('PAYHERE_MERCHANT_SECRET'));
        $merchant_secret = base64_decode($merchant_secret_raw);

        // 1. Verify Hash Signature
     $local_md5sig = strtoupper(
            md5(
                $merchant_id . 
                $payhere_order_id . 
                $payhere_amount . 
                $payhere_currency . 
                $status_code . 
                strtoupper(md5($merchant_secret))
            )
        );
        // 2. Recover Original Order ID from Cache Mapping
        $mappingKey = 'payhere_mapping_' . $payhere_order_id;
        $mapping = Cache::get($mappingKey);
        $original_order_id = $mapping ? $mapping['original_id'] : $payhere_order_id;

        if ($local_md5sig === $md5sig && $status_code == 2) {
            // Success
            Log::info("PayHere Payment Success. Short ID: $payhere_order_id, Original ID: $original_order_id");
            
            // TODO: Final database update for the order
            // Example: Order::where('id', $original_order_id)->update(['status' => 'paid']);
            
            // Optional: Cleanup mapping
            Cache::forget($mappingKey);

            return response()->json(['message' => 'Payment Success processed'], 200);
        }

        Log::error("PayHere Callback Invalid or Failed: ", [
            'short_id' => $payhere_order_id,
            'original_id' => $original_order_id,
            'status' => $status_code
        ]);

        return response()->json(['message' => 'Callback processing failed'], 400);
    }

    /**
     * PUBLIC endpoint — returns active gateways for a business.
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
     * Standard initiation for other gateways.
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
            ]);

            $result = $this->paymentService->initiatePayment($validated);
            return $this->successResponse($result, 'Payment initiated successfully.', 201);
        } catch (PaymentGatewayException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to initiate payment.', 500);
        }
    }
}