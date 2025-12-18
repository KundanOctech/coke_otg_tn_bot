<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class MessageCount extends BaseModel
{

    public static function addMessageCount($userData, $messageType, $messageCount)
    {
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();

        $saveData = [
            'message_type' => $messageType,
            'message_count' => $messageCount,
            'user_id' => $userData->id,
            'created_date' => date('Y-m-d')
        ];
        if (!empty($sessionData)) {
            $saveData['source'] = $sessionData->traffic_source;
            $saveData['source_id'] = $sessionData->id;
        }
        try {
            MessageCount::saveData($saveData, true);
        } catch (QueryException) {
        }
    }

    public static function addTemplateMessageCount($userData, $messageType, $messageId, $templateId)
    {
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();
        $saveData = [
            'message_type' => $messageType,
            'message_count' => 1,
            'user_id' => $userData->id,
            'message_id' => $messageId,
            'template_id' => $templateId,
            'created_date' => date('Y-m-d')
        ];
        if (!empty($sessionData)) {
            $saveData['source'] = $sessionData->traffic_source;
            $saveData['source_id'] = $sessionData->id;
            $saveData['brand'] = $sessionData->brand;
        }
        try {
            MessageCount::saveData($saveData, true);
        } catch (QueryException) {
        }
    }
}
/**
 * ------------------------------------------------------------------------
 * MessageCount  (coke_otg_message_counts)
 * ------------------------------------------------------------------------
 * id
 * message_type
 * source
 * source_id
 * brand
 * message_count
 * user_id
 * message_id
 * template_id
 * created_date
 * created_at
 * updated_at
 */
