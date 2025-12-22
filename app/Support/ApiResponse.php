<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        $payload['meta'] = array_merge(self::baseMeta(), $meta);

        return response()->json($payload, $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, array $items, array $meta = [], array $extra = []): JsonResponse
    {
        $payload = array_merge([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
            'meta' => array_merge(self::baseMeta(), $meta),
        ], $extra);

        return response()->json($payload);
    }

    public static function error(string $code, string $message, int $status = 400, array $details = []): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if (! empty($details)) {
            $error['details'] = $details;
        }

        $payload = [
            'success' => false,
            'error' => $error,
            'meta' => self::baseMeta(),
        ];

        return response()->json($payload, $status);
    }

    public static function validationError(ValidationException $exception, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'البيانات المدخلة غير صحيحة',
                'validation_errors' => $exception->errors(),
            ],
            'meta' => self::baseMeta(),
        ], $status);
    }

    private static function baseMeta(): array
    {
        /** @var Request $request */
        $request = request();

        $requestId = $request->attributes->get('request_id');

        if (! $requestId) {
            $requestId = $request->header('X-Request-ID');
        }

        return array_filter([
            'timestamp' => now()->toIso8601String(),
            'request_id' => $requestId,
            'version' => config('api.version', '1.0.0'),
        ]);
    }
}
