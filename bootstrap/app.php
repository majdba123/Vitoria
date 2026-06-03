<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsSyndicate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware(['api', 'auth:sanctum', 'admin'])
                ->prefix('api/admin')
                ->as('admin.')
                ->group(base_path('routes/api_admin.php'));

            Route::middleware(['api', 'auth:sanctum', 'vendor'])
                ->prefix('api/vendor')
                ->as('vendor.')
                ->group(base_path('routes/api_vendor.php'));

            Route::middleware(['api', 'auth:sanctum', 'syndicate'])
                ->prefix('api/syndicate')
                ->as('syndicate.')
                ->group(base_path('routes/api_syndicate.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [\App\Http\Middleware\SetLocale::class]);
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: ['api/*']);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'vendor' => \App\Http\Middleware\EnsureUserIsVendor::class,
            'syndicate' => EnsureUserIsSyndicate::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            'timezone' => \App\Http\Middleware\ApplyUserTimezone::class,
        ]);
        $middleware->appendToGroup('web', [\App\Http\Middleware\ApplyUserTimezone::class]);
        $middleware->appendToGroup('api', [\App\Http\Middleware\ApplyUserTimezone::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, \Illuminate\Http\Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof HttpResponseException) {
                return $exception->getResponse();
            }

            $status = match (true) {
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof TokenMismatchException => 419,
                $exception instanceof ValidationException => 422,
                $exception instanceof ModelNotFoundException, $exception instanceof NotFoundHttpException => 404,
                $exception instanceof ThrottleRequestsException => 429,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            $message = match ($status) {
                400 => __('حدث خطأ في الطلب. يرجى المحاولة مرة أخرى.'),
                401 => __('انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى.'),
                403 => __('لا تملك صلاحية تنفيذ هذا الإجراء.'),
                404 => __('العنصر المطلوب غير موجود.'),
                419 => __('انتهت الجلسة، يرجى تحديث الصفحة والمحاولة مرة أخرى.'),
                422 => __('البيانات المرسلة غير صالحة. يرجى مراجعة الحقول.'),
                429 => __('تم إرسال طلبات كثيرة. يرجى الانتظار قليلاً.'),
                default => __('حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.'),
            };

            if ($status >= 500) {
                Log::error('API request failed.', [
                    'path' => $request->path(),
                    'user_id' => $request->user()?->id,
                    'exception' => $exception,
                ]);
            }

            $payload = [
                'message' => config('app.debug') && $status >= 500 ? $exception->getMessage() : $message,
                'status' => $status,
            ];

            if ($exception instanceof ValidationException) {
                $payload['errors'] = $exception->errors();
            }

            return response()->json($payload, $status);
        });
    })->create();
