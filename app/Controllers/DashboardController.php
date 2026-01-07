<?php

namespace App\Controllers;

use App\Constant\GenericConstant;

use App\Helper\Hash;
use App\Models\ClaimForm;
use App\Models\ClickTracker;
use App\Models\FlowToken;
use App\Models\MassRewardCode;
use App\Models\MessageCount;
use App\Models\Survey;
use App\Models\UniqueCode;
use App\Models\UniqueCodeLog;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Winner;

class DashboardController extends DashboardHelperController
{
    private $lookerKey = "bNtR9jZvb48jN7G1qdZxwLyWXG0plN";
    private $reportKey = "XmoamqD42zWlgEeVAbLE8IxqeSr667zh";
    private $defaultError = ["statusCode" => 404, "message" => "Invalid request"];

    public function lookerData($req, $res, $args)
    {
        $key = UniqueCodeLog::htmlEncode($args['key']);
        if ($key == $this->lookerKey) {
            $startDate = $this->getStartDate($req);
            $endDate = $this->getEndDate($req);
            $repeatUserYTD = $this->getDateWiseCount($this->getWhereCount(
                $this->dashboardStartDate,
                $endDate,
                new User(),
                "REPEAT_YTD_USERS",
                "repeat_visitor",
                1,
            ));
            $uniqueVisitorYTD = $this->getDateWiseCount($this->getCount(
                $this->dashboardStartDate,
                $endDate,
                new User(),
                "UNIQUE_YTD_USERS",
            ));
            $uniqueVisitor = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new User(),
                "UNIQUE",
                true
            ));
            $regCount = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new User(),
                "REGISTERED_USER_SESSION",
                "registered",
                1,
                date: "registration_date"
            ));
            $nonConsentedUser = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new User(),
                "NON_CONSENTED_USER",
                "registered",
                0
            ));
            $uniqueCodeUsed = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new UniqueCodeLog(),
                "UNIQUE_CODE_USED"
            ));
            // $lead = $this->getDateWiseCount($this->getCount(
            //     $startDate,
            //     $endDate,
            //     new LeadForm(),
            //     "LEAD_FORM_COUNT"
            // ));
            $claimForm = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new ClaimForm(),
                "CLAIM_FORM_COUNT"
            ));
            $optOut = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new ClickTracker(),
                "OPT_OUT",
                "event_type",
                GenericConstant::$clickTypeUnsubscribe,
            ));
            $traffic = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new UserSession(),
                "TRAFFIC"
            ));
            $repeatVisitor = $traffic - $uniqueVisitor;
            $massWinner = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new Winner(), "MASS_WINNER", "reward_type", GenericConstant::$winTypeMassReward));
            $bikeWinner = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new Winner(), "BUMPER_WINNER_COUNT", "reward_type", GenericConstant::$winTypeBumperReward));
            $forFeitWinner = $this->getDateWiseCount($this->getWheren3DCount($startDate, $endDate, new ClickTracker(), "FORFEIT_WINNER", "event_type", GenericConstant::$eventBumperRewardForfeited));
            // $notWin = User::where("win_count", 0)->count();
            $validCount = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new UniqueCode(),
                "VALID_CODE_COUNT",
            ));
            $codeLater = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new ClickTracker(),
                "MAYBE_LATER_CTA",
                "event_type",
                "MAYBE_LATER_CTA"
            ));
            $invalidCodeCount = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new UniqueCodeLog(),
                "INVALID_CODE",
                "valid",
                0
            ));
            $profanity = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new ClickTracker(),
                "PROFANITY",
                "event_type",
                "PROFANITY"
            ));
            $outSideScript = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new ClickTracker(),
                "UNRECOGNIZED_INPUT",
                "event_type",
                "UNRECOGNIZED_INPUT",
            ));
            $message = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new MessageCount(),
                "MESSAGE_COUNT"
            ));
            $totalUniquCodes = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new UniqueCodeLog(),
                "UNIQUE_CODE_TOTAL_LOG"
            ));

            $oneTimeVisitor = User::where("repeat_visitor", 0)
                ->count();

            // Avg
            $sessionDurationCount = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new UserSession(),
                "SESSION_DURATION_COUNT"
            ));

            $sessionDurationSum = $this->getDateWiseCount($this->getSum(
                $startDate,
                $endDate,
                new UserSession(),
                "SESSION_DURATION_SUM",
                'session_duration'
            ));

            if ($sessionDurationCount) {
                $avgDuration = round(intval($sessionDurationSum) / intval($sessionDurationCount));
            } else {
                $avgDuration = 0;
            }


            $output[] = [
                "uniqueVisitorYTD" => $uniqueVisitorYTD,
                "repeatVisitorYTD" => $repeatUserYTD,
                "uniqueVisitor" => intval($uniqueVisitor),
                "repeatVisitor" => intval($repeatVisitor),
                "regCount" => intval($regCount),
                "nonConsentedUser" => intval($nonConsentedUser),
                "uniqueCodeUsed" => intval($uniqueCodeUsed),
                "optOut" => intval($optOut),
                "traffic" => intval($traffic),
                "massWinner" => intval($massWinner),
                "bikeWinner" => intval($bikeWinner),
                // "notWin" => intval($notWin),
                "validCount" => intval($validCount),
                "codeLater" => intval($codeLater),
                "invalidCodeCount" => intval($invalidCodeCount),
                // "leadCount" => intval($lead),
                "claimCount" => intval($claimForm),
                "profanity" => intval($profanity),
                "outSideScript" => intval($outSideScript),
                "message" => intval($message),
                "oneTimeVisitor" => intval($oneTimeVisitor),
                "avgDuration" => $avgDuration,
                "forFeitWinner" => $forFeitWinner,
                "totalUniqueCodes" => $totalUniquCodes
            ];
        } else {
            $output = $this->defaultError;
        }
        return json_encode($output);
    }

    public function dayWiseData($req, $res, $args)
    {
        $output = $this->defaultError;

        $key = UserSession::htmlEncode($args['key']);
        if ($key == $this->lookerKey) {
            $startDate = $this->getStartDate($req);
            $endDate = $this->getEndDate($req);
            $message = $this->getCount($startDate, $endDate, new MessageCount(), "MESSAGE_COUNT");
            $uniqueVisitor = $this->getWhereCount($startDate, $endDate, new UserSession(), "UNIQUE_MOBILE", "first_session_of_day", 1);
            $traffic = $this->getCount($startDate, $endDate, new UserSession(), "TRAFFIC");
            $regCount = $this->getWhereCount($startDate, $endDate, new User(), "REGISTERED_USER_SESSION", "registered", 1, date: "registration_date");
            $repeat = $this->getWhereCount($startDate, $endDate, new UserSession(), "REPEAT_ADD_YTD", "first_session_of_day", 0);
            $optOut = $this->getWhereCount($startDate, $endDate, new ClickTracker(), "event_type", "event_type", GenericConstant::$clickTypeUnsubscribe);
            $mass6Winner = $this->getWinnerCount($startDate, $endDate, new Winner(), "MASS_06_WINNER", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward6, ["reward_type", "reward_name"]);
            $mass20Winner = $this->getWinnerCount($startDate, $endDate, new Winner(), "MASS_20_WINNER", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward20, ["reward_type", "reward_name"]);
            $mass50Winner = $this->getWinnerCount($startDate, $endDate, new Winner(), "MASS_50_WINNER", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward50, ["reward_type", "reward_name"]);
            $mass100Winner = $this->getWinnerCount($startDate, $endDate, new Winner(), "MASS_100_WINNER", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward100, ["reward_type", "reward_name"]);
            $bikeMarvik = $this->getWinnerCount($startDate, $endDate, new Winner(), "BUMPER_WINNER_MERCH", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeMerch, ["reward_type", "reward_name"]);
            // merch
            $bikeXtrem = $this->getWinnerCount($startDate, $endDate, new Winner(), "BUMPER_WINNER_TICKET", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeTicket, ["reward_type", "reward_name"]);
            // ticket
            $nonConsentedUser = $this->getWhereCount($startDate, $endDate, new User(), "NON_CONSENTED_USER", "registered", 0);
            // $oneTimeVisitor = $this->getWhereCount($startDate, $endDate, new User(), "ONE_TIME_VISIT", "repeat_visitor", 0);
            $forFeitWinner = $this->getWheren3DCount($startDate, $endDate, new ClickTracker(), "FORFEIT_WINNER", "event_type", GenericConstant::$eventBumperRewardForfeited);
            $oneTimeVisitor = User::where("repeat_visitor", 0)->count();
            $validUniqueCode = $this->getCount(
                $startDate,
                $endDate,
                new UniqueCode(),
                "VALID_CODE_COUNT",
            );
            foreach ($message as $key => $count) {
                $final[] = [
                    "date" => $key,
                    "message" => intval($count),
                    "uniqueVisitorDay" => intval($uniqueVisitor[$key]),
                    "repeatVisitorDay" => intval($repeat[$key]),
                    "traffic" => intval($traffic[$key]),
                    "regCount" => intval($regCount[$key]),
                    "optOut" => intval($optOut[$key]),
                    "nonConsentedUser" => intval($nonConsentedUser[$key]),
                    "phonePe6" => intval($mass6Winner[$key]),
                    "phonePe20" => intval($mass20Winner[$key]),
                    "phonePe50" => intval($mass50Winner[$key]),
                    "phonePe100" => intval($mass100Winner[$key]),
                    "bikeMarvik" => intval($bikeMarvik[$key]),
                    // merch
                    "bikeXtreme" => intval($bikeXtrem[$key]),
                    // ticket
                    "uniqueCode" => intval($validUniqueCode[$key]),
                    // "oneTimeVisitor" => intval($oneTimeVisitor[$key])
                    "oneTimeVisitor" => intval($oneTimeVisitor),
                    "forFeitWinner" => intval($forFeitWinner[$key])
                ];
            }
            return json_encode($final);
        }
        return json_encode($output);
    }

    public function trafficWiseData($req, $res, $args)
    {
        $output = $this->defaultError;

        $key = UserSession::htmlEncode($args['key']);
        if ($key == $this->lookerKey) {
            $startDate = $this->getStartDate($req);
            $endDate = $this->getEndDate($req);
            // $uniqueDay = $this->getUniqueGroupByCount($startDate, $endDate, new User(), strtoupper("traffic_unique"));
            $uniqueDay = $this->getUniqueGroupByCount($startDate, $endDate, new UserSession(), "TRAFFIC_UNIQUE", "traffic_source");
            $register = $this->getRegGroupByCount($startDate, $endDate, new User(), "TRAFFIC_REGISTER", created_date: "registration_date");
            $uniqueCount = $this->getTrafficOnlyCount($startDate, $endDate, new UniqueCode(), "TRAFFIC_UNIQUE_CODE", "source");
            $massWinner = $this->getTrafficCount($startDate, $endDate, new Winner(), "TRAFFIC_WINNER_MASS_COUNT", "reward_type", GenericConstant::$winTypeMassReward, "source");
            $bikeWinner = $this->getTrafficCount($startDate, $endDate, new Winner(), "TRAFFIC_WINNER_BIKE_COUNT", "reward_type", GenericConstant::$winTypeBumperReward, "source");
            $traffic = $this->getTrafficGroupByCount($startDate, $endDate, new UserSession(), "TRAFFIC_COUNT", "traffic_source");
            foreach ($traffic as $key => $count) {
                $final[] = [
                    "traffic" => $key,
                    "count" => intval($count),
                    "register" => intval($register[$key] ?? null),
                    "uniqueDay" => intval($uniqueDay[$key] ?? null),
                    "uniqueCode" => intval($uniqueCount[$key] ?? null),
                    "massWinner" => intval($massWinner[$key] ?? null),
                    "bikeWinner" => intval($bikeWinner[$key] ?? null),
                ];
            }
            return json_encode($final);
        }
        return json_encode($output);
    }

    public function lsStateDistribution($req, $res, $args)
    {
        $output = [];

        $key = User::htmlEncode($args['key']);
        if ($key == $this->lookerKey) {


            $startDate = $this->getStartDate($req);
            $endDate = $this->getEndDate($req);


            $stateList = $this->getStateDistributionData($startDate, $endDate);

            foreach ($stateList as $stateInfo) {
                $output[] = [
                    'state' => $stateInfo['state'],
                    'session' => $stateInfo['session'],
                    'regCount' => $stateInfo['regCount'],
                    'codeCount' => $stateInfo['codeCount'],
                    'repeatCount' => $stateInfo['repeatCount'],
                    'mass6Count' => $stateInfo['mass6Count'],
                    'mass20Count' => $stateInfo['mass20Count'],
                    'mass50Count' => $stateInfo['mass50Count'],
                    'mass100Count' => $stateInfo['mass100Count'],
                    'merchWinnerCount' => $stateInfo['merchWinnerCount'],
                    'ticketWinnerCount' => $stateInfo['ticketWinnerCount'],
                    'forfitWinner' => $stateInfo['forfitWinner'],
                ];
            }
        }
        return json_encode($output);
    }


    public function lsUniqueCodeDistribution($req, $res, $args)
    {
        $output = ["status" => 400];

        $key = User::htmlEncode($args['key']);
        if ($key == $this->lookerKey) {
            $b0UserCount = User::where('registered', 1)
                ->where('valid_code_count', 0)
                ->count();
            $b1UserCount = User::where('valid_code_count', 1)->count();
            $b2UserCount = User::where('valid_code_count', 2)->count();
            $b3UserCount = User::where('valid_code_count', 3)->count();
            $b4UserCount = User::where('valid_code_count', 4)->count();
            $b5UserCount = User::where('valid_code_count', '>=', 5)
                ->where('valid_code_count', '<=', 10)->count();
            $b6UserCount = User::where('valid_code_count', '>=', 11)
                ->where('valid_code_count', '<=', 50)->count();
            $b7UserCount = User::where('valid_code_count', '>=', 51)
                ->count();
            $result = [
                ['codeEntered' => '0', 'userCount' => $b0UserCount],
                ['codeEntered' => '1', 'userCount' => $b1UserCount],
                ['codeEntered' => '2', 'userCount' => $b2UserCount],
                ['codeEntered' => '3', 'userCount' => $b3UserCount],
                ['codeEntered' => '4', 'userCount' => $b4UserCount],
                ['codeEntered' => '5-10', 'userCount' => $b5UserCount],
                ['codeEntered' => '10-50', 'userCount' => $b6UserCount],
                ['codeEntered' => '50+', 'userCount' => $b7UserCount]
            ];
            return json_encode($result);
        }
        return json_encode($output);
    }


    public function emailReport($req, $res, $args)
    {
        $output = $this->defaultError;
        $key = UserSession::htmlEncode($args['key']);
        if ($key == $this->lookerKey) {
            $startDate = $this->getStartDate($req);
            $endDate = date('Y-m-d', strtotime('-1 day'));

            $dateVal = date('F j', strtotime('-1 day'));


            $totalUsers = $this->getDateWiseCount($this->getWhereCount(
                $startDate,
                $endDate,
                new User(),
                "REGISTERED_USER_SESSION",
                "registered",
                1,
                date: "registration_date"
            ));

            $yesterdayUsers = $this->getDateWiseCount($this->getWhereCount(
                $endDate,
                $endDate,
                new User(),
                "REGISTERED_USER_SESSION",
                "registered",
                1,
                date: "registration_date"
            ));

            $totalmass5 = MassRewardCode::where('amount', 5)->count();
            $totalmass10 = MassRewardCode::where('amount', 10)->count();

            $uniquCode = $this->getDateWiseCount($this->getCount(
                $startDate,
                $endDate,
                new UniqueCode(),
                "UNIQUE_CODE_TOTAL",
            ));
            $yesterdayUniquCode = $this->getDateWiseCount($this->getCount(
                $endDate,
                $endDate,
                new UniqueCode(),
                "UNIQUE_CODE_TOTAL",
            ));

            // $totalMavrick = BumperReward::where('reward_name', 'Mavrick')->count();
            // $totalXtreme = BumperReward::where('reward_name', 'Xtreme')->count();

            $totalmass5Used = MassRewardCode::where('amount', 6)->where('assigned', 1)->count();
            $totalmass10Used = MassRewardCode::where('amount', 20)->where('assigned', 1)->count();

            $totalYMass5Used = $this->getDateWiseCount($this->getWinnerCount($startDate, $endDate, new MassRewardCode(), "MASS_WINNER_PHONE_5", 5, 1, ["amount", "assigned"], "assigned_date"));
            $yesterdayMass5Used = $this->getDateWiseCount($this->getWinnerCount($endDate, $endDate, new MassRewardCode(), "MASS_WINNER_PHONE_5", 5, 1, ["amount", "assigned"], "assigned_date"));

            $totalYMass10Used = $this->getDateWiseCount($this->getWinnerCount($startDate, $endDate, new MassRewardCode(), "MASS_WINNER_PHONE_20", 10, 1, ["amount", "assigned"], "assigned_date"));
            $yesterdayMass10Used = $this->getDateWiseCount($this->getWinnerCount($endDate, $endDate, new MassRewardCode(), "MASS_WINNER_PHONE_20", 10, 1, ["amount", "assigned"], "assigned_date"));


            $totalMerch = $this->getDateWiseCount($this->getWinnerCount($startDate, $endDate, new Winner(), "BUMPER_WINNER_MERCH", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeMerch, ["reward_type", "reward_name"]));
            $totalTicket = $this->getDateWiseCount($this->getWinnerCount($startDate, $endDate, new Winner(), "BUMPER_WINNER_TICKET", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeTicket, ["reward_type", "reward_name"]));

            $yesterdayMerchUsed = $this->getDateWiseCount($this->getWinnerCount($endDate, $endDate, new Winner(), "BUMPER_WINNER_MERCH", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeMerch, ["reward_type", "reward_name"]));
            $yesterdayTicketUsed = $this->getDateWiseCount($this->getWinnerCount($endDate, $endDate, new Winner(), "BUMPER_WINNER_TICKET", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeTicket, ["reward_type", "reward_name"]));
            $result = [
                'emailDate' => [
                    'value' => $dateVal
                ],
                'totalUsers' =>
                [
                    'value' => $totalUsers
                ],
                'yesterdayUsers' =>
                [
                    'value' => $yesterdayUsers
                ],
                'uniquCode' =>
                [
                    'value' => $uniquCode
                ],
                'yesterdayUniquCode' =>
                [
                    'value' => $yesterdayUniquCode
                ],
                'totalYUsedmass5' =>
                [
                    'value' => $totalYMass5Used
                ],
                'yesterdayMass5Used' =>
                [
                    'value' => $yesterdayMass5Used
                ],
                'totalYUsedmass10' =>
                [
                    'value' => $totalYMass10Used
                ],
                'yesterdayMass10Used' =>
                [
                    'value' => $yesterdayMass10Used
                ],
                'totalmass5' =>
                [
                    'value' => $totalmass5
                ],
                'totalmass10' =>
                [
                    'value' => $totalmass10
                ],

                'totalmass5Used' =>
                [
                    'value' => $totalmass5Used
                ],
                'totalmass10Used' =>
                [
                    'value' => $totalmass10Used
                ],
                'totalMerchUsed' =>
                [
                    'value' => $totalMerch
                ],
                'totalTicketUsed' =>
                [
                    'value' => $totalTicket
                ],
                'yesterdayMerchUsed' =>
                [
                    'value' => $yesterdayMerchUsed
                ],
                'yesterdayTicketUsed' =>
                [
                    'value' => $yesterdayTicketUsed
                ],
                'totalmass5Left' =>
                [
                    'value' => $totalmass5 - $totalmass5Used
                ],
                'totalmass10Left' =>
                [
                    'value' => $totalmass10 - $totalmass10Used
                ]
            ];
            $output = [
                'success' => true,
                'subject' =>
                [
                    'emailDate' =>
                    [
                        'value' => date('Y-m-d', strtotime('-1 day'))
                    ]
                ],
                'body' => $result

            ];
        }
        return json_encode($output);
    }

    public function journeyAnalysis($req, $res, $args)
    {
        $output = ["status" => 400];

        $key = UserSession::htmlEncode($args['key']);
        if ($key == $this->lookerKey) {
            $startDate = $this->getStartDate($req);
            $endDate = $this->getEndDate($req);

            $welcomeMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-WELCOME-MSG", 'message_type', 'welcome_msg'));
            $changeLangMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-CHANGE-LANG", 'message_type', 'change_lang_msg'));
            $welcomeBackMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-WELCOME-BACK", 'message_type', 'welcome_back_msg'));
            $selectState = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-SELECT-STATE", 'message_type', 'select_state'));
            $htp = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-HTP", 'message_type', 'htp'));
            $enterUniqueCode = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-ENTER-UNIQUE-CODE", 'message_type', 'enter_unique_code'));
            $uniqueCodeNudge = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new FlowToken(), "JA-UNIQUE-CODE-NUDGE", 'flow_token_type', 'unique_code_nudge'));
            $invalidUniqueCode = $this->getDateWiseCount($this->getCountConditions($startDate, $endDate, new UniqueCodeLog(), "JA-INVALID-UNIQUE-CODE", [
                ['valid', 0],
                ['invalid_reason', 'Invalid code']
            ]));
            $usedUniqueCode = $this->getDateWiseCount($this->getCountConditions($startDate, $endDate, new UniqueCodeLog(), "JA-USED-UNIQUE-CODE", [
                ['valid', 0],
                ['invalid_reason', 'Code already used']
            ]));
            $dailyLimitOver = $this->getDateWiseCount($this->getDailyLimitOverCount($startDate, $endDate, 'JA-DAILY-LIMIT-OVER'));
            $claimFormNudge = $this->getDateWiseCount($this->getClaimNudgeCount($startDate, $endDate, 'JA-CLAIM-FORM-NUDGE'));
            $gibberishMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-GIBBERISH-MSG", 'message_type', 'gibberish_msg'));
            $profanityMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-PROFANITY-MSG", 'message_type', 'profanity_msg'));
            $stopMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-STOP-MSG", 'message_type', 'stop_msg'));
            $wonAllMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-WON-ALL", 'message_type', 'won_all_msg'));
            $myWinsBaseMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-MY-WINS-BASE", 'message_type', 'mywins_base_msg'));
            $outsidePromoHours = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-OUTSIDE-PROMO-HOURS", 'message_type', 'outside_promo_hours'));
            $claimWelcomeBack = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new MessageCount(), "JA-CLAIM-WELCOME-BACK", 'message_type', 'claim_welcome_back_msg'));
            $phonepeWinner = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new Winner(), "JA-PP-WINNER", 'reward_type', GenericConstant::$winTypeMassReward));
            $ppQuotaOverMsg = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new ClickTracker(), "JA-PP-QUOTA-OVER", 'event_type', 'CODE_PP_QUOTA_OVER_MSG'));
            $surveyCompleteMsg = $this->getDateWiseCount($this->getCount($startDate, $endDate, new Survey(), "JA-SURVEY-COMPLETE"));
            $wonBike = $this->getDateWiseCount($this->getWhereCount($startDate, $endDate, new Winner(), "JA-BIKE-WINNER", 'reward_type', GenericConstant::$winTypeBumperReward));
            $rewardForfeited = $this->getDateWiseCount($this->getCountConditions($startDate, $endDate, new Winner(), "JA-REWARD-FORFEITED", [
                ['reward_type', GenericConstant::$winTypeBumperReward],
                ['claim_expired', 1]
            ]));
            // $claimNudge = 0;
            // $filledLeadForm = $this->getDateWiseCount($this->getCount($startDate, $endDate, new LeadForm(), "JA-LEAD-FORM"));


            $output = [
                'welcomeMsg' => $welcomeMsg,
                'changeLangMsg' => $changeLangMsg,
                'welcomeBackMsg' => $welcomeBackMsg,
                'selectState' => $selectState,
                'htp' => $htp,
                'enterUniqueCode' => $enterUniqueCode,
                'uniqueCodeNudge' => $uniqueCodeNudge,
                'invalidUniqueCode' => $invalidUniqueCode,
                'usedUniqueCode' => $usedUniqueCode,
                'dailyLimitOver' => $dailyLimitOver,
                'claimFormNudge' => $claimFormNudge,
                'gibberishMsg' => $gibberishMsg,
                'profanityMsg' => $profanityMsg,
                'stopMsg' => $stopMsg,
                'wonAllMsg' => $wonAllMsg,
                'myWinsBaseMsg' => $myWinsBaseMsg,
                'outsidePromoHours' => $outsidePromoHours,
                'claimWelcomeBack' => $claimWelcomeBack,
                'phonepeWinner' => $phonepeWinner,
                'ppQuotaOverMsg' => $ppQuotaOverMsg,
                'surveyCompleteMsg' => $surveyCompleteMsg,
                'wonBike' => $wonBike,
                'rewardForfeited' => $rewardForfeited,
                'claimNudge' => $claimFormNudge,
                // 'filledLeadForm' => $filledLeadForm
            ];
        }
        return json_encode($output);
    }

    public function claimForm($req, $res, $args)
    {
        $output = $this->defaultError;

        $key = UserSession::htmlEncode($args['key']);

        if ($key == $this->reportKey) {
            $startDate = $this->getStartDate($req);
            $endDate = $this->getEndDate($req);

            $data = ClaimForm::select(
                'mobile',
                'name',
                'email',
                'reward_name',
                'address_1',
                'address_2',
                'pincode',
                'region',
                'state',
                'city',
                'landmark',
                'ticket_city',
                'won_at',
                'created_at'
            )
                ->where("created_date", ">=", $startDate)
                ->where("created_date", "<=", $endDate)
                ->orderBy('id', 'DESC')
                ->get();
            $final = [];
            foreach ($data as $d) {
                $final[] = [
                    Hash::decryptData($d->mobile),
                    Hash::decryptData($d->name),
                    Hash::decryptData($d->email),
                    $d->reward_name,
                    $d->address_1,
                    $d->address_2,
                    $d->pincode,
                    $d->region,
                    $d->state,
                    $d->city,
                    $d->landmark,
                    $d->ticket_city,
                    date('Y-m-d H:i:s', strtotime($d->won_at)),
                    date('Y-m-d H:i:s', strtotime($d->created_at)),
                ];
            }
            $dataHeader = [
                [
                    "title" => 'Mobile',
                    "dataIndex" => 0,
                    "type" => "text"
                ],
                [
                    "title" => 'Name',
                    "dataIndex" => 1,
                    "type" => "text"
                ],
                [
                    "title" => 'Email',
                    "dataIndex" => 2,
                    "type" => "text"
                ],
                [
                    "title" => 'Reward name',
                    "dataIndex" => 3,
                    "type" => "text"
                ],
                [
                    "title" => 'Address 1',
                    "dataIndex" => 4,
                    "type" => "text"
                ],
                [
                    "title" => 'Address 2',
                    "dataIndex" => 5,
                    "type" => "text"
                ],
                [
                    "title" => 'Pincode',
                    "dataIndex" => 6,
                    "type" => "text"
                ],
                [
                    "title" => 'User region',
                    "dataIndex" => 7,
                    "type" => "text"
                ],
                [
                    "title" => 'State',
                    "dataIndex" => 8,
                    "type" => "text"
                ],
                [
                    "title" => 'City',
                    "dataIndex" => 9,
                    "type" => "text"
                ],
                [
                    "title" => 'landmark',
                    "dataIndex" => 10,
                    "type" => "text"
                ],
                [
                    "title" => 'Ticket City',
                    "dataIndex" => 11,
                    "type" => "text"
                ],
                [
                    "title" => 'Won At',
                    "dataIndex" => 12,
                    "type" => "text"
                ],
                [
                    "title" => 'Claimed At',
                    "dataIndex" => 13,
                    "type" => "text"
                ]
            ];
            $output = [
                "status" => 200,
                "data" => [
                    'filters' => [
                        [
                            "type" => "DATE-RANGE",
                            "param" => "startDate,endDate",
                            "endDate" => "2025-03-01"
                        ],
                    ],
                    'dataHeader' => $dataHeader,
                    "data" => $final,
                    "pageCount" => 1,
                    "totalRows" => count($final),
                ]
            ];
        }
        return json_encode($output);
    }
}
