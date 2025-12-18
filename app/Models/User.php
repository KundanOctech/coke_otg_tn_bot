<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class User extends BaseModel
{
    public static $referralRuns = 5;
    public static $refereeRuns = 10;
    public static $uniqueCodeRuns = 25;

    public static function getDataIv()
    {
        return hex2bin("3d4dd122ff23c042492acdc1");
    }

    public static function getLanguage($language)
    {
        return $language == 'ta' ? $language : 'en';
    }

    public static function addUserAuthKey($userId)
    {
        $authKey = '';
        $possibleLetters = 'ACDEFGHJKLMNPQRTUVWXY34679';
        do {
            $authKey = strtoupper(User::getToken(6, 6, $possibleLetters));
            try {
                User::where('id', $userId)->update([
                    'auth_key' => $authKey
                ]);
            } catch (QueryException) {
                $authKey = '';
            }
        } while (empty($authKey));
        return $authKey;
    }
    public static function wonAllMassReward($userData)
    {
        return $userData->cashback_count >= MassRewardCode::$massRewardLimit;
    }

    public static function wonBumperReward($userData)
    {
        return $userData->is_ticket_winner ||
            $userData->is_merch_winner;
    }

    public static function everWonBumperReward($userData)
    {
        return $userData->is_ticket_winner ||
            $userData->ticket_expired ||
            $userData->is_merch_winner ||
            $userData->merch_expired;
    }

    public static function wonAllReward($userData)
    {
        return User::wonAllMassReward($userData) && User::wonBumperReward($userData);
    }
}

/**
 * ------------------------------------------------------------------------
 * User
 * ------------------------------------------------------------------------
 * id
 * auth_key
 * registered
 * mobile
 * language
 * name
 * source
 * brand
 * unique_code_count
 * valid_code_count
 * total_answer_count
 * correct_answer_count
 * is_bumper_winner
 * bumper_win_type
 * claimed_bumper
 * is_bumper_expired
 * cashback_count
 * cashback_amount
 * referred_by
 * referral_count
 * repeat_visitor
 * opt_out
 * last_session_id
 * last_source
 * last_unique_code
 * can_win_bumper_reward
 * registration_date
 * registered_at
 * created_date
 * created_at
 * updated_at
 */
