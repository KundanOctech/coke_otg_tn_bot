<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager as DB;

class MassRewardQuota extends BaseModel
{
    public static $rewardOrder = [10, 5];

    public static function canBeWinner($amount)
    {
        $today = date('Y-m-d');
        $rewardData = MassRewardQuota::where('reward_date', $today)
            ->where('amount', $amount)
            ->first();
        if (!empty($rewardData)) {
            $updated = MassRewardQuota::where('id', $rewardData->id)
                ->where('winner_count', '<', $rewardData->reward_quota + $rewardData->carry_forward)
                ->update([
                    'winner_count' => DB::raw('winner_count + 1')
                ]);
            return boolval($updated);
        }

        return false;
    }
}

/**
 * ------------------------------------------------------------------------
 * MassRewardQuota
 * ------------------------------------------------------------------------
 * id
 * reward_date
 * reward_hour
 * amount
 * region
 * reward_quota
 * carry_forward
 * winner_count
 * created_at
 * updated_at
 * ------------------------------------------------------------------------
 * UK -> reward_date + reward_hour
 * ------------------------------------------------------------------------
 */
