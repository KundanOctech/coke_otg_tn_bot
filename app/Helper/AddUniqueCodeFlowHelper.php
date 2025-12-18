<?php

namespace App\Helper;

use App\Constant\AddCodeMessage;
use App\Constant\FlowConstant;
use App\Constant\GenericConstant;
use App\Models\FlowToken;
use App\Models\User;
use App\Models\Winner;

class AddUniqueCodeFlowHelper
{

    public static function usedCodeError($languageCode)
    {
        return FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$usedUniqueCode);
    }
    public static function invalidCodeError($languageCode, $codeCount)
    {
        $message = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$invalidUniqueCode);
        return str_replace('{{codeCount}}', $codeCount, $message);
    }

    public static function getAddCodeScreenResponse(FlowToken $flowTokenData, User $userData, $errorMessage = '')
    {
        $languageCode = User::getLanguage($userData->language);

        $result = [
            'flow_token' => $flowTokenData->flow_token,
            'pageTitle' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$uniqueCodePageTitle),
            'heading' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$uniqueCodeRoiHeading),
            'inputLabel' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$uniqueCodeInputLabel),
            'inputHint' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$uniqueCodeInputHint),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$uniqueCodeBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            'banner' => BannerImageHelper::getHtpImage($languageCode),
            'unique_code_error' => $errorMessage

        ];
        $screen = FlowConstant::$screenAddCodeUniqueCode;
        return [$screen, $result];
    }

    // Outside time window response
    public static function getOutsideTimeWindowResponse($flowToken, User $userData)
    {
        $languageCode = User::getLanguage($userData->language);

        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$outsideTimeWindowPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$outsideTimeWindowHeading),
            "body" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$outsideTimeWindowBodyRoi),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$outsideTimeWindowBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            "icon" => BannerImageHelper::getWarningIcon()

        ];
        $screen = 'ERROR_PAGE';
        return [$screen, $result];
    }

    // Daily limit over response
    public static function getDailyLimitOverResponse($flowToken, User $userData)
    {
        $languageCode = User::getLanguage($userData->language);
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyLimitOverPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyLimitOverHeading),
            "body" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyLimitOverBody),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyLimitOverBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            "icon" => BannerImageHelper::getWarningIcon(),
            'errorId' => 'DAILY_LIMIT_OVER'

        ];
        $screen = 'ERROR_PAGE'; // FlowConstant::$screenAddCodeDailyLimitOver;
        return [$screen, $result];
    }

    // Question Page
    public static function getQuestionResponse($flowToken, User $userData, $question)
    {
        $languageCode = User::getLanguage($userData->language);
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyLimitOverPageTitle),
            "question" => $question['question'],
            "options" => [
                [
                    'id' => 'A',
                    'title' => $question['option_a']
                ],
                [
                    'id' => 'B',
                    'title' => $question['option_b']
                ],
                [
                    'id' => 'C',
                    'title' => $question['option_c']
                ],
                [
                    'id' => 'D',
                    'title' => $question['option_d']
                ],
            ],
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$questionBtnText),
            "banner" => BannerImageHelper::getKvBannerImage($languageCode),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText)
        ];
        $screen = 'QNA';
        return [$screen, $result];
    }

    public static function getWrongAnswerResponse($flowToken, User $userData)
    {
        $languageCode = User::getLanguage($userData->language);
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wrongAnswerPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wrongAnswerHeading),
            "body" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wrongAnswerBody),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wrongAnswerBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            "icon" => BannerImageHelper::getWarningIcon(),
            'errorId' => 'WRONG_ANSWER'
        ];
        $screen = 'ERROR_PAGE';
        return [$screen, $result];
    }

    public static function getWeeklyQuotaOverResponse($flowToken, User $userData)
    {
        $languageCode = User::getLanguage($userData->language);
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$weeklyQuotaOverPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$weeklyQuotaOverHeading),
            "body" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$weeklyQuotaOverBody),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$weeklyQuotaOverBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            "icon" => BannerImageHelper::getWarningIcon(),
            'errorId' => 'WRONG_ANSWER'
        ];
        $screen = 'ERROR_PAGE';
        return [$screen, $result];
    }

    // Won bumper reward
    public static function getWonBumperRewardResponse($flowToken, User $userData, Winner $winnerData)
    {
        $languageCode = User::getLanguage($userData->language);
        if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
            $heading = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonTicketHeading);
            $textBody = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonTicketTextBody);
            $btnText = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonTicketBtnText);
            $icon = BannerImageHelper::getMatchTicketImage($languageCode);
        } else {
            $heading = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonMerchHeading);;
            $textBody = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonMerchTextBody);;
            $btnText = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonMerchBtnText);;
            $icon = BannerImageHelper::getMerchImage($languageCode);
        }
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonBumperRewardPageTitle),
            "heading" => $heading,
            "textBody" => $textBody,
            'btnText' => $btnText,
            "icon" => $icon,

        ];
        $screen = 'WINNER';
        return [$screen, $result];
    }

    // Daily quota over response
    public static function getDailyQuotaOverResponse($flowToken, User $userData)
    {
        $languageCode = User::getLanguage($userData->language);
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyQuotaOverPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyQuotaOverHeading),
            "body" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyQuotaOverBody),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyQuotaBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            "icon" => BannerImageHelper::getWarningIcon(),
            'errorId' => 'DAILY_QUOTA_OVER'
        ];
        $screen = 'ERROR_PAGE';
        return [$screen, $result];
    }

    public static function getWonTodayResponse($flowToken, User $userData, $amount)
    {
        $languageCode = User::getLanguage($userData->language);
        $body = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonTodayOverBody);
        $body = str_replace('{{amount}}', $amount, $body);
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyQuotaOverPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyQuotaOverHeading),
            "body" => $body,
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$dailyQuotaBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            "icon" => BannerImageHelper::getWarningIcon(),
            'errorId' => 'DAILY_QUOTA_OVER'
        ];
        $screen = 'ERROR_PAGE';
        return [$screen, $result];
    }


    // Hourly quota over response
    public static function getHourlyQuotaOverResponse($flowToken, User $userData)
    {
        $languageCode = User::getLanguage($userData->language);
        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$hourlyQuotaOverPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$hourlyQuotaOverHeading),
            "body" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$hourlyQuotaOverBody),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$hourlyQuotaBtnText),
            'tandcBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$tncBtnText),
            "icon" => BannerImageHelper::getWarningIcon(),
            'errorId' => 'HOURLY_QUOTA_OVER'
        ];
        $screen = 'ERROR_PAGE';
        return [$screen, $result];
    }

    // Won mass reward response
    public static function getWonMassRewardResponse($flowToken, User $userData, Winner $winnerData)
    {
        $languageCode = User::getLanguage($userData->language);

        $rewardExpireDate = date('d-m-Y', strtotime($winnerData->reward_expire_date));
        $rewardName = explode('_', $winnerData->reward_name);

        $textBody = FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonMassRewardTextBody);
        $textBody = str_replace('{{amount}}', $rewardName[1], $textBody);
        $textBody = str_replace('{{couponCode}}', $winnerData->reward_code, $textBody);
        if ($winnerData->reward_pin) {
            $textBody = str_replace('{{PIN}}', $winnerData->reward_pin, $textBody);
        }
        $textBody = str_replace('{{redeemByDate}}', $rewardExpireDate, $textBody);

        $result = [
            'flow_token' => $flowToken,
            "pageTitle" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonMassRewardPageTitle),
            "heading" => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonMassRewardHeading),
            'textBody' => $textBody,
            'redeemNowBtnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$redeemNowBtnText),
            'btnText' => FlowMessage::getLocalMessage($languageCode, AddCodeMessage::$wonMassRewardBtnText),
            "code" => $winnerData->reward_code,
            'pin' => $winnerData->reward_pin,
            'amount' => intval($rewardName[1]),
            'redeemBy' => $rewardExpireDate,
            "icon" => BannerImageHelper::getPhonepeImage($languageCode)
        ];
        $screen = 'WON_PHONEPE';
        return [$screen, $result];
    }
}
