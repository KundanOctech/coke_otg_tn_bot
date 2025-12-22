<?php

namespace App\Controllers;

use App\Constant\GenericConstant;
use App\Models\User;
use App\Helper\Hash;
use App\Helper\Whatsapp;
use App\Models\ApiLog;
use App\Models\BuyNow;
use App\Models\ClickTracker;
use App\Models\DailyCodeCount;
use App\Models\FlowToken;
use App\Models\MassRewardQuota;
use App\Models\MessageCount;
use App\Models\Survey;
use App\Models\UserSession;
use App\Models\Winner;
use Illuminate\Database\QueryException;

class UsersController extends UsersHelperController
{
    public function register($req, $res)
    {
        $input = $req->getParsedBody();
        $mobile = substr($this->getData($input, 'mobile'), -10, 10);
        if ($this->getData($input, 'mobile') == 'WEB_SIMULATION') {
            $mobile = '8553550133';
        }

        // if (!in_array($mobile, ['9008008120', '8553550133'])) {
        //     return $res->withJson([
        //         "success" => false
        //     ], 200);
        // }

        $source = $this->getData($input, 'source');
        $name = $this->getData($input, 'name');
        $brand = $this->getData($input, 'brand');

        $isAutoTypedMessageRaw = $this->getData($input, 'isAutoTypedMessage', false);
        if (is_string($isAutoTypedMessageRaw)) {
            $isAutoTypedMessage = strtolower($isAutoTypedMessageRaw) == 'true';
        } else {
            $isAutoTypedMessage = $isAutoTypedMessageRaw;
        }
        if (!User::isPhoneNumber($mobile)) {
            return $res->withJson([
                "success" => false
            ], 200);
        }
        $mobileEnc = Hash::encryptData($mobile);
        $userData = User::where('mobile', $mobileEnc)->first();

        if (empty($userData)) {
            $newUser = true;

            $userData = new User();
            $userData->mobile = $mobileEnc;

            $userData->name = Hash::encryptData($name);

            $userData->source = $source;
            $userData->brand = $brand;
            $userData->last_source = $source;
            $userData->registered = $isAutoTypedMessage ? 1 : 0;
            $userData->created_date = $this->getTodayDate();
            if ($isAutoTypedMessage) {
                $userData->registration_date = $this->getTodayDate();
                $userData->registered_at = date('Y-m-d H:i:s');
            }
            $userData->save();
            User::addUserAuthKey($userData->id);
            $userData = User::where('id', $userData->id)->first();
            $newUser = true;
            if ($isAutoTypedMessage) {
                $newUserSession = 1;
                $postData = $this->getNewRegistrationCdpPayload($name, $source, $brand);
                ClickTracker::trackEvent($userData, GenericConstant::$clickTypeRegister);
            } else {
                $newUserSession = null;
                $postData = [];
            }
        } elseif (!$userData->registered) {
            $updateList = [
                'repeat_visitor' => 1,
                'brand' => $brand,
            ];

            User::where('id', $userData->id)->update($updateList);
            $newUser = true;
            $newUserSession = null;
            if ($isAutoTypedMessage) {
                $userData->registered = 1;
                $userData->name = Hash::encryptData($name);
                $userData->source = $source;
                $userData->last_source = $source;
                $userData->registration_date = $this->getTodayDate();
                $userData->registered_at = date('Y-m-d H:i:s');
                $userData->save();
                $postData = $this->getNewRegistrationCdpPayload($name, $source, $brand);
                ClickTracker::trackEvent($userData, GenericConstant::$clickTypeRegister);
            } else {
                $postData = [];
            }
        } else {
            $newUser = false;
            $userUpdateList = [
                'repeat_visitor' => 1,
                'last_source' => $source
            ];

            User::where('id', $userData->id)->update($userUpdateList);
            $userData = User::where('id', $userData->id)->first();
            $newUserSession = 0;
            $postData = $this->getLoginCdpPayload($name, $source, $brand);
        }

        $wonAllReward = User::wonAllReward($userData);
        $resp = $this->getSuccessMessage([
            "newUser" => $newUser,
            "outsideMagicHours" => $this->outsideTimeWindow(),
            "language" => User::getLanguage($userData->language),
            "canAddCode" => !DailyCodeCount::isDailyLimitOver($userData) && !$wonAllReward,
            "isTicketWinner" => boolval($userData->is_ticket_winner),
            "canClaimTicket" => $userData->is_ticket_winner && !$userData->claimed_ticket,
            "isMerchWinner" => boolval($userData->is_merch_winner),
            "canClaimMerch" => $userData->is_merch_winner && !$userData->claimed_merch,
            "wonAllReward" => $wonAllReward,
            "isFromTN" => true, //User::fromTn($userData),
            'isActive' => strtotime('now') >= strtotime('2025-12-15')
        ]);
        $sourceDetails = [
            'source' => $source,
            'brand' => $brand
        ];
        $this->trackRegisterEvents($userData, $sourceDetails, $resp, $input, $postData, $newUserSession);

        return $res->withJson($resp, 200);
    }

