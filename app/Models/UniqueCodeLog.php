<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class UniqueCodeLog extends BaseModel
{
    public static function addCodeLog($userData, $code, $valid = 1, $invalidReason = '')
    {
        try {
            $saveData = [
                'code' => substr($code, 0, 20),
                'user_id' => $userData->id,
                'valid' => $valid,
                'invalid_reason' => $invalidReason,
                'created_date' => date('Y-m-d')
            ];
            UniqueCodeLog::saveData($saveData);
        } catch (QueryException) {
        }
    }
}
