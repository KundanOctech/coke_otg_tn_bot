<?php

namespace App\Controllers;

use App\Constant\Profanity;
use App\Models\CdpDetails;

use App\Helper\Hash;

class Controller
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    protected function getData($list = [], $key = "", $trim = true)
    {
        if (is_array($list) && isset($list[$key])) {
            return $trim ? trim($list[$key]) : $list[$key];
        }
        return "";
    }

    protected function pushToCDP($postData, $userData, $addDefaultBrand = true, $eventType = '')
    {
        $postData["client_id"] = $this->container->CDS_CLIENT_ID;
        if (!array_key_exists('event_type', $postData)) {
            $postData['event_type'] = 'Click';
        }
        $postData = CdpDetails::addBrand($postData, $userData, $addDefaultBrand);

        $mobileNo = Hash::decryptData($userData->mobile);
        $postData['phone_e164'] = '+91' . $mobileNo;

        if (empty($eventType)) {
            $eventType = $postData['event_sub_type'];
        }

        $saveData = [
            'mobile' => $userData->mobile,
            'payload' => json_encode($postData),
            'event_type' => $eventType,
            'created_date' => date('Y-m-d')
        ];
        CdpDetails::saveData($saveData);
    }

    protected function getTodayDate()
    {
        return date('Y-m-d');
    }

    protected function containsProfanity($text)
    {
        $text = strtolower($text);
        $textList = preg_split("/[-\s:.?_,]/", $text);
        foreach (Profanity::$profanityList as $profanity) {
            if (in_array($profanity, $textList)) {
                return true;
            }
        }
        return false;
    }

    protected function getCurrentHour()
    {
        return intval(date('G'));
        // return 15;
    }

    protected function outsideTimeWindow()
    {
        $hour = $this->getCurrentHour();
        return $hour < 10 ||  $hour >= 22;
    }
    protected function removeEmoji($text)
    {
        return trim(substr(preg_replace('/\\\\u[a-zA-Z0-9]{4}/', '', json_encode($text)), 1, -1));
    }
}
