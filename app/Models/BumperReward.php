<?php

namespace App\Models;

use App\Constant\GenericConstant;
use Illuminate\Database\QueryException;

class BumperReward extends BaseModel
{
    private static $notWinnerResp = [
        'isWinner' => false,
        'rewardName' => null,
        'winId' => null
    ];

    public static function makeWinner($userData, $uniqueCode)
    {
        $status = true;
        $output = self::$notWinnerResp;
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();

        while ($status) {
            $wonReward = BumperReward::where('user_id', $userData->id)->exists();
            if ($wonReward) {
                return $output;
            }

            $rewardData = BumperReward::where('assigned', 0)->inRandomOrder()->first();
            if (empty($rewardData)) {
                $status = false;
                continue;
            }
            try {
                $updated = BumperReward::where('id', $rewardData->id)
                    ->where('assigned', 0)
                    ->update([
                        'assigned' => 1,
                        'user_id' => $userData->id,
                        'assigned_date' => date('Y-m-d'),
                        'assigned_at' => date('Y-m-d H:i:s')
                    ]);
                if (!$updated) {
                    continue;
                }
                $claimBy = date('Y-m-d H:i:s', strtotime('+72 hours'));
                $saveData = [
                    'user_id' => $userData->id,
                    'source' => $sessionData->traffic_source,
                    'source_id' => $sessionData->id,
                    'mobile' => $userData->mobile,
                    'unique_code' => $uniqueCode,
                    'reward_type' => GenericConstant::$winTypeBumperReward,
                    'reward_name' => $rewardData->reward_name,
                    'is_bumper_winner' => 1,
                    'claim_by' => $claimBy,
                    'claimed' => 0,
                    'claim_expired' => 0,
                    'created_date' => date('Y-m-d')
                ];

                $saveResp = Winner::saveData($saveData);

                if ($rewardData->reward_name == GenericConstant::$winTypeTicket) {
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
                $output = [
                    'isWinner' => true,
                    'rewardName' => $rewardData->reward_name,
                    'winId' => $saveResp['id']
                ];
                $status = false;
                return $output;
            } catch (QueryException $e) {
                ApiLog::addLog($userData->id, 'BUMPER_REWARD__MAKE_WINNER_EXP', $e, $saveData);
            }
        }
    }
}

/**
 * ------------------------------------------------------------------------
 * BumperReward
 * ------------------------------------------------------------------------
 * id
 * assigned
 * reward_name
 * user_id
 * assigned_date
 * assigned_at
 * opens_at
 * phase
 * carry_forward
 * created_at
 * updated_at
 * ------------------------------------------------------------------------
 * UK (user_id)
 * ------------------------------------------------------------------------
 */
