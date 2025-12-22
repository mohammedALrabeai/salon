<?php

use App\Exceptions\PermissionDeniedException;
use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\ApiRequestId::class,
        ]);

        $middleware->alias([
            'auth.api' => \App\Http\Middleware\ApiTokenAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            return ApiResponse::validationError($exception);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('TOKEN_INVALID', 'الرمز غير صالح', 401);
        });

        $exceptions->render(function (PermissionDeniedException $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('PERMISSION_DENIED', 'ليس لديك صلاحية لتنفيذ هذا الإجراء', 403, [
                'required_permission' => $exception->permission(),
                'user_role' => $exception->role(),
            ]);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('PERMISSION_DENIED', 'ليس لديك صلاحية لتنفيذ هذا الإجراء', 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('RESOURCE_NOT_FOUND', 'المورد غير موجود', 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            return ApiResponse::error('RESOURCE_NOT_FOUND', 'المورد غير موجود', 404);
        });

        $exceptions->render(function (QueryException $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            $sqlState = $exception->errorInfo[0] ?? null;
            $driverCode = $exception->errorInfo[1] ?? null;

            if ($sqlState === '23505' || $driverCode === 1062) {
                return ApiResponse::error('DUPLICATE_ENTRY', 'قيمة مكررة', 409);
            }

            return null;
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! isApiRequest($request)) {
                return null;
            }

            if ($exception instanceof TooManyRequestsHttpException) {
                return ApiResponse::error('RATE_LIMIT_EXCEEDED', 'لقد تجاوزت الحد المسموح من الطلبات', 429, [
                    'retry_after' => $exception->getHeaders()['Retry-After'] ?? null,
                ]);
            }

            return ApiResponse::error('ERROR', 'حدث خطأ غير متوقع', $exception->getStatusCode());
        });
    })->create();

if (! function_exists('isApiRequest')) {
    function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || str_starts_with($request->path(), 'api/');
    }
}
