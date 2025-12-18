<?php

namespace App\Controllers;

use App\Constant\GenericConstant;
use App\Helper\Hash;
use App\Models\ApiLog;
use App\Models\CdpDetails;
use App\Models\ClickTracker;
use App\Models\DailyCodeCount;
use App\Models\Survey;
use App\Models\User;
use App\Models\UserSession;


class UsersHelperController extends Controller
{
    ########## register##########
    protected function trackRegisterEvents($userData, $sourceDetails, $resp, $input, $postData, $newUserSession)
    {
        UserSession::addUserSession($userData, $sourceDetails, $newUserSession);
        ApiLog::addLog($userData->id, 'REGISTER', $resp, $input);
        if (!empty($postData)) {
            $this->pushToCDP($postData, $userData);
        }
    }

    protected function getNewRegistrationCdpPayload($name, $source, $brand)
    {
        $payload = [
            'event_type' => 'Enter',
            'event_sub_type' => 'Submit_Reg',
            'first_name'  => $this->removeEmoji($name),
            'targeting_age_from' => '18',
            'communication_preferences/sms_message' => 1,
            'communication_preferences/phone_call' => 1,
            'communication_preferences/email' => 1,
            'communication_preferences/whatsapp' => 1,
            'referrer' => $source
        ];
        if (!empty($brand)) {
            $cdpBrandName = CdpDetails::getBrandName($brand);
            if (!empty($cdpBrandName)) {
                $payload['brand_name'] = CdpDetails::getBrandName($brand);
            }
        }
        return $payload;
    }
    protected function getLoginCdpPayload($name, $source, $brand)
    {
        $payload = [
            'event_type' => 'Enter',
            'event_sub_type' => 'Login',
            'first_name'  => $this->removeEmoji($name),
            'communication_preferences/sms_message' => 1,
            'communication_preferences/phone_call' => 1,
            'communication_preferences/email' => 1,
            'communication_preferences/whatsapp' => 1,
            'referrer' => $source
        ];
        if (!empty($brand)) {
            $cdpBrandName = CdpDetails::getBrandName($brand);
            if (!empty($cdpBrandName)) {
                $payload['brand_name'] = CdpDetails::getBrandName($brand);
            }
        }
        return $payload;
    }

    protected function getUserDetails($userData)
    {
        $canAddCode = !DailyCodeCount::isDailyLimitOver($userData);
        $isBumperWinner = boolval($userData->is_bumper_winner);

        return [$canAddCode, $isBumperWinner];
    }

    ########## unsubscribe ##########
    protected function trackUnsubscribeEvent($userData, $output, $input)
    {
        ApiLog::addLog($userData->id, 'UNSUBSCRIBE', $output, $input);
        UserSession::updateUserSession($userData->last_session_id);
        $postData = [
            'event_type' => 'Enter',
            'event_sub_type' => 'Opt_Out',
            'communication_preferences/sms_message' => 0,
            'communication_preferences/phone_call' => 0,
            'communication_preferences/email' => 0,
            'communication_preferences/whatsapp' => 0,
        ];
        $this->pushToCDP($postData, $userData);
    }

    ########## trackAction ##########
    protected function trackActionEvent($actionType, $userData)
    {
        $updated = false;
        if ($actionType == GenericConstant::$clickTypeIAmInterested) {
            $this->trackIAmInterested($userData);
            $updated = true;
        } elseif ($actionType == GenericConstant::$clickTypeHowToParticipate) {
            $this->trackEventAndUserCount($userData, GenericConstant::$clickTypeHowToParticipate);
            $postData = [
                'event_type' => 'Click',
                'event_sub_type' => 'How_To_Participate'
            ];
            $this->pushToCDP($postData, $userData);
            $updated = true;
        }
        UserSession::updateUserSession($userData->last_session_id);
        return $updated;
    }

    protected function validateAnswer($question, $answer, $userData)
    {
        $error = false;
        if (!Survey::validateQuestionNumber($question) || !Survey::validateOption($question, $answer)) {
            $error = true;
        }
        return $error;
    }

    protected function trackIAmInterested($userData)
    {
        $userSession = UserSession::where('id', $userData->last_session_id)->first();
        $source = htmlspecialchars_decode($userSession->traffic_source);

        if (!$userData->registered) {
            $userData->registered = 1;
            $userData->source = $source;
            $userData->last_source = $source;
            $userData->registration_date = $this->getTodayDate();
            $userData->registered_at = date('Y-m-d H:i:s');
            $userData->save();
            ClickTracker::trackEvent($userData, GenericConstant::$clickTypeRegister);

            $name = Hash::decryptData($userData->name);
            $postData = $this->getNewRegistrationCdpPayload($name, $source, '');
            $this->pushToCDP($postData, $userData);
        }
    }
    private function trackEventAndUserCount($userData, $eventType)
    {
        ClickTracker::trackEvent($userData, $eventType);
    }

    ########## chooseBrand ##########
    protected function sendChooseBrandCdpEvent($userData, $brand)
    {
        $eventName = '';

        if ($brand == 'FANTA') {
            $eventName = 'Select_brand_Fanta';
        } elseif ($brand == 'THUMS UP') {
            $eventName = 'Select_brand_Thums_Up';
        } elseif ($brand == 'SPRITE') {
            $eventName = 'Select_brand_Sprite';
        } elseif ($brand == 'COCA-COLA') {
            $eventName = 'Select_brand_Coca_Cola';
        } elseif ($brand == 'LIMCA') {
            $eventName = 'Select_brand_Limca';
        }

        if (!empty($eventName)) {
            $name = Hash::decryptData($userData->name);
            $postData = [
                'event_type' => 'Click',
                'event_sub_type' => $eventName,
                'first_name'  => $this->removeEmoji($name),
                'brand_name' => CdpDetails::getBrandName($brand)
            ];
            $this->pushToCDP($postData, $userData);
        }
    }


    ########## setLanguage ##########
    protected function trackSetLanguageEvent($userData, $output, $input, $language)
    {
        ApiLog::addLog($userData->id, 'SET_LANGUAGE', $output, $input);
        UserSession::updateUserSession($userData->last_session_id);

        $postData = [
            'event_type' => 'Click',
            'event_sub_type' => 'Change_Language',
            'language_code' => strtoupper($language)
        ];
        $this->pushToCDP($postData, $userData);
    }

    ########## generic ##########

    protected function updateUser($userId, $update)
    {
        User::where('id', $userId)->update($update);
    }

    protected function getSuccessMessage($response = [])
    {
        $response["success"] = true;
        return $response;
    }
}