    public function unsubscribe($req, $res)
    {
        $input = $req->getParsedBody();
        $userData = $req->getAttribute('userData');
        $this->updateUser($userData->id, ['opt_out' => 1]);
        $output = $this->getSuccessMessage();
        $this->trackUnsubscribeEvent($userData, $output, $input);
        ClickTracker::trackEvent($userData, GenericConstant::$clickTypeUnsubscribe);
        return $res->withJson($output, 200);
    }

    public function trackAction($req, $res)
    {
        $input = $req->getParsedBody();
        $actionType = strtoupper($this->getData($input, 'type'));
        $userData = $req->getAttribute('userData');
        $updated = $this->trackActionEvent($actionType, $userData);
        $output = $this->getSuccessMessage([
            'updated' => $updated
        ]);
        ApiLog::addLog($userData->id, 'TRACK_ACTION', $output, $input);
        return $res->withJson($output, 200);
    }

    public function chooseBrand($req, $res)
    {
        $input = $req->getParsedBody();
        $brand = strtoupper($this->getData($input, 'brand'));
        $userData = $req->getAttribute('userData');
        if (empty($userData->brand)) {
            $updateList = ['brand' => $brand];
            $this->updateUser($userData->id, $updateList);
        }

        UserSession::where('id', $userData->last_session_id)
            ->update(['brand' => $brand]);

        $this->sendChooseBrandCdpEvent($userData, $brand);
        $output = $this->getSuccessMessage();
        return $res->withJson($output, 200);
    }

    public function setLanguage($req, $res)
    {
        $input = $req->getParsedBody();
        $userData = $req->getAttribute('userData');
        $language = strtolower($this->getData($input, 'language'));
        $output = $this->getSuccessMessage(['updated' => false]);
        if (in_array($language, ['en', 'hi', 'bn', 'gu'])) {
            $this->updateUser($userData->id, ['language' => $language]);
            $output['updated'] = true;
        }

        $this->trackSetLanguageEvent($userData, $output, $input, $language);
        ApiLog::addLog($userData->id, 'SET_LANGUAGE', $output, $input);
        return $res->withJson($output, 200);
    }

    public function getFlowToken($req, $res)
    {
        $input = $req->getParsedBody();
        $userData = $req->getAttribute('userData');
        $flowTokenType = $this->getData($input, 'flowTokenType');
        $output = $this->getSuccessMessage([
            'token' => FlowToken::getFlowToken($userData->id, $flowTokenType)
        ]);
        ApiLog::addLog($userData->id, 'GET_FLOW_TOKEN', $output, $input);
        return $res->withJson($output, 200);
    }

    public function canTakeSurvey($req, $res)
    {
        $userData = $req->getAttribute('userData');
        $input = $req->getParsedBody();
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();

        $surveyCompleted = Survey::where('user_id', $userData->id)
            ->where('brand', $sessionData->brand)
            ->exists();

        $output = $this->getSuccessMessage([
            "canTakeSurvey" => !$surveyCompleted,
        ]);
        ApiLog::addLog($userData->id, 'CAN-TAKE-SURVEY', $output, $input);
        return $res->withJson($output, 200);
    }

