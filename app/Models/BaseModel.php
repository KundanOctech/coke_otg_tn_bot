<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BaseModel extends Model
{

    public static function createUser($device = 'web', $values = [], $addCreatedDate = true)
    {
        $device = ($device == "mobile") ? "mobile" : "web";
        $child_name = get_called_class();
        $userKey = 0;
        $max = mt_getrandmax();
        if ($max > 4294967290) {
            $max = 4294967290;
        }
        while ($userKey == 0) {
            $userKey = mt_rand(1, $max);
            $flag = $child_name::isUnique('userKey', $userKey);
            if (!$flag) {
                $userKey = 0;
            }
        }
        if ($addCreatedDate) {
            $values['created_date'] = date('Y-m-d');
        }

        $values["userKey"] = $userKey;
        if (!(isset($values["masterKey"]) && $values["masterKey"] != "")) {
            $values["masterKey"] = $userKey;
        }
        $values["device"] = $device;
        $values["user_ip "] = $child_name::getClientIp();

        $values["created_day"] = date("l");
        $values["created_hour"] = intval(date("G"));
        $values["created_day_hour"] = date("D-G");

        $resp = $child_name::saveData($values);
        if ($resp["statusCode"] == 200) {
            return $userKey;
        } else {
            return 0;
        }
    }

    public static function saveData($data = [], $addCreatedDate = false)
    {
        if (is_array($data) && !empty($data)) {
            $child_name = get_called_class();
            $obj = new $child_name();

            foreach ($data as $key => $val) {
                $encoded_key = $child_name::htmlEncode($key);
                $obj->$encoded_key = $child_name::htmlEncode($val);
            }
            if ($addCreatedDate) {
                $data['created_date'] = date('Y-m-d');
            }
            if ($obj->save()) {
                return ["statusCode" => 200, "message" => "Data saved", "id" => $obj->id];
            } else {
                return ["statusCode" => 401, "message" => "Invalid data format"];
            }
        }
        return ["statusCode" => 400, "message" => "Invalid data format"];
    }

    public static function isUnique($col_name = "", $value = "")
    {
        $child_name = get_called_class();

        $col_name = $child_name::htmlEncode($col_name);
        $value = $child_name::htmlEncode($value);

        if ($col_name == '' || $value == '') {
            return false;
        }

        $count = $child_name::where($col_name, $value)->count();
        if ($count == 0) {
            return true;
        }
        return false;
    }

    public static function htmlEncode($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[htmlspecialchars(trim($key), ENT_QUOTES, 'UTF-8')] =
                    htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
            }
            return $data;
        } else {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }

    public static function getClientIp()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    public static function isEmail($email = "")
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
    }

    public static function isPhoneNumber($phoneNumber)
    {
        return boolval(preg_match('/^[6789][0-9]{9}$/', $phoneNumber));
    }

    public static function isPinCodeNumber($pinCode)
    {
        return boolval(preg_match('/^[1-9][0-9]{5}$/', $pinCode));
    }

    public static function isNumber($number = 0, $minLen = 1, $maxLen = 50)
    {
        return boolval(preg_match('/^[0-9]{' . $minLen . ',' . $maxLen . '}$/', $number));
    }

    public static function getData($list = [], $key = "")
    {
        if (is_array($list) && isset($list[$key])) {
            return trim($list[$key]);
        }
        return "";
    }

    public static function getToken($min = 8, $max = 8, $possible = '')
    {
        if ($possible == '') {
            $possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        }
        if ($min == $max) {
            $l = $min;
        } else {
            $l = mt_rand($min, $max);
        }
        $str = "";
        for ($i = 0; $i < $l; $i++) {
            $k = mt_rand(0, (strlen($possible) - 1));
            $str .= $possible[$k];
        }
        return $str;
    }

    public static function getUuid4Key()
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public static function checkValidData($date, $separator = '-', $yearOrder = 0, $monthOrder = 1, $dayOrder = 2)
    {
        $dateList = explode($separator, $date);
        if (count($dateList) < 3) {
            return false;
        }

        $year = intval($dateList[$yearOrder]);
        $month = intval($dateList[$monthOrder]);
        $day = intval($dateList[$dayOrder]);
        return checkdate($month, $day, $year);
    }
}
