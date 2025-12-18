<?php

namespace App\Middleware;


class AuthMiddleware
{
    public function __invoke($request, $response, $next)
    {
        if ($request->getHeaderLine("X-API-Key") == 'XZ2Vm4rFjaNwgc6vegCVPj8ttQnb0f1u9lEm8hTc9QQFjUCHTc') {
            return $next($request, $response);
        }

        return $response->withJson([
            "statusCode" => 401,
            "message" => "Unauthorized",
        ], 401);
    }
}