    public function completeSurvey($req, $res)
    {
        $userData = $req->getAttribute('userData');

        $input = $req->getParsedBody();
        $answer1 = $this->getData($input, 'answer1');
        $answer2 = $this->getData($input, 'answer2');
        $answer3 = $this->getData($input, 'answer3');
        $error1 = $this->validateAnswer('q1', $answer1, $userData);
        $error2 = $this->validateAnswer('q2', $answer2, $userData);
        $error3 = $this->validateAnswer('q3', $answer3, $userData);

        if ($error1 || $error2 || $error3) {
            return $res->withJson([
                "success" => false,
            ], 200);
        }
        $surveySaved = false;
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();
        try {
            Survey::saveData([
                'user_id' => $userData->id,
                'source' => $sessionData->traffic_source,
                'source_id' => $sessionData->id,
                'brand' => $sessionData->brand,
                'mobile' => $userData->mobile,
                'answer_1' => Survey::$questionOptions['q1'][$answer1],
                'answer_2' => Survey::$questionOptions['q2'][$answer2],
                'answer_3' => Survey::$questionOptions['q3'][$answer3],
                'created_date' => date('Y-m-d')

            ]);

            $postData = [
                'event_sub_type' => Survey::$cdpOptions['q1'][$answer1],
            ];
            $this->pushToCDP($postData, $userData);
            $postData = [
                'event_sub_type' => Survey::$cdpOptions['q2'][$answer2],
            ];
            $this->pushToCDP($postData, $userData);
            $postData = [
                'event_sub_type' => Survey::$cdpOptions['q3'][$answer3],
            ];
            $this->pushToCDP($postData, $userData);
            $surveySaved = true;
        } catch (QueryException $e) {
            // echo json_encode($e);
            // exit(1);
        }
        $output = $this->getSuccessMessage([
            "surveySaved" => $surveySaved,
        ]);
        ApiLog::addLog($userData->id, 'SURVEY', $output, $input);
        return $res->withJson($output, 200);
    }

    public function myWins($req, $res)
    {
        $input = $req->getParsedBody();
        $userData = $req->getAttribute('userData');

        $massRewards = [];
        $winnerList = Winner::where('user_id', $userData->id)
            ->where('reward_type', GenericConstant::$winTypeMassReward)
            ->orderBy('id')
            ->get();
        foreach ($winnerList as $winner) {
            $rewardName = explode('_', $winner->reward_name);
            $amount = $rewardName[1];

            $massRewards[] = [
                'rewardCode' => $winner->reward_code,
                'rewardPin' => $winner->reward_pin,
                'amount' => $amount,
                'redeemBy' => date('d-m-Y', strtotime($winner->reward_expire_date)),
                'winningDate' => date('d-m-Y', strtotime($winner->created_date)),
            ];
        }
        $ticketWinner = null;
        $merchWinner = null;

        $bumperWinnerData = Winner::where('user_id', $userData->id)
            ->where('reward_type', GenericConstant::$winTypeBumperReward)
            ->first();

        if (!empty($bumperWinnerData) && !$bumperWinnerData->claim_expired) {
            if ($bumperWinnerData->reward_name == GenericConstant::$winTypeTicket) {
                $ticketWinner = [
                    'claimBy' => date('d-m-Y H:i:s', strtotime($bumperWinnerData->claim_by)),
                    'claimed' => boolval($bumperWinnerData->claimed)
                ];
            } else {
                $merchWinner = [
                    'claimBy' => date('d-m-Y H:i:s', strtotime($bumperWinnerData->claim_by)),
                    'claimed' => boolval($bumperWinnerData->claimed)
                ];
            }
        }

        $output = $this->getSuccessMessage([
            'data' => [
                'massRewards' => $massRewards,
                'ticket' => $ticketWinner,
                'merch' => $merchWinner,
            ]
        ]);
        ApiLog::addLog($userData->id, 'MY_WINS', $output, $input);
        return $res->withJson($output, 200);
    }

