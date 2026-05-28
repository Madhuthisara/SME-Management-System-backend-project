<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        $middleware->trustProxies(at: '*');
        
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->alias([
            'jwt.verify' => \App\Http\Middleware\JwtMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'login',
            'logout',
            'api/payment/notify',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first(),
                    'output' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                \Illuminate\Support\Facades\Log::error('Server Error: ' . $e->getMessage(), [
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Internal Server Error: ' . $e->getMessage(),
                    'output' => [],
                ], 500);
            }
        });
    })->create();