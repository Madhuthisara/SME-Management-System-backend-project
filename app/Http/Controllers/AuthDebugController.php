<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthDebugController extends Controller
{
    /**
     * Diagnostic endpoint to check headers and auth state.
     */
    public function debug(Request $request)
    {
        $headers = $request->headers->all();
        
        // Sanitize headers (remove full tokens for safety, but keep prefix)
        $sanitizedHeaders = [];
        foreach ($headers as $key => $values) {
            if (strtolower($key) === 'authorization') {
                $sanitizedHeaders[$key] = array_map(function($v) {
                    return substr($v, 0, 15) . '... [redacted]';
                }, $values);
            } else {
                $sanitizedHeaders[$key] = $values;
            }
        }

        $authState = [
            'check' => Auth::check(),
            'guard' => Auth::getDefaultDriver(),
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'email' => Auth::user()->email,
            ] : null,
        ];

        try {
            $token = JWTAuth::getToken();
            $payload = $token ? JWTAuth::getPayload($token)->toArray() : null;
        } catch (\Exception $e) {
            $payload = 'Error decoding payload: ' . $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'message' => 'Auth Debug Information',
            'data' => [
                'headers' => $sanitizedHeaders,
                'auth_state' => $authState,
                'jwt_payload' => $payload,
                'php_version' => PHP_VERSION,
                'server_software' => $request->server('SERVER_SOFTWARE'),
            ]
        ]);
    }
}
