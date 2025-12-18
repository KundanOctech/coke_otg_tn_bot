<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class DailyCodeCount extends BaseModel
{
    public static $validCodeLimit = 5;
    public static $invalidCodeLimit = 5;

    public static function isDailyLimitOver($userData)
    {
        $dailyCodeCount = DailyCodeCount::where('user_id', $userData->id)
            ->where('created_date', date('Y-m-d'))
            ->first();
        if (!empty($dailyCodeCount)) {
            return $dailyCodeCount->valid_code_count >= self::$validCodeLimit || $dailyCodeCount->invalid_code_count >= self::$invalidCodeLimit;
        }
        return false;
    }

    public static function getInvalidCodeCount($userId)
    {
        $dailyCodeCount = DailyCodeCount::where('user_id', $userId)
            ->where('created_date', date('Y-m-d'))
            ->first();
        return !empty($dailyCodeCount) ? $dailyCodeCount->invalid_code_count : 0;
    }

    public static function checkDailyLimitOver($userData, $code)
    {
        $dailyLimitOver = self::isDailyLimitOver($userData);
        if ($dailyLimitOver) {
            UniqueCodeLog::addCodeLog($userData, $code, 0, 'daily limit exceeded');
        }

        return $dailyLimitOver;
    }

    public static function addedValidCode($userId)
    {
        $dailyCodeCount = DailyCodeCount::where('user_id', $userId)
            ->where('created_date', date('Y-m-d'))
            ->first();
        return !empty($dailyCodeCount) && $dailyCodeCount->valid_code_count >= self::$validCodeLimit;
    }

    public static function addCodeLog($userData, $code, $valid, $invalidReason)
    {
        $status = true;
        $date = date('Y-m-d');
        while ($status) {
            $dailyCodeCount = DailyCodeCount::where('user_id', $userData->id)
                ->where('created_date', $date)
                ->first();
            if (empty($dailyCodeCount)) {
                $saveData = [
                    'user_id' => $userData->id,
                    'created_date' => $date
                ];
                if ($valid) {
                    $saveData['valid_code_count'] = 1;
                } else {
                    $saveData['invalid_code_count'] = 1;
                }

                try {
                    DailyCodeCount::saveData($saveData);
                    $status = false;
                } catch (QueryException $e) {
                    ApiLog::addLog($userData->id, 'DAILY_CODE_ADD_CODE_LOG_EXP', $e, $saveData);
                }
            } else {
                if ($valid) {
                    DailyCodeCount::where('id', $dailyCodeCount->id)->increment('valid_code_count');
                } else {
                    DailyCodeCount::where('id', $dailyCodeCount->id)->increment('invalid_code_count');
                }
                $status = false;
            }
        }
        UniqueCodeLog::addCodeLog($userData, $code, $valid, $invalidReason);
    }

    public static function addValidCodeLog($userData, $code)
    {
        $status = true;
        $date = date('Y-m-d');
        $success = false;

        while ($status) {
            $dailyCodeCount = DailyCodeCount::where('user_id', $userData->id)
                ->where('created_date', $date)
                ->first();

            if (empty($dailyCodeCount)) {
                $saveData = [
                    'user_id' => $userData->id,
                    'valid_code_count' => 1,
                    'created_date' => $date
                ];

                try {
                    DailyCodeCount::saveData($saveData);
                    $success = true;
                    $status = false;
                } catch (QueryException) {
                    $status = false;
                }
            } elseif ($dailyCodeCount->valid_code_count < self::$validCodeLimit) {
                $updated = DailyCodeCount::where('id', $dailyCodeCount->id)
                    ->where('valid_code_count', '<', self::$validCodeLimit)
                    ->increment('valid_code_count');
                if ($updated) {
                    $success = true;
                    $status = false;
                }
            }
        }
        return $success;
    }
}

/**
 * ------------------------------------------------------------------------
 * DailyCodeCount
 * ------------------------------------------------------------------------
 * id
 * user_id
 * valid_code_count
 * invalid_code_count
 * created_date
 * created_at
 * updated_at
 */
