<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPageSize
{
    public const SIZES = [10, 25, 50];

    public function handle(Request $request, Closure $next): Response
    {
        $size = $request->integer('per_page');

        if (in_array($size, self::SIZES, true)) {
            config(['web.pagination_size' => $size, 'inertia-table.per_page' => $size]);
        }

        return $next($request);
    }
}
