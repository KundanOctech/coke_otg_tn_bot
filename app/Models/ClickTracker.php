<?php

namespace App\Models;

class ClickTracker extends BaseModel
{

    public static function trackEvent($userData, $eventType)
    {
        $saveData = [
            'user_id' => $userData->id,
            'event_type' => $eventType,
            'created_date' => date('Y-m-d')
        ];

        $userSession = UserSession::where('id', $userData->last_session_id)->first();
        if (!empty($userSession)) {
            $saveData['source'] = $userSession->traffic_source;
            $saveData['source_id'] = $userSession->id;
            $saveData['brand'] = $userSession->brand;
        }
        ClickTracker::saveData($saveData);
    }

    public static function trackEventValue($userData, $eventType, $value)
    {
        $saveData = [
            'user_id' => $userData->id,
            'event_type' => $eventType,
            'event_value' => $value,
            'created_date' => date('Y-m-d')
        ];
        $userSession = UserSession::where('id', $userData->last_session_id)->first();
        if (!empty($userSession)) {
            $saveData['source'] = $userSession->traffic_source;
            $saveData['source_id'] = $userSession->id;
            $saveData['brand'] = $userSession->brand;
        }
        ClickTracker::saveData($saveData);
    }
}
/**
 * ------------------------------------------------------------------------
 * ClickTracker
 * ------------------------------------------------------------------------
 * id
 * user_id
 * event_type
 * event_value
 * source
 * source_id
 * brand
 * created_date
 * created_at
 * updated_at
 */
