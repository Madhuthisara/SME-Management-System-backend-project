<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentGatewayException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayHereGateway implements PaymentGatewayInterface
{
    private string $merchantId;
    private string $merchantSecret;
    private string $environment;
    private string $checkoutUrl;

    public function __construct(array $credentials, string $environment = 'sandbox')
    {
        $this->merchantId     = $credentials['merchant_id'] ?? '';
        $this->merchantSecret = $credentials['merchant_secret'] ?? '';
        $this->environment    = $environment;
        $this->checkoutUrl    = $environment === 'production'
            ? 'https://www.payhere.lk/pay/checkout'
            : 'https://sandbox.payhere.lk/pay/checkout';
    }

    /**
     * Generate the PayHere MD5 hash required in the checkout form.
     * Formula: md5(merchant_id + order_id + amount_formatted + currency + md5(strtoupper(merchant_secret)))
     */
    private function generateHash(string $orderId, string $amount, string $currency): string
    {
        $hashedSecret = strtoupper(md5($this->merchantSecret));
        return strtoupper(md5(
            $this->merchantId . $orderId . $amount . $currency . $hashedSecret
        ));
    }

    /**
     * Amount Normalization: PayHere uses LKR natively and accepts standard decimal format.
     * LKR 150.00 → "150.00"
     */
    public function initiate(array $paymentData): array
    {
        $orderId  = $paymentData['order_id'] ?? uniqid('ph_');
        $amount   = number_format((float) $paymentData['amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'LKR');
        $hash     = $this->generateHash($orderId, $amount, $currency);

        // PayHere uses a redirect-based checkout via a POST HTML form.
        // We return the checkout URL and all required form parameters.
        // The frontend must construct and auto-submit the form.
        return [
            'txn_id'      => $orderId,
            'payment_url' => $this->checkoutUrl,
            'status'      => 'pending',
            'form_params' => [
                'merchant_id'  => $this->merchantId,
                'return_url'   => $paymentData['return_url'] ?? '',
                'cancel_url'   => $paymentData['cancel_url'] ?? '',
                'notify_url'   => $paymentData['notify_url'] ?? '',
                'order_id'     => $orderId,
                'items'        => $paymentData['items_description'] ?? 'Order Payment',
                'currency'     => $currency,
                'amount'       => $amount,
                'first_name'   => $paymentData['first_name'] ?? '',
                'last_name'    => $paymentData['last_name'] ?? '',
                'email'        => $paymentData['email'] ?? '',
                'phone'        => $paymentData['phone'] ?? '',
                'address'      => $paymentData['address'] ?? '',
                'city'         => $paymentData['city'] ?? '',
                'country'      => $paymentData['country'] ?? 'Sri Lanka',
                'hash'         => $hash,
            ],
        ];
    }

    /**
     * PayHere does not have a direct status query API in the standard flow.
     * Status is confirmed via the notify_url webhook (IPN).
     * This method can be used to verify via PayHere's order status API if enabled.
     */
    public function verify(string $transactionId): array
    {
        // PayHere status verification requires their reporting API.
        // Basic implementation: return pending and rely on webhook.
        return [
            'status'       => 'pending',
            'gateway_data' => ['note' => 'PayHere status confirmed via webhook IPN'],
        ];
    }

    /**
     * Parse PayHere IPN (Instant Payment Notification) webhook payload.
     * PayHere sends a POST form-encoded payload.
     */
    public function handleWebhook(Request $request): array
    {
        $statusCode = (int) $request->input('status_code');

        $status = match($statusCode) {
            2  => 'completed',  // Success
            0  => 'pending',    // Pending
            -1 => 'failed',     // Canceled
            -2 => 'failed',     // Failed
            -3 => 'refunded',   // Chargedback
            default => 'pending',
        };

        return [
            'txn_id'            => $request->input('payment_id'),
            'order_id'          => $request->input('order_id'),
            'status'            => $status,
            'payhere_amount'    => $request->input('payhere_amount'),
            'payhere_currency'  => $request->input('payhere_currency'),
        ];
    }

    /**
     * Verify PayHere IPN signature.
     * PayHere sends: merchant_id, order_id, payhere_amount, payhere_currency, status_code, md5sig
     * Expected MD5: strtoupper(md5(merchant_id + order_id + payhere_amount + payhere_currency + status_code + md5(strtoupper(merchant_secret))))
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $merchantId    = $request->input('merchant_id');
        $orderId       = $request->input('order_id');
        $amount        = $request->input('payhere_amount');
        $currency      = $request->input('payhere_currency');
        $statusCode    = $request->input('status_code');
        $receivedSig   = strtoupper($request->input('md5sig', ''));

        if (!$merchantId || !$orderId || !$amount || !$currency || !$statusCode || !$receivedSig) {
            return false;
        }

        // Verify the merchant_id matches our stored credentials
        if ($merchantId !== $this->merchantId) {
            return false;
        }

        $hashedSecret  = strtoupper(md5($this->merchantSecret));
        $expectedSig   = strtoupper(md5(
            $merchantId . $orderId . $amount . $currency . $statusCode . $hashedSecret
        ));

        return hash_equals($expectedSig, $receivedSig);
    }
}
