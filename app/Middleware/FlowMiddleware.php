<?php

namespace App\Middleware;

use App\Helper\FlowHash;

class FlowMiddleware
{
    public function __invoke($request, $response, $next)
    {
        $input = $request->getParsedBody();
        $encryptedFlowData = $input['encrypted_flow_data'] ?? null;
        $encryptedAesKey = $input['encrypted_aes_key'] ?? null;
        $initialVector = $input['initial_vector'] ?? null;

        $obj = new FlowHash();
        $resp = $obj->decryptRequest($encryptedAesKey, $encryptedFlowData, $initialVector);
        if (!$resp) {
            return '';
        }

        $inputBody = $resp['decryptedBody'] ?? [];
        $aesKey = bin2hex($resp['aesKeyBuffer']) ?? null;
        $initialVector = bin2hex($resp['initialVectorBuffer']) ?? null;

        $request = $request->withAttribute('inputBody', $inputBody);
        $request = $request->withAttribute('aesKey', $aesKey);
        $request = $request->withAttribute('initialVector', $initialVector);

        return $next($request, $response);
    }
}
