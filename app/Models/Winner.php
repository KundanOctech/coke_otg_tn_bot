<?php

namespace App\Models;

use App\Constant\GenericConstant;
use Illuminate\Database\QueryException;

class Winner extends BaseModel
{

    public static function makeRoiBumperWinner($userData, $rewardName)
    {
        $output = [
            false, // isBumperWinner
            null, // rewardName
            null // winId
        ];

        $sessionData = UserSession::where('id', $userData->last_session_id)->first();
        try {
            $claimBy = date('Y-m-d H:i:s', strtotime('+72 hours'));
            $saveData = [
                'user_id' => $userData->id,
                'source' => $sessionData->traffic_source,
                'source_id' => $sessionData->id,
                'mobile' => $userData->mobile,
                'reward_type' => GenericConstant::$winTypeBumperReward,
                'reward_name' => $rewardName,
                'is_bumper_winner' => 1,
                'claim_by' => $claimBy,
                'claimed' => 0,
                'claim_expired' => 0,
                'created_date' => date('Y-m-d')
            ];

            $saveResp = Winner::saveData($saveData);
            if ($rewardName == GenericConstant::$winTypeTicket) {
                $userUpdate = [
                    'is_ticket_winner' => 1,
                    'ticket_expired' => 0,
                    'claimed_ticket' => 0
                ];
            } else {
                $userUpdate = [
                    'is_merch_winner' => 1,
                    'merch_expired' => 0,
                    'claimed_merch' => 0
                ];
            }

            User::where('id', $userData->id)->update($userUpdate);
            return [
                true, // isBumperWinner
                $rewardName, // rewardName
                $saveResp['id'] // winId
            ];
        } catch (QueryException $e) {
            ApiLog::addLog($userData->id, 'BUMPER_REWARD_MAKE_WINNER_EXP', $e, $saveData);
        }
        return $output;
    }
}

/**
 * ------------------------------------------------------------------------
 * Winner 
 * ------------------------------------------------------------------------
 * id
 * user_id
 * source
 * source_id
 * brand
 * mobile
 * unique_code
 * reward_type
 * is_bumper_winner
 * reward_code
 * reward_pin
 * reward_amount
 * reward_expire_date
 * claim_by
 * claimed
 * claimed_at
 * created_date
 * created_at
 * updated_at
 */
