<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentGatewayException;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $webhookSecret;
    private string $environment;

    private const API_BASE = 'https://api.stripe.com/v1';

    public function __construct(array $credentials, string $environment = 'sandbox')
    {
        $this->secretKey     = $credentials['secret_key'] ?? env('STRIPE_SECRET_KEY', '');
        $this->webhookSecret = $credentials['webhook_secret'] ?? env('STRIPE_WEBHOOK_SECRET', '');
        $this->environment   = $environment;
    }

    public function initiate(array $paymentData): array
    {
        $amountInCents = (int) round($paymentData['amount'] * 100);
        $currency      = strtolower($paymentData['currency'] ?? 'lkr');

        try {
            // Set Stripe API Key
            Stripe::setApiKey($this->secretKey);

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Order Payment',
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $paymentData['return_url'] ?? '',
                'cancel_url' => $paymentData['cancel_url'] ?? '',
                'client_reference_id' => $paymentData['order_id'] ?? null,
                'metadata' => [
                    'order_id'    => $paymentData['order_id'] ?? null,
                    'business_id' => $paymentData['business_id'] ?? null,
                ],
            ]);

            return [
                'txn_id'      => $session->id, // cs_test_...
                'payment_url' => $session->url, // Redirect URL for Stripe Checkout
                'status'      => 'pending',
                'metadata'    => ['session_id' => $session->id]
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new PaymentGatewayException('Stripe API Error: ' . $e->getMessage());
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
     * Parse a webhook payload. We assume the signature is verified before calling this.
     */
    public function handleWebhook(Request $request): array
    {
        $payload = $request->input();
        $event   = $payload['type'] ?? '';
        $object  = $payload['data']['object'] ?? [];

        $status = match($event) {
            'checkout.session.completed'            => 'completed',
            'checkout.session.expired'              => 'failed',
            'checkout.session.async_payment_failed' => 'failed',
            'payment_intent.succeeded'              => 'completed',
            'payment_intent.payment_failed'         => 'failed',
            default                                 => 'pending',
        };

        return [
            'txn_id'   => $object['id'] ?? null,
            'order_id' => $object['client_reference_id'] ?? ($object['metadata']['order_id'] ?? null),
            'status'   => $status,
        ];
    }

    /**
     * Verify Stripe webhook signature using Stripe SDK.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $sigHeader = $request->header('Stripe-Signature');
        $payload   = $request->getContent();
        $secret    = $this->webhookSecret;

        if (!$sigHeader || !$secret) {
            return false;
        }

        try {
            Webhook::constructEvent($payload, $sigHeader, $secret);
            return true;
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return false;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return false;
        }
    }
}
