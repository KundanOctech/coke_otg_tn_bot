<?php

namespace App\Models;

use App\Constant\GenericConstant;
use Illuminate\Database\QueryException;

class MassRewardCode extends BaseModel
{
    public static $massRewardLimit = 4;

    private static $notWinnerResp = [
        'isWinner' => false,
        'code' => null,
        'pin' => null,
        'rewardName' => null,
        'redeemBy' => null,
        'wid' => null,
        'errorID' => null,
    ];

    public static function wonToday(User $userData)
    {
        $today = date('Y-m-d');
        $key = $userData->id . '_' . $today;
        $winnerData = MassRewardCode::where('user_cashback_key', $key)->first();
        return !empty($winnerData);
    }

    public static function assignRewardCode($userData, $rewardAmount, $uniqueCode)
    {
        $status = true;
        $output = self::$notWinnerResp;
        $today = date('Y-m-d');
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();
        $saveData = [];
        while ($status) {
            $rewardCount = MassRewardCode::where('user_id', $userData->id)->count();

            if ($rewardCount >= self::$massRewardLimit) {
                $output['errorID'] = 'MAX_LIMIT_EXCEED';
                $status = false;
                continue;
            }
            if (MassRewardCode::wonToday($userData)) {
                $output['errorID'] = 'WON_TODAY';
                $status = false;
                continue;
            }

            $userRewardKey = $userData->id . '_MASS_' . ($rewardCount + 1);
            $userCashbackKey = $userData->id . '_' . $today;

            $reward = MassRewardCode::where('amount', $rewardAmount)
                ->where('assigned', 0)
                ->orderBy('id')
                ->first();
            if (empty($reward)) {
                ApiLog::addLog($userData->id, 'MASS_REWARD_CODE_OVER', [], ['amount' => $rewardAmount]);
                $status = false;
                continue;
            }
            try {
                $updated = MassRewardCode::where('id', $reward->id)
                    ->where('assigned', 0)
                    ->update([
                        'assigned' => 1,
                        'user_id' => $userData->id,
                        'user_reward_key' => $userRewardKey,
                        'user_cashback_key' => $userCashbackKey,
                        'assigned_date' => $today,
                        'assigned_at' => date('Y-m-d H:i:s'),
                    ]);
                if ($updated) {
                    $rewardName = 'PhonePe_' . $reward->amount;
                    $saveData = [
                        'user_id' => $userData->id,
                        'source' => $sessionData->traffic_source,
                        'source_id' => $sessionData->id,
                        'brand' => $sessionData->brand,
                        'mobile' => $userData->mobile,
                        'unique_code' => $uniqueCode,
                        'reward_type' => GenericConstant::$winTypeMassReward,
                        'reward_name' => $rewardName,
                        'reward_code' => $reward->reward_code,
                        'reward_pin' => $reward->reward_pin,
                        'reward_expire_date' => $reward->redeem_by,
                        'created_date' => date('Y-m-d')
                    ];
                    $winnerResp = Winner::saveData($saveData);
                    User::where('id', $userData->id)->increment('cashback_count');

                    $output = [
                        'isWinner' => true,
                        'code' => $reward->reward_code,
                        'pin' => $reward->reward_pin,
                        'rewardName' => $reward->amount,
                        'redeemBy' => date('d-m-Y', strtotime($reward->redeem_by)),
                        'win' => $winnerResp['id'] ?? null,
                        'errorID' => null
                    ];
                    $status = false;
                }
            } catch (QueryException $e) {
                // echo json_encode($e);
                // exit(1);
                ApiLog::addLog($userData->id, 'MASS_REWARD_MAKE_WINNER_EXP', $e, $saveData);
            }
        }
        return $output;
    }
}

/**
 * ------------------------------------------------------------------------
 * MassRewardCode
 * ------------------------------------------------------------------------
 * id
 * reward_code
 * reward_pin
 * redeem_by
 * amount
 * assigned
 * user_id
 * user_reward_key
 * assigned_date
 * assigned_at
 * created_at
 * updated_at
 * ------------------------------------------------------------------------
 * UK -> reward_code
 * UK -> user_reward_key
 * INDEX -> amount + assigned
 * INDEX -> user_id
 * ------------------------------------------------------------------------
 */
