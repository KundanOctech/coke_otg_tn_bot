<?php

namespace App\Models;

class CdpDetails extends BaseModel
{

    public static function addBrand($payload, $userData, $addDefaultBrand = true)
    {
        if (array_key_exists('brand_name', $payload)) {
            return $payload;
        }
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();
        if (!empty($sessionData) && $sessionData->brand) {
            $payload['brand_name'] = CdpDetails::getBrandName($sessionData->brand);
        }
        if (!array_key_exists('brand_name', $payload) && $addDefaultBrand) {
            $payload['brand_name'] = CdpDetails::getBrandName('COCA-COLA');
        }

        return $payload;
    }

    public static function getBrandName($brand)
    {
        // ["Fanta", "Thums Up", "Sprite", "Coca-Cola", "Limca"]
        return match (strtoupper($brand)) {
            'FANTA' => 'Fanta',
            'THUMS UP' => 'Thums Up',
            'SPRITE' => 'Sprite',
            'COCA-COLA' => 'Coca-Cola',
            'LIMCA' => 'Limca',
            default => '',
        };
    }
}

/**
 * ------------------------------------------------------------------------
 * CdpDetails
 * ------------------------------------------------------------------------
 * id
 * mobile
 * event_type
 * payload
 * api_status
 * api_result
 * created_date
 * created_at
 * updated_at
 * data_pushed
 * ------------------------------------------------------------------------
 * UK (user_id)
 * ------------------------------------------------------------------------
 */
