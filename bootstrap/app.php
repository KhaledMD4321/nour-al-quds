<?php

use App\Http\Middleware\CheckBusinessUnit;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.unit' => CheckBusinessUnit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // تسجيل كل الاستثناءات غير المتوقعة مع سياق المستخدم والطلب
        $exceptions->report(function (Throwable $e) {
            // تجاهل الاستثناءات المعتادة (404, 403, ValidationException)
            if ($e instanceof HttpResponseException) {
                return;
            }
            if ($e instanceof HttpException) {
                return;
            }
            if ($e instanceof ValidationException) {
                return;
            }
            if ($e instanceof AuthenticationException) {
                return;
            }
            if ($e instanceof ModelNotFoundException) {
                return;
            }

            Log::error('Unhandled exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'user_id' => auth()->id(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
            ]);
        });

        // تأمين: لا تكشف التفاصيل التقنية في production
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->expectsJson()
        );

    })
    ->booted(function () {
        // حماية صفحة تسجيل الدخول: 5 محاولات كحد أقصى في الدقيقة لكل IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    })
    ->create();
