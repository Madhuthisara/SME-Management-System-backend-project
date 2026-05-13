<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentGatewayException;
use App\Models\PaymentSetting;
use App\Services\Payment\Drivers\PayHereGateway;
use App\Services\Payment\Drivers\PayPalGateway;
use App\Services\Payment\Drivers\StripeGateway;

class PaymentGatewayFactory
{
    /**
     * Resolve the correct gateway driver for a given business and gateway name.
     * The gateway must be active (is_active = true) for the business.
     *
     * @throws PaymentGatewayException  User-friendly error if gateway not found or unsupported.
     */
    public function make(string $businessId, string $gatewayName): PaymentGatewayInterface
    {
        $setting = PaymentSetting::where('business_id', $businessId)
            ->where('gateway_name', $gatewayName)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            throw new PaymentGatewayException(
                "Payment gateway '{$gatewayName}' is not configured or not active for this business."
            );
        }

        // Credentials are automatically decrypted here by the 'encrypted:array' model cast
        $credentials = $setting->credentials;
        $environment = $setting->environment;

        return match($gatewayName) {
            'stripe'  => new StripeGateway($credentials, $environment),
            'paypal'  => new PayPalGateway($credentials, $environment),
            'payhere' => new PayHereGateway($credentials, $environment),
            default   => throw new PaymentGatewayException("Unsupported payment gateway: '{$gatewayName}'."),
        };
    }
}
