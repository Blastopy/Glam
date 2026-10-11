<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php', // required for Reverb — see REALTIME_SETUP.md
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:sanctum']]
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class, // required for the admin panel — see ADMIN_SETUP.md
            
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Every API response — success or failure — should be JSON, never Laravel's
        // default HTML error page. This is what makes that consistent app-wide.
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ValidationException $e, $request) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            return response()->json(['message' => $e->getMessage() ?: 'This action is unauthorized.'], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            return response()->json(['message' => 'The requested resource was not found.'], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'The requested resource was not found.'], 404);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            return response()->json(['message' => 'Too many attempts. Please wait a moment and try again.'], 429);
        });

        // Catches everything abort()/abort_if()/abort_unless() throws — this is what
        // makes every custom message used across the controllers (e.g. "Only providers
        // can add services") come through to the frontend instead of a generic page.
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage() ?: 'Request failed.'], $e->getStatusCode());
            }
        });

        // Last resort — an unexpected server error. Never leak internals to the client
        // in production; full detail still goes to the log either way.
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                return response()->json(['message' => 'Something went wrong on our end. Please try again.'], 500);
            }
        });
    })->create();
