<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentGatewayException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPalGateway implements PaymentGatewayInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $environment;
    private string $baseUrl;

    public function __construct(array $credentials, string $environment = 'sandbox')
    {
        $this->clientId     = $credentials['client_id'] ?? '';
        $this->clientSecret = $credentials['client_secret'] ?? '';
        $this->environment  = $environment;
        $this->baseUrl      = $environment === 'production'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Obtain a short-lived OAuth2 access token from PayPal.
     */
    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->baseUrl . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            throw new PaymentGatewayException('PayPal auth failed: ' . ($response->json('error_description') ?? 'Unknown'));
        }

        return $response->json('access_token');
    }

    /**
     * Amount Normalization: PayPal requires a string decimal with exactly 2 decimal places.
     * LKR 150.00 → "150.00", USD 10.50 → "10.50"
     */
    public function initiate(array $paymentData): array
    {
        $amount   = number_format((float) $paymentData['amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->post($this->baseUrl . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => $paymentData['order_id'] ?? null,
                        'amount'       => [
                            'currency_code' => $currency,
                            'value'         => $amount,
                        ],
                    ]],
                    'application_context' => [
                        'return_url' => $paymentData['return_url'] ?? '',
                        'cancel_url' => $paymentData['cancel_url'] ?? '',
                    ],
                ]);

            if ($response->failed()) {
                throw new PaymentGatewayException('PayPal Error: ' . ($response->json('message') ?? 'Unknown'));
            }

            $order      = $response->json();
            $approveUrl = collect($order['links'])->firstWhere('rel', 'approve')['href'] ?? '';

            return [
                'txn_id'      => $order['id'],
                'payment_url' => $approveUrl,
                'status'      => 'pending',
            ];
        } catch (PaymentGatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentGatewayException('Failed to initiate PayPal payment: ' . $e->getMessage());
        }
    }

    /**
     * Verify a PayPal order by its ID (capture if approved).
     */
    public function verify(string $transactionId): array
    {
        try {
            $token    = $this->getAccessToken();
            $response = Http::withToken($token)
                ->get($this->baseUrl . '/v2/checkout/orders/' . $transactionId);

            if ($response->failed()) {
                throw new PaymentGatewayException('PayPal verify error');
            }

            $order  = $response->json();
            $status = match($order['status'] ?? '') {
                'COMPLETED', 'APPROVED' => 'completed',
                'VOIDED'                => 'failed',
                default                 => 'pending',
            };

            return ['status' => $status, 'gateway_data' => $order];
        } catch (PaymentGatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentGatewayException('Failed to verify PayPal payment: ' . $e->getMessage());
        }
    }

    /**
     * Parse PayPal webhook payload.
     */
    public function handleWebhook(Request $request): array
    {
        $payload  = $request->all();
        $event    = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        $status = match($event) {
            'PAYMENT.CAPTURE.COMPLETED' => 'completed',
            'PAYMENT.CAPTURE.DENIED'    => 'failed',
            'PAYMENT.CAPTURE.REFUNDED'  => 'refunded',
            default                     => 'pending',
        };

        // Extract order_id from purchase_units supplementary_data or custom_id
        $orderId = $resource['supplementary_data']['related_ids']['order_id']
            ?? $resource['custom_id']
            ?? null;

        return [
            'txn_id'   => $resource['id'] ?? null,
            'order_id' => $orderId,
            'status'   => $status,
        ];
    }

    /**
     * Verify PayPal webhook signature via PayPal's verification API.
     * Requires: PAYPAL-AUTH-ALGO, PAYPAL-CERT-URL, PAYPAL-TRANSMISSION-ID,
     *           PAYPAL-TRANSMISSION-SIG, PAYPAL-TRANSMISSION-TIME headers.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->post($this->baseUrl . '/v1/notifications/verify-webhook-signature', [
                    'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO'),
                    'cert_url'          => $request->header('PAYPAL-CERT-URL'),
                    'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID'),
                    'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG'),
                    'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                    'webhook_id'        => $this->clientId, // Use stored webhook ID if available
                    'webhook_event'     => $request->all(),
                ]);

            return $response->json('verification_status') === 'SUCCESS';
        } catch (\Exception $e) {
            return false;
        }
    }
}
