<?php

namespace App\Models;

use App\Constant\GenericConstant;
use App\Helper\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Database\Capsule\Manager as DB;

class ClaimForm extends BaseModel
{

    public static function emailExist($email)
    {
        return ClaimForm::where('email', $email)->exists();
    }

    public static function panNumberExist($panNumber)
    {
        return ClaimForm::where('pan_no', $panNumber)->exists();
    }

    public static function isValidPanNumber($panNumber)
    {
        return preg_match('/^[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}$/', $panNumber);
    }

    public static function claim(User $userData, $data, Winner $winnerData)
    {
        try {
            $saveData = [
                'user_id' => $userData->id,
                'source' => $winnerData->source,
                'source_id' => $winnerData->source_id,
                'mobile' => $userData->mobile,
                'user_name' => $userData->name,
                'registered_at' => date('Y-m-d H:i:s', strtotime($userData->created_at)),
                'won_at' => date('Y-m-d H:i:s', strtotime($winnerData->created_at)),
                'reward_name' => $winnerData->reward_name,
                'name' => Hash::encryptData($data['name']),
                'email' => Hash::encryptData($data['email']),
                'pincode' => $data['pincode'],
                'state' => $data['state'],
                'city' => $data['city'],
                'created_date' => date('Y-m-d'),
            ];
            if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
                $saveData['ticket_city'] = $userData->ticket_city;
            }
            if ($data['address_line_1']) {
                $saveData['address_1'] = $data['address_line_1'];
            }
            if ($data['address_line_2']) {
                $saveData['address_2'] = $data['address_line_2'];
            }
            if ($data['landmark']) {
                $saveData['landmark'] = $data['landmark'];
            }
            return ClaimForm::saveData($saveData);
        } catch (QueryException $e) {
            ApiLog::addLog($userData->id, 'CLAIM_FORM_CLAIM_EXP', $e, $saveData);
            return false;
        }
        return true;
    }
}


/**
 * ------------------------------------------------------------------------
 * ClaimForm
 * ------------------------------------------------------------------------
 * id
 * user_id
 * source
 * source_id
 * mobile
 * user_name
 * registered_at
 * won_at
 * reward_name
 * name
 * email
 * address_1
 * address_2
 * pincode
 * state
 * city
 * landmark
 * created_date
 * created_at
 * updated_at
 */
