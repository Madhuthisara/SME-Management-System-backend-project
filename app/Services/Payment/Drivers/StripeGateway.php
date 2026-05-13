<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentGatewayException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $webhookSecret;
    private string $environment;

    private const API_BASE = 'https://api.stripe.com/v1';

    public function __construct(array $credentials, string $environment = 'sandbox')
    {
        $this->secretKey     = $credentials['secret_key'] ?? '';
        $this->webhookSecret = $credentials['webhook_secret'] ?? '';
        $this->environment   = $environment;
    }

    /**
     * Amount Normalization: Stripe requires amounts in the smallest currency unit (cents).
     * LKR 150.00 → 15000, USD 10.50 → 1050
     */
    public function initiate(array $paymentData): array
    {
        $amountInCents = (int) round($paymentData['amount'] * 100);
        $currency      = strtolower($paymentData['currency'] ?? 'lkr');

        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post(self::API_BASE . '/payment_intents', [
                    'amount'               => $amountInCents,
                    'currency'             => $currency,
                    'metadata'             => [
                        'order_id'    => $paymentData['order_id'] ?? null,
                        'business_id' => $paymentData['business_id'] ?? null,
                    ],
                    'automatic_payment_methods' => ['enabled' => true],
                ]);

            if ($response->failed()) {
                throw new PaymentGatewayException('Stripe Error: ' . ($response->json('error.message') ?? 'Unknown error'));
            }

            $intent = $response->json();

            return [
                'txn_id'      => $intent['id'],           // pi_xxxx
                'client_secret' => $intent['client_secret'], // Returned to frontend for Stripe.js
                'payment_url' => $paymentData['return_url'] ?? '', // Stripe uses client-side Stripe.js
                'status'      => 'pending',
            ];
        } catch (PaymentGatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentGatewayException('Failed to initiate Stripe payment: ' . $e->getMessage());
        }
    }

    /**
     * Verify a PaymentIntent by its ID.
     */
    public function verify(string $transactionId): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_BASE . '/payment_intents/' . $transactionId);

            if ($response->failed()) {
                throw new PaymentGatewayException('Stripe verify error: ' . ($response->json('error.message') ?? 'Unknown'));
            }

            $intent = $response->json();
            $status = match($intent['status']) {
                'succeeded' => 'completed',
                'canceled'  => 'failed',
                default     => 'pending',
            };

            return ['status' => $status, 'gateway_data' => $intent];
        } catch (PaymentGatewayException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentGatewayException('Failed to verify Stripe payment: ' . $e->getMessage());
        }
    }

    /**
     * Parse a webhook payload after signature is verified.
     */
    public function handleWebhook(Request $request): array
    {
        $payload = $request->all();
        $event   = $payload['type'] ?? '';
        $object  = $payload['data']['object'] ?? [];

        $status = match($event) {
            'payment_intent.succeeded'              => 'completed',
            'payment_intent.payment_failed'         => 'failed',
            'charge.refunded'                       => 'refunded',
            default                                 => 'pending',
        };

        return [
            'txn_id'   => $object['id'] ?? null,
            'order_id' => $object['metadata']['order_id'] ?? null,
            'status'   => $status,
        ];
    }

    /**
     * Verify Stripe webhook signature using HMAC-SHA256.
     * Stripe sends a 'Stripe-Signature' header with a timestamp + signature.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $sigHeader = $request->header('Stripe-Signature');
        $payload   = $request->getContent();
        $secret    = $this->webhookSecret;

        if (!$sigHeader || !$secret) {
            return false;
        }

        // Parse the timestamp and v1 signature from the header
        $parts     = explode(',', $sigHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part, 2);
            if ($key === 't')  $timestamp    = $value;
            if ($key === 'v1') $signatures[] = $value;
        }

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        // Compute the expected signature
        $signedPayload   = "{$timestamp}.{$payload}";
        $expectedSig     = hash_hmac('sha256', $signedPayload, $secret);

        // Compare against any of the signatures in the header (Stripe can send multiple)
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSig, $sig)) {
                return true;
            }
        }

        return false;
    }
}
