<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PermissionDeniedException;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function paginated(LengthAwarePaginator $paginator, array $items, array $meta = [], array $extra = []): JsonResponse
    {
        return ApiResponse::paginated($paginator, $items, $meta, $extra);
    }

    protected function error(string $code, string $message, int $status = 400, array $details = []): JsonResponse
    {
        return ApiResponse::error($code, $message, $status, $details);
    }

    protected function requirePermission(string $permission): void
    {
        $user = request()->user();

        if (! $user || ! $user->can($permission)) {
            throw new PermissionDeniedException($permission, $user?->role);
        }
    }

    protected function perPage(): int
    {
        $perPage = (int) request()->query('per_page', 20);

        if ($perPage < 1) {
            return 20;
        }

        return min($perPage, 100);
    }
}
