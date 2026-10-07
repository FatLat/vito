<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPageSize
{
    public function handle(Request $request, Closure $next): Response
    {
        $size = is_string($request->query('per_page')) ? $request->integer('per_page') : 0;

        if (! in_array($size, config('web.pagination_sizes'), true)) {
            return $next($request);
        }

        $default = config('web.pagination_size');
        config(['web.pagination_size' => $size]);

        try {
            return $next($request);
        } finally {
            config(['web.pagination_size' => $default]);
        }
    }
}
