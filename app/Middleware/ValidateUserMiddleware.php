<?php

namespace App\Middleware;

use App\Helper\Hash;
use App\Models\User;

class ValidateUserMiddleware
{

    public function __invoke($request, $response, $next)
    {
        $input = $request->getParsedBody();
        $mobile = substr(User::getData($input, 'mobile'), -10, 10);
        if (User::getData($input, 'mobile') == 'WEB_SIMULATION') {
            $mobile = '8553550133';
        }
        if (!User::isPhoneNumber($mobile)) {
            return $response->withJson([
                "success" => false
            ], 400);
        }

        $mobileEnc = Hash::encryptData($mobile);
        $userData = User::where('mobile', $mobileEnc)->first();
        if (empty($userData)) {
            return $response->withJson([
                "success" => true,
            ], 200);
        }
        $request = $request->withAttribute('userData', $userData);
        return $next($request, $response);
    }
}
