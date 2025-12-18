<?php

namespace App\Helper;

use App\Constant\ClaimFormWaMessage;
use App\Constant\FlowConstant;
use App\Constant\GenericConstant;
use App\Models\User;
use App\Models\Winner;

class ClaimFormFlowHelper
{

    public static function getTicketCityResponse($flowTokenData, User $userData, $errorMessage = null)
    {
        $languageCode = User::getLanguage($userData->language);
        if (!empty($errorMessage)) {
            $result = ["formErrors" => $errorMessage, 'flow_token' => $flowTokenData->flow_token];
        } else {
            $result = [
                'flow_token' => $flowTokenData->flow_token,
                'pageTitle' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$cityPageTitle),
                'cityInputLabel' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$cityInputLabel),
                'cityInputDescription' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$cityInputDescription),
                'btnText' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$cityBtnText),
                'cityData' => ClaimFormWaMessage::$cityData
            ];

            $result['banner'] = BannerImageHelper::getMatchTicketImage($languageCode);
        }
        $screen = 'SELECT_CITY';
        return [$screen, $result];
    }

    // Claim screen response.
    public static function getClaimFormResponse($flowTokenData, User $userData, $errorMessage = null)
    {
        $languageCode = User::getLanguage($userData->language);
        if (!empty($errorMessage)) {
            $result = ["formErrors" => $errorMessage, 'flow_token' => $flowTokenData->flow_token];
        } else {
            $winnerData = Winner::where('id', $flowTokenData->data_id)->first();

            $initialData  = self::getInitialData($userData);
            $result = [
                'flow_token' => $flowTokenData->flow_token,
                'pageTitle' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$claimFormPageTitle),
                'name_label' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$claimFormNameLabel),
                'email_label' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$claimFormEmailLabel),
                'address_line_1_label' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$claimFormAddressLine1Label),
                'address_line_2_label' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$claimFormAddressLine2Label),
                'landmark_label' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$claimFormLandmarkLabel),
                'pincode_label' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$claimFormPincodeLabel),
                'btnText' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenBtnText),
                'initialData' => $initialData
            ];
            $result['banner'] = $winnerData->reward_name == GenericConstant::$winTypeTicket ?
                BannerImageHelper::getMatchTicketImage($languageCode) :
                BannerImageHelper::getMerchImage($languageCode);
        }
        $screen = FlowConstant::$screenClaimFormAdd;
        return [$screen, $result];
    }

    public static function getClaimFormSuccessResponse($flowToken, $userData)
    {
        $languageCode = User::getLanguage($userData->language);
        $winnerData = Winner::where('id', $flowToken->data_id)->first();

        $result = [
            'flow_token' => $flowToken,
            'pageTitle' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$ticketSuccessPageTitle),
            'btnText' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$ticketSuccessBtnText),
            'heading' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$ticketSuccessHeading),
            'textBody' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$ticketSuccessTextBody),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$tncBtnText)
        ];
        $result['banner'] = $winnerData->reward_name == GenericConstant::$winTypeTicket ?
            BannerImageHelper::getMatchTicketImage($languageCode) :
            BannerImageHelper::getMerchImage($languageCode);
        $screen = FlowConstant::$screenClaimFormSuccess;
        return [$screen, $result];
    }

    // summary screen for Claim form.
    public static function getClaimFormSummaryResponse($flowTokenData, $userData, $summeryData, $errorMessage = null)
    {
        $languageCode = User::getLanguage($userData->language);
        if (!empty($errorMessage)) {
            $errorMessage['flow_token'] = $flowTokenData->flow_token;
            $result = $errorMessage;
        } else {
            $winnerData = Winner::where('id', $flowTokenData->data_id)->first();

            $result = [
                'flow_token' => $flowTokenData->flow_token,
                'pageTitle' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenPageTitle),
                'btnText' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenBtnText),
                'name' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenNameLabel) . ":\n" . $summeryData['name'],
                'email' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenEmailLabel) . ":\n" . $summeryData['email'],
                'address_line_1' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenAddressLine1) . ":\n" . $summeryData['address_line_1'],
                'address_line_2' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenAddressLine2) . ":\n" . $summeryData['address_line_2'],
                'landmark' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenLandmark) . ":\n" . $summeryData['landmark'],
                'pincode' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenPincode) . ":\n" . $summeryData['pincode'],
                'state' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenState) . ":\n" . $summeryData['state'],
                'city' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$summaryScreenCity) . ":\n" . $summeryData['city'],
                'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, ClaimFormWaMessage::$tncBtnText),

            ];
            $result['banner'] = $winnerData->reward_name == GenericConstant::$winTypeTicket ?
                BannerImageHelper::getMatchTicketImage($languageCode) :
                BannerImageHelper::getMerchImage($languageCode);
        }
        $screen = 'SUMMARY';
        return [$screen, $result];
    }


    private static function getInitialData($userData)
    {
        $initialData = ['efo' => ''];

        if ($userData->name) {
            $initialData['name'] = Hash::decryptData($userData->name);
        }
        if ($userData->email) {
            $initialData['email'] = Hash::decryptData($userData->email);
        }
        if ($userData->address_line_1) {
            $initialData['address_line_1'] = $userData->address_line_1;
        }
        if ($userData->address_line_2) {
            $initialData['address_line_2'] = $userData->address_line_2;
        }
        if ($userData->landmark) {
            $initialData['landmark'] = $userData->landmark;
        }
        if ($userData->address_pincode) {
            $initialData['pincode'] = $userData->address_pincode;
        }


        return $initialData;
    }
}
