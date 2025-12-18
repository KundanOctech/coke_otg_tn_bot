<?php

namespace App\Helper;

use App\Constant\FlowImage;

class BannerImageHelper
{
    public static function getIcon($path)
    {
        $data = file_get_contents($path);
        return base64_encode($data);
    }

    public static function getWarningIcon()
    {
        $path = __DIR__ . '/../assets/warning-icon.png';
        return self::getIcon($path);
    }

    public static function getKvBannerImage($languageCode)
    {
        $imageList = FlowImage::$kvImage;
        $image = $imageList[$languageCode] ?? $imageList['en'];
        $path = __DIR__ . '/../assets/' . $image;
        return self::getIcon($path);
    }

    public static function getHtpImage($languageCode)
    {
        $imageList = FlowImage::$htpImage;
        $image = $imageList[$languageCode] ?? $imageList['en'];
        $path = __DIR__ . '/../assets/' . $image;
        return self::getIcon($path);
    }

    public static function getPhonepeImage($languageCode)
    {
        $imageList = FlowImage::$phonepeImage;
        $image = $imageList[$languageCode] ?? $imageList['en'];
        $path = __DIR__ . '/../assets/' . $image;
        return self::getIcon($path);
    }
    public static function getMatchTicketImage($languageCode)
    {
        $imageList = FlowImage::$matchTicketImage;
        $image = $imageList[$languageCode] ?? $imageList['en'];
        $path = __DIR__ . '/../assets/' . $image;
        return self::getIcon($path);
    }
    public static function getMerchImage($languageCode)
    {
        $imageList = FlowImage::$merchImage;
        $image = $imageList[$languageCode] ?? $imageList['en'];
        $path = __DIR__ . '/../assets/' . $image;
        return self::getIcon($path);
    }
}
