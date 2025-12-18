<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Capsule\Manager as DB;

class UserSession extends BaseModel
{
    public static function addUserSession($userData, $trafficSourceDetails, $newUserSession)
    {
        try {
            $date = date('Y-m-d');
            $count = UserSession::where('user_id', $userData->id)
                ->where('created_date', $date)
                ->count();
            $firstsSessionOfDay = $count ? 0 : 1;

            $saveData = [
                'user_id' => $userData->id,
                'first_session_of_day' => $firstsSessionOfDay,
                'traffic_source' => $trafficSourceDetails['source'],
                'session_duration' => 5,
                'last_action_at' => date('Y-m-d H:i:s', strtotime("+5 seconds")),
                'created_date' => $date
            ];
            if ($trafficSourceDetails['brand']) {
                $saveData['brand'] = $trafficSourceDetails['brand'];
            }
            if (!is_null($newUserSession)) {
                $saveData['is_new_user'] = $newUserSession;
            }

            $saveResp = UserSession::saveData($saveData, true);
            User::where('id', $userData->id)->update(['last_session_id' => $saveResp['id']]);
        } catch (QueryException $e) {
            // Handle the exception as needed, e.g., log it or return an error response
            echo json_encode($e);
            die();
        }
    }

    public static function updateUserSession($sessionId)
    {
        $sessionData = UserSession::where('id', $sessionId)->first();
        $lastActionAt = strtotime($sessionData->last_action_at);
        $now = strtotime('now');
        if ($now > $lastActionAt) {
            $dif = $now - $lastActionAt;
            if ($dif > 45) {
                $dif = 45;
            }
            $update = [
                'last_action_at'  => date('Y-m-d H:i:s'),
                'session_duration' => DB::raw('session_duration + ' . $dif)
            ];
            try {
                UserSession::where('id', $sessionId)->update($update);
            } catch (QueryException) {
            }
        }
    }
}


/**
 * ------------------------------------------------------------------------
 * UserSession (coke_otg_user_sessions)
 * ------------------------------------------------------------------------
 * id
 * user_id
 * first_session_of_day
 * traffic_source
 * brand
 * session_duration
 * last_action_at
 * created_date
 * created_at
 * updated_at
 */
