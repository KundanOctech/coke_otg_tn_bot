<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class FlowToken extends BaseModel
{

    // Method which takes user id and token type as parameters and returns the flow token
    public static function getFlowToken($userId, $flowTokenType, $addNudge = true)
    {
        $status = true;
        $nudgeAt = FlowToken::getNudgeTime($flowTokenType);

        while ($status) {
            $token = FlowToken::getUuid4Key();
            $existingToken = FlowToken::where('flow_token', $token)->first();
            if (empty($existingToken)) {
                try {
                    $saveData = [
                        'flow_token' => $token,
                        'user_id' => $userId,
                        'flow_token_type' => $flowTokenType,
                        'token_status' => '',
                        'created_date' => date('Y-m-d'),
                        'expires_at' => self::getExpireTime(),
                    ];
                    if ($addNudge) {
                        $saveData['nudge_at'] = $nudgeAt;
                    }
                    FlowToken::saveData($saveData);
                    $status = false;
                    User::where('id', $userId)
                        ->update([
                            'flow_token' => $token
                        ]);
                    return $token;
                } catch (QueryException) {
                }
            }
        }
    }

    public static function getNudgeTime($flowTokenType = '')
    {
        return date('Y-m-d H:i:s', strtotime('+5 minutes'));
    }

    private static function getExpireTime()
    {
        return date('Y-m-d H:i:s', strtotime('+20 minutes'));
    }
}
/**
 * ------------------------------------------------------------------------
 * FlowToken
 * ------------------------------------------------------------------------
 * flow_token
 * user_id
 * flow_token_type
 * token_status
 * data_id
 * expires_at
 * created_date
 * created_at
 * updated_at
 */
