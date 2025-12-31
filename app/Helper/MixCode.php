<?php

namespace App\Helper;

use App\Models\ApiLog;
use App\Models\User;

class MixCode
{
    private $container;
    private $mixcodeBaseUrl;
    private $programId;
    private $programSecret;
    private $devMode = false;

    public function __construct($container)
    {
        $this->container = $container;
        $this->mixcodeBaseUrl = $container->MIXCODE_BASE_URL;

        $this->programId = $container->MIXCODE_PROGRAM_ID;
        $this->programSecret = $container->MIXCODE_SECRET_KEY;
    }

    public function getTransactionId($mobileNo)
    {
        return User::getToken(7, 9) . "-" .
            strtotime('now') . "-" . substr($mobileNo, -5);
    }

    public function getCodeDetails($code, $mobileNo, $transactionId)
    {
        if (!$this->devMode) {
            $url = $this->mixcodeBaseUrl . '/pcservices/rest/v7/pincodes/' .
                $code . '?programId=' . $this->programId .
                '&consumerId=' . '91' . $mobileNo . '&transactionId=' . $transactionId;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . $this->programSecret
            ]);
            $result = curl_exec($ch);
            $success = false;
            $codeDetails = null;

            if ($result !== false) {
                $codeDetails  = json_decode($result, true);
                $success = true;
            }

            return [$success, $codeDetails];
        }

        return [true, [
            "programId" => "453560",
            "programName" => "Coke Food Loot Promo 2024 ROI TEST",
            "programStartDate" => 1707782400000,
            "programEndDate" => 1725148740000,
            "codeLength" => 10,
            "maxCodes" => 100000000,
            "code" => $code,
            "consumerId" => "91900800812",
            "validAttempts" => 0,
            "invalidAttempts" => 0,
            "index" => 1,
            "lot" => [
                "lotId" => "446574",
                "lotName" => "Lot 1",
                "activateDate" => 1707782400000,
                "inactivateDate" => 1725148740000,
                "active" => true,
                "expired" => false,
                "bevProdPkg" => [
                    "trademarkCd" => "",
                    "trademarkName" => "",
                    "brandCd" => "",
                    "brandName" => "",
                    "bevProdCd" => "",
                    "bevProdName" => "",
                    "primaryContainerName" => "",
                    "primaryContainerId" => "",
                    "secondaryPackageName" => "",
                    "secondaryPackageId" => "",
                    "closureColorName" => "",
                    "closureColorId" => "",
                    "closureDiameterId" => "",
                    "closureDiameterName" => "",
                    "closureTypeName" => "",
                    "closureTypeId" => "",
                    "caffeinated" => null,
                    "carbonated" => null,
                    "calorieCategory" => [
                        "code" => null,
                        "description" => null
                    ]
                ],
                "releaseId" => "495271",
                "releaseName" => "Release 1",
                "organizationId" => "157218",
                "organizationName" => "Michael_India",
                "realtimeCode" => false,
                "testRelease" => true,
                "pointValue" => "",
                "pointType" => "",
                "bevProdOptions" => []
            ],
            "redeemed" => false,
            "actualCode" => $code
        ]];
    }

    public function isValidCode($code, $mobileNo, $transactionId)
    {
        [$success, $codeDetails] = $this->getCodeDetails($code, $mobileNo, $transactionId);

        $isValid = false;
        $brandName = '';
        $actualCode = '';
        $isUsed = false;

        if (!$success) {
            return [
                $isValid,
                $isUsed,
                $actualCode,
                $brandName,
                $codeDetails
            ];
        }
        if (isset($codeDetails["lot"])) {
            $lotDetails = $codeDetails["lot"];

            if ($lotDetails["active"] && !$lotDetails["expired"]) {
                $isValid = true;
                if (isset($codeDetails["actualCode"])) {
                    $actualCode = $codeDetails["actualCode"];
                }
            }
            if ($isValid && isset($codeDetails["redeemed"]) && $codeDetails["redeemed"]) {
                $isUsed = true;
            }
            if ($isValid) {
                $brandName = (isset($lotDetails['bevProdPkg']) &&
                    isset($lotDetails['bevProdPkg']['brandName'])
                ) ? $lotDetails['bevProdPkg']['brandName'] : 'Coca Cola';
            }
        }


        return [
            $isValid,
            $isUsed,
            $actualCode,
            $brandName,
            $codeDetails
        ];
    }

    private function updateCodeAsUsed($code, $mobileNo, $transactionId)
    {
        if ($this->devMode) {
            return true;
        }

        $success = false;
        $url = $this->mixcodeBaseUrl . '/pcservices/rest/v7/pincodes/' .
            $code . '?programId=' . $this->programId .
            '&consumerId=' . '91' . $mobileNo . '&transactionId=' . $transactionId;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36');

        $updateArray = [
            'consumerId' => '91' . $mobileNo,
            'transactionId' => $transactionId
        ];
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($updateArray));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . $this->programSecret
        ]);
        $result = curl_exec($curl);
        $codeDetails = null;
        if ($result !== false) {
            $codeDetails  = json_decode($result, true);
            if (!isset($codeDetails['errorName']) || empty($codeDetails['errorName'])) {
                $success = true;
            }
        }
        return $success;
    }

    public function checkCodeAndUpdate($code, $mobileEnc)
    {
        $transactionId = $this->getTransactionId($mobileEnc);
        $mobileNo = Hash::decryptData($mobileEnc);

        [
            $isValid,
            $isUsed,
            $actualCode,
            $brandName,
            $codeDetails
        ] = $this->isValidCode($code, $mobileNo, $transactionId);
        if ($isValid && !$isUsed) {
            $success = $this->updateCodeAsUsed($code, $mobileNo, $transactionId);
            if (!$success) {
                $isUsed = true;
            }
        }
        return [
            $isValid,
            $isUsed,
            $actualCode,
            $brandName,
            $codeDetails
        ];
    }
}
