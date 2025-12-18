<?php

namespace App\Middleware;

use App\Helper\Hash;
use App\Models\User;

class UserRegisteredMiddleware
{

    public function __invoke($request, $response, $next)
    {
        $userData = $request->getAttribute('userData');

        if (empty($userData) || !$userData->registered) {
            return $response->withJson([
                "success" => false,
            ], 200);
        }
        return $next($request, $response);
    }
}
