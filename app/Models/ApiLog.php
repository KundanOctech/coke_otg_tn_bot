<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class ApiLog extends BaseModel
{

    public static function addLog($userId, $apiType, $resp, $input = [])
    {
        $saveData = [
            'user_id' =>  $userId,
            'api_type' => $apiType,
            'resp' => substr(json_encode($resp), 0, 2000),
            'created_date' => date('Y-m-d'),
        ];
        if (!empty($input)) {
            $saveData['req_input'] = substr(json_encode($input), 0, 2000);
        }
        try {
            ApiLog::saveData($saveData);
        } catch (QueryException $e) {
            ApiLog::addLog($userId, substr($apiType, 0, 40) . '_EXP', $e, $input);
        }
    }
}
/**
 * ------------------------------------------------------------------------
 * Message  (coke_otg_api_logs)
 * ------------------------------------------------------------------------
 * id
 * user_id
 * api_type
 * req_input
 * resp
 * created_at
 * updated_at
 */