    public function messageSent($req, $res)
    {
        $input = $req->getParsedBody();
        $messageType = $this->getData($input, 'messageType');
        $userData = $req->getAttribute('userData');
        UserSession::updateUserSession($userData->last_session_id);
        MessageCount::addMessageCount($userData, $messageType, 1);
        return $res->withJson($this->getSuccessMessage(), 200);
    }

    public function messageContainsProfanity($req, $res)
    {
        $input = $req->getParsedBody();
        $message = $this->getData($input, 'message', false);

        $profanity = false;
        if (is_string($message)) {
            $profanity = $this->containsProfanity($message);
        }

        return $res->withJson($this->getSuccessMessage([
            "profanity" => $profanity
        ]), 200);
    }

    public function getBuyOutMessage($req, $res)
    {
        $input = $req->getParsedBody();
        $userData = $req->getAttribute('userData');
        $sessionData = UserSession::where('id', $userData->last_session_id)->first();

        $brand = strtoupper($sessionData->brand);
        if (!in_array($brand, ['FANTA', 'SPRITE', 'THUMS UP', 'LIMCA'])) {
            $brand = 'COCA-COLA';
        }
        $language = 'en'; //User::getLanguage($userData->language);
        $buyNowList = BuyNow::getList($brand, $language);

        $waObject = new Whatsapp($this->container);
        $messageId = strtolower($userData->auth_key) . '_' . User::getUuid4Key();

        $message = $waObject->getBuyOutMessage($userData, $messageId, $buyNowList);
        $response = $waObject->sendWhatsappMessage([$message]);

        ApiLog::addLog($userData->id, 'BUY-OUT-WA', $response, $message);
        $output = $this->getSuccessMessage(['brand' => $brand, 'sBrand' => $sessionData->brand]);
        ApiLog::addLog($userData->id, 'BUY-OUT-API', $output, $input);
        $postData = [
            'event_type' => 'Click',
            'event_sub_type' => 'Buy_Now_TN'
        ];
        $this->pushToCDP($postData, $userData);
        return $res->withJson($output, 200);
    }

    private function generateTimeSlot($startDate, $days, $perDay, $distribution, $phase)
    {
        $insert = [];
        $currentDate = strtotime($startDate . ' 18:00:00');

        $startHr = 18;
        $endHour = 22;
        $daySecond = ($endHour - $startHr) * 60 * 60;
        $slot = $daySecond / $perDay;
        $gutter = floor($slot * 0.05);
        // echo 'gutter: ' . $gutter . "\n";
        // echo 'slot: ' . $slot . "\n";
        // die;

        $bikeData = [];
        foreach ($distribution as $regionData) {
            $region = $regionData['region'];
            $rewardCount = $regionData['Merch'];
            for ($i = 0; $i < $rewardCount; $i++) {
                $bikeData[] = [$region, 'TICKET'];
            }
        }
        shuffle($bikeData);
        $bikeCount = 0;

        for ($i = 0; $i < $days; $i++) {
            $t = 0;
            for ($j = 0; $j < $perDay && $bikeCount < count($bikeData); $j++) {
                $randTime = mt_rand($gutter, $slot * 0.9);
                $insert[] = [
                    'region' => $bikeData[$bikeCount][0],
                    'reward_name' => $bikeData[$bikeCount][1],
                    'opens_at' => date('Y-m-d H:i:s', $currentDate + $t + $randTime),
                    'phase' => $phase
                ];
                $bikeCount++;
                $t += $slot;
            }
            $currentDate = strtotime("+1 days", $currentDate);
        }
        return $insert;
    }

