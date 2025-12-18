<?php

namespace App\Middleware;


class DashboardAuthMiddleware
{
    public function __invoke($request, $response, $next)
    {
        if ($request->getHeaderLine("X-API-Key") == 'ScJ82Go78VXRFUyB7EfohIJluovdqfLGtnakQBkIT5qPaNR6n5') {
            return $next($request, $response);
        }

        return $response->withJson([
            "statusCode" => 401,
            "message" => "Unauthorized",
        ], 401);
    }
}
