<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class UniqueCode extends BaseModel
{
    public static function isValidUniqueCodeFormat($code)
    {
        return boolval(preg_match('/^[A-Z0-9]{10,11}$/', $code));
    }

    public static function codeExist($code)
    {
        return UniqueCode::where('code', $code)->exists();
    }

    public static function actualCodeExist($actualCode)
    {
        return UniqueCode::where('actual_code', $actualCode)->exists();
    }

    public static function addCode($userData, $code, $actualCode)
    {
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();
        try {
            $saveData = [
                'code' => $code,
                'actual_code' => $actualCode,
                'code_type' => 'UNIQUE_CODE',
                'user_id' => $userData->id,
                'source' => $sessionData->traffic_source,
                'source_id' => $sessionData->id,
                'created_date' => date('Y-m-d')
            ];
            if ($sessionData->brand) {
                $saveData['brand'] = $sessionData->brand;
            }

            UniqueCode::saveData($saveData);
            return true;
        } catch (QueryException $e) {
            ApiLog::addLog($userData->id, 'ADD_UNIQUE_CODE_EXP', $e, $saveData);
            return false;
        }
    }
}
/**
 * ------------------------------------------------------------------------
 * User
 * ------------------------------------------------------------------------
 * code
 * actual_code
 * user_id
 * source
 * source_id
 * brand
 * question_id
 * answered_correct
 * created_date
 * created_at
 * updated_at
 */
