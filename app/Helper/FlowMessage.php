<?php

namespace App\Helper;

use App\Constant\WaMessage;

class FlowMessage
{
    public static function getLocalMessage($languageCode, $messagelist)
    {
        return array_key_exists($languageCode, $messagelist) && !empty($messagelist[$languageCode]) ? $messagelist[$languageCode] : $messagelist['en'];
    }
}
/** ----------------------------------------------------------------
 * Language
 * ----------------------------------------------------------------
 * en: English
 * hi: Hindi
 * te: Telugu
 * mr: Marathi
 * bn: Bengali
 * gu: Gujarati
 * kn: Kannada
 * or: Odia
 * ----------------------------------------------------------------
 */
