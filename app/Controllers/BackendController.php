<?php

namespace App\Controllers;

use App\Constant\GenericConstant;
use App\Helper\Hash;
use App\Models\MassRewardQuota;
use App\Models\BumperReward;
use App\Models\User;
use App\Models\CdpDetails;
use App\Models\ClickTracker;
use App\Models\FlowToken;
use App\Models\Report;
use App\Models\UniqueCode;
use App\Models\Winner;

class BackendController extends BackendHelperController
{

    public function activateBumperRewardCode($req, $res, $args)
    {
        $key = BumperReward::htmlEncode($this->getData($args, 'key'));
        if ($key != "vYX6w1ZcmSSPW1qUFiFsk2oGw1KL4k39") {
            return;
        }
        usleep(100000);
        $resp = BumperReward::whereNull('assigned')
            ->where('opens_at', '<=', date('Y-m-d H:i:s'))
            ->update([
                'assigned' => 0
            ]);
        echo "Updated " . $resp;
    }

    public function sendCDP($req, $res, $args)
    {
        $key = CdpDetails::htmlEncode($this->getData($args, 'key'));
        if ($key != "FI0yPtugKmd0hXsA2ni1MgGjAKy4qJLC") {
            return;
        }

        $startTime = strtotime('now');
        $endTime = strtotime('now');
        $hasData = true;
        $n = 0;
        while ($hasData && $endTime - $startTime < 28) {
            $cdp = CdpDetails::where('data_pushed', 0)->first();
            if (empty($cdp)) {
                $hasData = false;
            } else {
                $payload = htmlspecialchars_decode($cdp->payload);

                $ch = curl_init('https://' . $this->container->CDS_BASE_URL . '/v2/datamart/collect');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

                curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    array(
                        'Content-Type: application/json',
                        'x-api-key: ' . $this->container->CDS_API_KEY,
                        'Content-Length: ' . strlen($payload)
                    )
                );
                $result = curl_exec($ch);
                $apiStatus = 'error';
                $updateData = [];

                if ($result) {
                    $updateData['api_result'] = $result;
                    $jsonResult = json_decode($result, true);
                    if (!empty($jsonResult) && isset($jsonResult["status"]) && $jsonResult["status"] === 0) {
                        $apiStatus = 'ok';
                    }
                }
                $apiStatus;

                $updateData['api_status'] = $apiStatus;
                $updateData['data_pushed'] = 1;
                CdpDetails::where('id', $cdp->id)->update($updateData);
                $n++;
            }
            $endTime = strtotime('now');
        }
        echo $n . " record has been updated.";
    }

    public function sendForfeitedNudgeMessage($req, $res, $args)
    {
        $key = FlowToken::htmlEncode($this->getData($args, 'key'));
        if ($key != "VewF8ke2GrxY0sQ36cFe76xws4vFHUCu") {
            return;
        }
        $winnerData = Winner::where('claim_by', '<=', date('Y-m-d H:i:s'))
            ->where('claimed', 0)
            ->first();

        if (
            empty($winnerData) ||
            $winnerData->reward_type != GenericConstant::$winTypeBumperReward &&
            $winnerData->claim_expired
        ) {
            return;
        }
        $updated = Winner::where('id', $winnerData->id)
            ->where('claimed', 0)
            ->update([
                'claim_expired' => 1,
                'claimed' => null,
            ]);
        if ($updated) {

            if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
                $updateList = [
                    'is_ticket_winner' => 0,
                    'claimed_ticket' => null,
                    'ticket_expired' => 1,
                ];
                $cdpEvent = 'ICC_Match_Ticket_Forfeited';
            } else {
                $updateList = [
                    'is_merch_winner' => 0,
                    'claimed_merch' => null,
                    'merch_expired' => 1,
                ];
                $cdpEvent = 'ThumsUp_Merch_Forfeited';
            }
            User::where('id', $winnerData->user_id)
                ->update($updateList);

            $userData = User::where('id', $winnerData->user_id)->first();
            ClickTracker::trackEventValue($userData, GenericConstant::$eventBumperRewardForfeited, $winnerData->state);
            $this->sendForfeitedNudgeWaMessage($winnerData, $cdpEvent);
        }
    }

    public function sendClaimFormNudge($req, $res, $args)
    {
        $key = FlowToken::htmlEncode($this->getData($args, 'key'));
        $day = FlowToken::htmlEncode($this->getData($args, 'day'));
        if ($key != "VewF8ke2GrxY0sQ36cFe76xws4vFHUCu" || !in_array($day, ['1', '2'])) {
            return;
        }


        $date = date('Y-m-d', strtotime(' -' . $day . ' day'));
        // $date = date('Y-m-d');
        $winnerList = Winner::where('created_date', $date)
            ->where('reward_type', GenericConstant::$winTypeBumperReward)
            ->where('claimed', 0)
            ->get();

        foreach ($winnerList as $winnerData) {
            $this->sendClaimFormNudgeMessage($winnerData, $day);
        }
    }

    public function deleteData($req, $res, $args)
    {
        $key = User::htmlEncode($args['key']);
        if ($key != "ebJhv6r3wFcLPE9jzSx2spWRA5DmNy4f") {
            return $res->withJson(['message' => 'Invalid Data'], 400);
        }
        $input = $req->getParsedBody();
        $target = $this->getData($input, 'target');
        $key = $this->getData($input, 'key');
        $resp = [];
        if ($key == 'cB=N9/[tG`z%8W2K)xZ*g"Q>y5w6D<A') {
            if ($target == "REPORT") {
                $delete = Report::where('id', ">", 0)->delete();
            }
            $resp = $delete ? "deleted successfull" : "not delete";
        }
        echo json_encode($resp);
    }

    public function getUserHistory($req, $res, $args)
    {
        $key = User::htmlEncode($args['key']);
        if ($key != "lXz8pLIusb8LHGFpi39X6974wjcOKzbiyv07") {
            return $res->withJson(['message' => 'Invalid Data-' . $key], 400);
        }
        $input = $req->getParsedBody();
        $mobile = User::htmlEncode($this->getData($input, 'mobile'));
        $resp = [];
        $mobileEnc = Hash::encryptData($mobile);
        $userData = User::where('mobile', $mobileEnc)->first();
        $data = [];
        $uid = null;
        if (!empty($userData)) {
            $uid = $userData->id;
            [$overviewDataHeader, $overviewData] = $this->getOverviewData($userData);
            $data[] = [
                "title" => "Overview",
                "dataHeader" => $overviewDataHeader,
                "data" => $overviewData,
            ];


            [$uniqueCodeDataHeader, $uniqueCodeData] = $this->getUniqueCodeData($uid);
            $data[] = [
                "title" => "Unique Codes",
                "dataHeader" => $uniqueCodeDataHeader,
                "data" => $uniqueCodeData,
            ];


            [$rewardDataHeader, $rewardData] = $this->getRewardsData($uid);
            $data[] = [
                "title" => "Rewards",
                "dataHeader" => $rewardDataHeader,
                "data" => $rewardData,
            ];

            [$claimFormDataHeader, $claimFormData] = $this->getClaimFormData($uid);
            $data[] = [
                "title" => "Claim Form",
                "dataHeader" => $claimFormDataHeader,
                "data" => $claimFormData,
            ];

            [$surveyDataHeader, $surveyData] = $this->getSurveyData($uid);
            $data[] = [
                "title" => "Survey",
                "dataHeader" => $surveyDataHeader,
                "data" => $surveyData,
            ];
        }
        $resp = [
            "status" => 200,
            "message" => "success",
            "data" => $data,
            "uid" => $uid,
        ];
        return $res->withJson($resp, 200);
    }

    public function getUniqueCodeHistory($req, $res, $args)
    {
        $key = User::htmlEncode($args['key']);
        if ($key != "xi6sOU2s5DeNF293w6AT7y6jNHxFhKs7plIL") {
            return $res->withJson(['message' => 'Invalid Data'], 400);
        }
        $input = $req->getParsedBody();
        $uniqueCode = User::htmlEncode($this->getData($input, 'uniqueCode'));

        $data = [];
        $uid = null;
        $uniqueCodeData = UniqueCode::where('code', $uniqueCode)->first();
        if (!empty($uniqueCodeData)) {
            $uid = $uniqueCodeData->user_id;
            $userData = User::where('id', $uid)->first();
            if (!empty($userData)) {
                [$overviewDataHeader, $overviewData] = $this->getOverviewData($userData);
                $data[] = [
                    "title" => "Overview",
                    "dataHeader" => $overviewDataHeader,
                    "data" => $overviewData,
                ];

                [$uniqueCodeDataHeader, $uniqueCodeData] = $this->getUniqueCodeData($uid);
                $data[] = [
                    "title" => "Unique Codes",
                    "dataHeader" => $uniqueCodeDataHeader,
                    "data" => $uniqueCodeData,
                ];


                [$rewardDataHeader, $rewardData] = $this->getRewardsData($uid);
                $data[] = [
                    "title" => "Rewards",
                    "dataHeader" => $rewardDataHeader,
                    "data" => $rewardData,
                ];

                [$claimFormDataHeader, $claimFormData] = $this->getClaimFormData($uid);
                $data[] = [
                    "title" => "Claim Form",
                    "dataHeader" => $claimFormDataHeader,
                    "data" => $claimFormData,
                ];

                [$surveyDataHeader, $surveyData] = $this->getSurveyData($uid);
                $data[] = [
                    "title" => "Survey",
                    "dataHeader" => $surveyDataHeader,
                    "data" => $surveyData,
                ];
            }
        }


        $resp = [
            "status" => 200,
            "message" => "success",
            "data" => $data,
            "uid" => $uid,
        ];
        return $res->withJson($resp, 200);
    }

    public function updateRewardCarryForward($req, $res, $args)
    {
        $key = CdpDetails::htmlEncode($this->getData($args, 'key'));
        if ($key != "Hha83KvNu7EzZAf64m9cp5gJReBxsMrk") {
            return;
        }

        $hour = $this->getCurrentHour();
        $today = date('Y-m-d');
        $lastHour = $hour - 1;
        $lastDate = $today;

        if ($hour == 10) {
            $lastHour = 21;
            $lastDate = date('Y-m-d', strtotime($today . ' -1 day'));
        }
        usleep(300000);
        $regionList = [
            'AP',
            'BIHAR',
            'DELHI',
            'GUJARAT',
            'HR_RAJ',
            'KARNATAKA',
            'MAHA',
            'MP_JKD_CHG',
            'NE',
            'ORISSA',
            'PJB_JK',
            'TELENGANA',
            'UP',
            'WB'
        ];

        $amountList = [6, 20, 50, 100];
        foreach ($regionList as $region) {
            foreach ($amountList as $amount) {
                $lastHourData = MassRewardQuota::where('reward_date', $lastDate)
                    ->where('reward_hour', $lastHour)
                    ->where('region', $region)
                    ->where('amount', $amount)
                    ->first();
                if (empty($lastHourData)) {
                    continue;
                }
                $currentHourData = MassRewardQuota::where('reward_date', $today)
                    ->where('reward_hour', $hour)
                    ->where('region', $region)
                    ->where('amount', $amount)
                    ->first();
                if (empty($currentHourData)) {
                    continue;
                }
                $carryForward = $lastHourData->reward_quota + $lastHourData->carry_forward - $lastHourData->winner_count;
                if ($carryForward > 0) {
                    MassRewardQuota::where('id', $currentHourData->id)
                        ->where('carry_forward_updated', 0)
                        ->update([
                            'carry_forward' => $carryForward,
                            'carry_forward_updated' => 1,
                        ]);
                } else {
                    MassRewardQuota::where('id', $currentHourData->id)
                        ->where('carry_forward_updated', 0)
                        ->update([
                            'carry_forward_updated' => 1,
                        ]);
                }
                echo $carryForward . " carry forward updated for " . $region . " - " . $amount . " - " . $currentHourData->reward_hour . " hour of " . $today . "<br/>";
            }
        }
    }
}