    private function masRewardQuota($startDate, $days, $distribution)
    {

        $startHr = 10;
        $endHour = 21;

        foreach ($distribution as $region => $counts) {
            $insert = [];
            $currentDate = strtotime($startDate);

            for ($i = 0; $i < $days; $i++) {
                for ($h = $startHr; $h < $endHour; $h++) {
                    [$rs6, $rs20, $rs50, $rs100] = $counts;

                    $insert[] = [
                        'reward_date' => date('Y-m-d', $currentDate),
                        'reward_hour' => $h,
                        'amount' => 6,
                        'region' => $region,
                        'reward_quota' => $rs6
                    ];

                    $insert[] = [
                        'reward_date' => date('Y-m-d', $currentDate),
                        'reward_hour' => $h,
                        'amount' => 20,
                        'region' => $region,
                        'reward_quota' => $rs20
                    ];
                    $insert[] = [
                        'reward_date' => date('Y-m-d', $currentDate),
                        'reward_hour' => $h,
                        'amount' => 50,
                        'region' => $region,
                        'reward_quota' => $rs50
                    ];
                    $insert[] = [
                        'reward_date' => date('Y-m-d', $currentDate),
                        'reward_hour' => $h,
                        'amount' => 100,
                        'region' => $region,
                        'reward_quota' => $rs100
                    ];
                }
                $currentDate = strtotime("+1 days", $currentDate);
                if (count($insert) >= 5000) {
                    MassRewardQuota::insert($insert);
                    $insert = [];
                }
            }

            if (count($insert) > 0) {
                MassRewardQuota::insert($insert);
            }
        }
        return $insert;
    }

    public function testFun()
    {
        exit(1);
        $distribution = [
            'AP' => [167, 6, 2, 0],
            'BIHAR' => [222, 7, 2, 1],
            'DELHI' => [194, 6, 2, 1],
            'GUJARAT' => [139, 5, 1, 0],
            'HR_RAJ' => [194, 7, 2, 0],
            'KARNATAKA' => [111, 4, 1, 0],
            'MAHA' => [333, 10, 3, 1],
            'MP_JKD_CHG' => [194, 7, 2, 0],
            'NE' => [83, 3, 1, 0],
            'ORISSA' => [83, 3, 1, 0],
            'PJB_JK' => [222, 7, 2, 1],
            'UP' => [500, 16, 4, 1],
            'WB' => [194, 7, 2, 0],
            'TELENGANA' => [139, 5, 1, 0],
        ];
        $insert = $this->masRewardQuota('2025-12-15', 76, $distribution);





        exit(1);
        $startDate = '2025-12-15';
        $days = 76;
        $perDay = 16;
        $distribution = [
            [
                'region' => 'AP',
                'Merch' => 30,
            ],
            [
                'region' => 'BIHAR',
                'Merch' => 40,
            ],
            [
                'region' => 'DELHI',
                'Merch' => 35,
            ],
            [
                'region' => 'GUJARAT',
                'Merch' => 25,
            ],
            [
                'region' => 'HR_RAJ',
                'Merch' => 35,
            ],
            [
                'region' => 'KARNATAKA',
                'Merch' => 20,
            ],
            [
                'region' => 'MAHA',
                'Merch' => 60,
            ],
            [
                'region' => 'MP_JKD_CHG',
                'Merch' => 35,
            ],
            [
                'region' => 'NE',
                'Merch' => 15,
            ],
            [
                'region' => 'ORISSA',
                'Merch' => 16,
            ],
            [
                'region' => 'PJB_JK',
                'Merch' => 40,
            ],
            [
                'region' => 'UP',
                'Merch' => 90,
            ],
            [
                'region' => 'WB',
                'Merch' => 35,
            ],
            [
                'region' => 'TELENGANA',
                'Merch' => 26,
            ],
        ];
        $phase = 'Match Tickets';
        $dateDistribution = [
            [
                'startDate' => '2025-12-15',
                'days' => 22,
                'perDay' => 3
            ],
            [
                'startDate' => '2026-01-06',
                'days' => 26,
                'perDay' => 13
            ],
            [
                'startDate' => '2026-02-01',
                'days' => 14,
                'perDay' => 6
            ],
            [
                'startDate' => '2026-02-15',
                'days' => 14,
                'perDay' => 1
            ],
        ];
        $insert = [];
        foreach ($dateDistribution as $dateDist) {
            $insertT = $this->generateTimeSlot(
                $dateDist['startDate'],
                $dateDist['days'],
                $dateDist['perDay'],
                $distribution,
                $phase
            );
            $insert = array_merge($insert, $insertT);
        }
        // BumperReward::insert($insert);
        echo json_encode($insert);
    }
}
