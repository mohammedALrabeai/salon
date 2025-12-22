<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiRequestId
{
    /**
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $requestId = $request->header('X-Request-ID') ?: Str::uuid()->toString();
        $request->attributes->set('request_id', $requestId);

        return $next($request);
    }
}
