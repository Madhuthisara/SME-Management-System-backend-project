<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayFactory $factory
    ) {}

    // =========================================================================
    // Gateway Settings Management
    // =========================================================================

    /**
     * Upsert a single gateway's settings for a business.
     * Does NOT deactivate other gateways — each is toggled independently.
     * Empty credential strings are ignored so they don't overwrite existing keys.
     */
    public function saveGatewaySettings(string $businessId, array $data): PaymentSetting
    {
        $existing = PaymentSetting::where('business_id', $businessId)
            ->where('gateway_name', $data['gateway_name'])
            ->first();

        // Remove null and empty strings from new credentials
        $newCredentials = array_filter($data['credentials'], fn($val) => $val !== null && $val !== '');
        $credentials = $existing ? array_merge($existing->credentials ?? [], $newCredentials) : $newCredentials;

        return PaymentSetting::updateOrCreate(
            [
                'business_id'  => $businessId,
                'gateway_name' => $data['gateway_name'],
            ],
            [
                'credentials'   => $credentials,
                'is_active'     => $data['is_active'] ?? true,
                'display_order' => $data['display_order'] ?? 0,
                'environment'   => $data['environment'] ?? 'sandbox',
            ]
        );
    }

    /**
     * Toggle the is_active flag for a single gateway setting.
     */
    public function toggleGateway(PaymentSetting $setting): PaymentSetting
    {
        $setting->update(['is_active' => !$setting->is_active]);
        return $setting->fresh();
    }

    /**
     * Get all gateway settings for a business with credentials MASKED.
     * Safe to return in API responses.
     */
    public function getGatewaySettings(string $businessId): array
    {
        $settings = PaymentSetting::where('business_id', $businessId)
            ->orderBy('display_order')
            ->get();

        return $settings->map(function (PaymentSetting $setting) {
            return [
                'id'            => $setting->id,
                'gateway_name'  => $setting->gateway_name,
                'is_active'     => $setting->is_active,
                'display_order' => $setting->display_order,
                'environment'   => $setting->environment,
                'credentials'   => $this->maskCredentials($setting->credentials ?? []),
                'created_at'    => $setting->created_at,
                'updated_at'    => $setting->updated_at,
            ];
        })->toArray();
    }

    /**
     * Get all ACTIVE gateways for a business — public-safe (no credentials).
     * Used directly in the checkout UI gateway selector.
     */
    public function getActiveMethods(string $businessId): array
    {
        return PaymentSetting::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn(PaymentSetting $s) => [
                'gateway_name'  => $s->gateway_name,
                'display_name'  => $this->getDisplayName($s->gateway_name),
                'logo_url'      => $this->getLogoUrl($s->gateway_name),
                'display_order' => $s->display_order,
            ])
            ->toArray();
    }

    // =========================================================================
    // Payment Processing
    // =========================================================================

    /**
     * Initiate a payment with the customer's chosen gateway.
     * Creates a pending PaymentTransaction record.
     *
     * @throws PaymentGatewayException
     */
    public function initiatePayment(array $data): array
    {
        $businessId  = $data['business_id'];
        $gatewayName = $data['gateway_name'];

        // Factory resolves the correct driver — throws PaymentGatewayException if not found/active
        $gateway = $this->factory->make($businessId, $gatewayName);

        $result = $gateway->initiate($data);

        // Create a pending transaction record
        PaymentTransaction::create([
            'business_id'    => $businessId,
            'order_id'       => $data['order_id'] ?? null,
            'gateway_name'   => $gatewayName,
            'gateway_txn_id' => $result['txn_id'],
            'amount'         => $data['amount'],
            'currency'       => strtoupper($data['currency'] ?? 'LKR'),
            'status'         => 'pending',
            'metadata'       => $result,
        ]);

        return $result;
    }

    /**
     * Verify a payment transaction by its gateway transaction ID.
     *
     * @throws PaymentGatewayException
     */
    public function verifyPayment(string $businessId, string $gatewayTxnId): array
    {
        $transaction = PaymentTransaction::where('gateway_txn_id', $gatewayTxnId)
            ->where('business_id', $businessId)
            ->first();

        if (!$transaction) {
            throw new PaymentGatewayException('Transaction not found.');
        }

        $gateway = $this->factory->make($businessId, $transaction->gateway_name);
        $result  = $gateway->verify($gatewayTxnId);

        $transaction->update([
            'status'   => $result['status'],
            'metadata' => array_merge($transaction->metadata ?? [], ['verify_response' => $result['gateway_data']]),
        ]);

        return ['transaction' => $transaction->fresh(), 'gateway_result' => $result];
    }

    // =========================================================================
    // Credentials Masking
    // =========================================================================

    /**
     * Mask sensitive credential values before returning in API responses.
     * "sk_test_abc123xyz5a2b" → "sk_test_*******5a2b"
     * Short values (<=11 chars) are fully masked.
     */
    public function maskCredentials(array $credentials): array
    {
        return array_map(fn($value) => $this->maskValue((string) $value), $credentials);
    }

    private function maskValue(string $value): string
    {
        if (strlen($value) <= 11) {
            return str_repeat('*', strlen($value));
        }
        return substr($value, 0, 7) . '*******' . substr($value, -4);
    }

    // =========================================================================
    // Display Helpers
    // =========================================================================

    private function getDisplayName(string $gatewayName): string
    {
        return match($gatewayName) {
            'stripe'  => 'Stripe',
            'paypal'  => 'PayPal',
            'payhere' => 'PayHere',
            default   => ucfirst($gatewayName),
        };
    }

    private function getLogoUrl(string $gatewayName): string
    {
        return "/images/payment-logos/{$gatewayName}.png";
    }
}
