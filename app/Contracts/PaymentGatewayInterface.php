<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment session with the gateway.
     *
     * @param  array  $paymentData  Must include: amount (decimal), currency, order_id, return_url, cancel_url
     *                              Amount is ALWAYS a standard decimal (e.g. 150.00).
     *                              Each driver is responsible for converting to the gateway's required format.
     * @return array  ['txn_id' => string, 'payment_url' => string, 'status' => 'pending']
     */
    public function initiate(array $paymentData): array;

    /**
     * Verify a transaction status by its gateway transaction ID.
     *
     * @param  string  $transactionId  The gateway-assigned transaction ID
     * @return array  ['status' => 'completed|failed|pending', 'gateway_data' => array]
     */
    public function verify(string $transactionId): array;

    /**
     * Parse and extract payment outcome from a webhook request payload.
     * Must be called AFTER verifyWebhookSignature() passes.
     *
     * @return array  ['txn_id' => string, 'order_id' => string|null, 'status' => 'completed|failed']
     */
    public function handleWebhook(Request $request): array;

    /**
     * Verify the authenticity of an incoming webhook request.
     * Each gateway uses a different verification method:
     *  - Stripe: HMAC-SHA256 on 'Stripe-Signature' header
     *  - PayPal: 'PAYPAL-TRANSMISSION-SIG' via PayPal API
     *  - PayHere: MD5 hash comparison
     *
     * @return bool  True if the request is genuine, false if it should be rejected (403)
     */
    public function verifyWebhookSignature(Request $request): bool;
}
