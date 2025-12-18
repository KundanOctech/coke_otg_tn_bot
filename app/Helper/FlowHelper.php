<?php

namespace App\Helper;

class FlowHelper
{

    // if flow token is empty
    public static function getEmptyTokenResponse()
    {
        $result = [
            'extension_message_response' => [
                'params' => [
                    'flow_token' => '',
                ]
            ]
        ];
        $screen = 'SUCCESS';
        return [$screen, $result];
    }

    // if flow token is expired
    public static function getExpiredTokenResponse($flowToken)
    {
        $result = [
            'extension_message_response' => [
                'params' => [
                    'flow_token' => $flowToken,
                ]
            ]
        ];
        $screen = 'SUCCESS';
        return [$screen, $result];
    }

    // if flow token is expired
    public static function getBaseTokenResponse($flowToken)
    {
        return [
            'flow_token' => $flowToken,
        ];
    }
}
