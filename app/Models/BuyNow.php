<?php

namespace App\Models;

use Illuminate\Database\QueryException;

class BuyNow extends BaseModel
{
    protected $table = 'buy_now';

    public static function getList($brand, $language)
    {
        $buyNowList = BuyNow::where('brand', $brand)
            ->where('language', $language)
            ->where('is_active', 1)
            ->orderBy('card_order', 'asc')
            ->get();

        if (empty($buyNowList) && $language != 'en') {
            $buyNowList = BuyNow::where('brand', $brand)
                ->where('language', 'en')
                ->where('is_active', 1)
                ->orderBy('card_order', 'asc')
                ->get();
        }


        return $buyNowList;
    }
}
/**
 * ------------------------------------------------------------------------
 * BuyNow
 * ------------------------------------------------------------------------
 * id
 * brand
 * language
 * retailer_name
 * url
 * is_active
 * created_at
 * updated_at
 */
