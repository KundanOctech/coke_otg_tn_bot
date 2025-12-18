<?php

namespace App\Models;

class PinCode extends BaseModel
{
    public static function isValidPinCode($pinCode)
    {
        $valid = false;
        $state = '';
        $city = '';

        if (preg_match('/^[1-9]{1}\d{5}$/', $pinCode)) {
            $pinCodData = PinCode::where('pin_code', $pinCode)->first();
            if (!empty($pinCodData)) {
                $valid = true;
                $state = $pinCodData->state_name;
                $city = ucfirst(strtolower($pinCodData->city));
            }
        }
        return [$valid, $state, $city];
    }
}
