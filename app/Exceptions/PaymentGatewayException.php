<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by PaymentGatewayFactory and gateway drivers for expected, user-facing errors.
 * Caught in controllers to return structured 422 JSON instead of raw PHP exceptions.
 *
 * Usage in controllers:
 *   } catch (PaymentGatewayException $e) {
 *       return $this->errorResponse($e->getMessage(), 422);
 *   }
 */
class PaymentGatewayException extends Exception
{
    // Inherits standard Exception constructor: __construct(string $message, int $code = 0)
}
