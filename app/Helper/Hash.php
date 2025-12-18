<?php

namespace App\Helper;

use App\Models\User;
use Slim\Http\Response;

class Hash
{
    private static $dataKey = "cbfc1b2a5a7430653ce8fa3f2ccb4b3b033a83e28981c15b46952aa64fc2b181";
    private static $algo = 'aes-256-gcm';

    public static function encryptData($data)
    {
        $dataBin = openssl_encrypt(
            $data,
            self::$algo,
            hex2bin(self::$dataKey),
            OPENSSL_RAW_DATA,
            User::getDataIv(),
            $dataTag
        );
        return bin2hex($dataBin) . '.' . bin2hex($dataTag);
    }

    public static function decryptData($data)
    {
        $valid = false;
        $dataBin = '';
        $dataTag = '';

        if (!empty($data)) {
            $dataPart = explode('.', $data);
            if (count($dataPart) == 2) {
                [$dataBin, $dataTag] = $dataPart;
                $valid = true;
            }
        }



        if (!$valid || empty($dataBin) || empty($dataTag)) {
            return '';
        }
        return openssl_decrypt(
            hex2bin($dataBin),
            self::$algo,
            hex2bin(self::$dataKey),
            OPENSSL_RAW_DATA,
            User::getDataIv(),
            hex2bin($dataTag)
        );
    }


    ########## Response  Methods ##########
    public static function encodeOutput(Response $res, $output)
    {
        $encOut = base64_encode(json_encode($output));
        return $res->withJson(['resp' => $encOut], $output['statusCode']);
    }

    public static function sendSuccessMessage($res, $data = [])
    {
        $output = [
            'statusCode' => 200,
            'message' => 'Success'
        ];
        if (!empty($data)) {
            $output['data'] = $data;
        }
        return self::encodeOutput($res, $output);
    }

    public static function sendErrorMessage($res, $message = 'Invalid data', $extra = [])
    {
        $output = [
            'statusCode' => 400,
            'message' => $message
        ];
        if (!empty($extra)) {
            $output = array_merge($output, $extra);
        }
        return self::encodeOutput($res, $output);
    }
}
