<?php

namespace App\Controllers;

use App\Constant\FlowConstant;
use App\Constant\GenericConstant;

use App\Helper\AddUniqueCodeFlowHelper;
use App\Helper\ClaimFormFlowHelper;
use App\Helper\FlowHash;
use App\Helper\FlowHelper;
use App\Helper\Hash;
use App\Helper\MixCode;
use App\Models\ApiLog;
use App\Models\BumperReward;
use App\Models\ClaimForm;
use App\Models\ClickTracker;
use App\Models\DailyCodeCount;
use App\Models\FlowToken;
use App\Models\MassRewardCode;
use App\Models\MassRewardQuota;
use App\Models\PinCode;
use App\Models\Question;
use App\Models\UniqueCode;
use App\Models\UniqueCodeLog;
use App\Models\User;
use App\Models\Winner;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\QueryException;


class FlowHelperController extends Controller
{

    protected function pingAddCode()
    {
        return ['status' => 'active'];
    }

    protected function initAddCode($flowTokenData)
    {
        $userData = User::where('id', $flowTokenData->user_id)->first();
        $status = $flowTokenData->token_status;
        [$screen, $result] = AddUniqueCodeFlowHelper::getAddCodeScreenResponse($flowTokenData, $userData);

        if (
            $status == FlowConstant::$statusAddCodeMatchTicket ||
            $status == FlowConstant::$statusAddCodeMerchandise ||
            $status == FlowConstant::$statusAddCodeMassPrizeWinner
        ) {
            [$screen, $result] = FlowHelper::getExpiredTokenResponse($flowTokenData->flow_token);
        }
        return [$screen, $result];
    }

    protected function backAddCode($flowTokenData)
    {
        $userData = User::where('id', $flowTokenData->user_id)->first();
        [$screen, $result] = AddUniqueCodeFlowHelper::getAddCodeScreenResponse($flowTokenData, $userData);

        if ($this->outsideTimeWindow()) {
            [$screen, $result] = AddUniqueCodeFlowHelper::getOutsideTimeWindowResponse($flowTokenData->flow_token, $userData);
        } elseif (DailyCodeCount::isDailyLimitOver($userData)) {
            [$screen, $result] = AddUniqueCodeFlowHelper::getDailyLimitOverResponse($flowTokenData->flow_token, $userData);
        }

        return [$screen, $result];
    }

    protected function dataExchangeAddCode($input, $flowTokenData, $srcScreen)
    {
        ApiLog::addLog(0, 'dataExchangeAddCode', [], $input);
        // echo 'dataExchangeAddCode 1<br>';

        $data = $this->getData($input, 'data', false);
        $uniqueCode = strtoupper($this->getData($data, 'unique_code'));
        $userData = User::where('id', $flowTokenData->user_id)->first();
        $status = $flowTokenData->token_status;

        if ($srcScreen == 'WINNER') {
            $winnerData = Winner::where('id', $flowTokenData->data_id)->first();
            if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
                [$screen, $result] = ClaimFormFlowHelper::getTicketCityResponse($flowTokenData, $userData);
            } else {
                [$screen, $result] = ClaimFormFlowHelper::getClaimFormResponse($flowTokenData, $userData);
            }
            return [$screen, $result];
        } elseif ($srcScreen == 'QNA') {
            $answer = strtoupper($this->getData($data, 'answer'));
            return $this->validateAnswer($flowTokenData, $userData, $answer);
        }

        if (!empty($flowTokenData->nudge_at)) {
            FlowToken::where('flow_token', $flowTokenData->flow_token)->update(['nudge_at' => null]);
        }

        if ($this->outsideTimeWindow()) {
            UniqueCodeLog::addCodeLog($userData, $uniqueCode, 0, 'outside time window');
            [$screen, $result] = AddUniqueCodeFlowHelper::getOutsideTimeWindowResponse($flowTokenData->flow_token, $userData);
            ClickTracker::trackEvent($userData, 'CODE_OUTSIDE_TIME_WINDOW');
        } elseif (DailyCodeCount::isDailyLimitOver($userData)) {
            $this->addCodeLog($userData, $uniqueCode, 0, 'daily limit exceeded');
            [$screen, $result] = AddUniqueCodeFlowHelper::getDailyLimitOverResponse($flowTokenData->flow_token, $userData);
            ClickTracker::trackEvent($userData, 'CODE_DAILY_LIMIT_EXCEEDED');
        } elseif (empty($status)) {
            [$screen, $result] = $this->checkCodeAndUpdate($uniqueCode, $flowTokenData, $userData);
        } elseif ($status == GenericConstant::$winTypeTicket) {
            return $this->dataExchangeTicketClaimForm($input, $flowTokenData, $srcScreen);
        } elseif ($status == GenericConstant::$winTypeMerch) {
            return $this->dataExchangeMerchClaimForm($input, $flowTokenData, $srcScreen);
        } elseif ($status == FlowConstant::$statusAddCodeMassPrizeWinner) {
            $winnerData = Winner::where('id', $flowTokenData->data_id)->first();
            [$screen, $result] = AddUniqueCodeFlowHelper::getWonMassRewardResponse($flowTokenData->flow_token, $userData, $winnerData);
        }
        return [$screen, $result];
    }

    protected function checkCodeAndUpdate($uniqueCode, $flowTokenData, $userData)
    {
        $isValidUniqueCodeFormate = UniqueCode::isValidUniqueCodeFormat($uniqueCode);

        if (!$isValidUniqueCodeFormate) {
            return $this->getInvalidUniqueCodeResponse($userData, $uniqueCode, $flowTokenData);
        }

        $screen = '';
        $result = '';

        $mixCodeObj = new MixCode($this->container);
        [
            $isValid,
            $isUsed,
            $actualCode,
            $brandName,
        ] = $mixCodeObj->checkCodeAndUpdate($uniqueCode, $userData->mobile);

        if ($isUsed) {
            [$screen, $result] = $this->getUsedCodeResponse($userData, $uniqueCode, $flowTokenData);
        } elseif (!$isValid) {
            [$screen, $result] = $this->getInvalidUniqueCodeResponse($userData, $uniqueCode, $flowTokenData);
        } else {
            [$added, $isUsed] = $this->addCode($userData, $uniqueCode, $actualCode, $brandName);

            if ($isUsed) {
                [$screen, $result] = $this->getUsedCodeResponse($userData, $uniqueCode, $flowTokenData);
            } elseif (!$added) {
                [$screen, $result] = $this->getInvalidUniqueCodeResponse($userData, $uniqueCode, $flowTokenData);
            } else {
                $userData = User::where('id', $userData->id)->first();
                $postData = [
                    'event_type' => 'Enter',
                    'event_sub_type' => 'Submit_Code_' . $userData->valid_code_count
                ];
                $this->pushToCDP($postData, $userData);
                $postData = [
                    'event_type' => 'Enter',
                    'event_sub_type' => 'Runs_Scored_Code' . $userData->valid_code_count
                ];
                $this->pushToCDP($postData, $userData);
                [$screen, $result] = $this->askQuestion($userData, $flowTokenData, $uniqueCode);
            }
        }
        return [$screen, $result];
    }

    private function askQuestion($userData, $flowTokenData, $uniqueCode)
    {
        $questionData = Question::getQuestion($uniqueCode);
        return AddUniqueCodeFlowHelper::getQuestionResponse($flowTokenData->flow_token, $userData, $questionData);
    }

    private function validateAnswer($flowTokenData, $userData, $answer)
    {
        $uniqueCodeData = UniqueCode::where('code', $userData->last_unique_code)->first();
        $questionData = Question::where('id', $uniqueCodeData->question_id)->first();

        if (empty($questionData) || strtoupper($questionData->correct_option) != $answer) {
            UniqueCode::where('code', $userData->last_unique_code)->update([
                'answered_correct' => 0
            ]);
            User::where('id', $userData->id)->update([
                'total_answer_count' => DB::raw('total_answer_count + 1')
            ]);
            [$screen, $result] = AddUniqueCodeFlowHelper::getWrongAnswerResponse($flowTokenData->flow_token, $userData);
            return [$screen, $result];
        }
        UniqueCode::where('code', $userData->last_unique_code)->update([
            'answered_correct' => 0
        ]);
        UniqueCodeLog::addCodeLog($userData, $uniqueCodeData->code, 1, '');
        User::where('id', $userData->id)->update([
            'total_answer_count' => DB::raw('total_answer_count + 1'),
            'correct_answer_count' => DB::raw('correct_answer_count + 1'),
        ]);

        $userData = User::where('id', $userData->id)->first();
        // echo 'correct answers: ' . $userData->correct_answer_count . '<br>';
        return $this->makeWinner($userData, $flowTokenData, $uniqueCodeData->code);
    }

    private function makeWinner($userData, $flowTokenData, $uniqueCode)
    {
        // 1. Check if user has won all mass rewards
        // 2. Check if user has won this hour
        // 3. Check hourly quota, if not return hourly or daily quota over message
        // 4. make winner


        if ($userData->correct_answer_count >= 2 && !User::everWonBumperReward($userData)) {
            // echo 'makeWinner 1<br>';
            $winnerDetails = BumperReward::makeWinner($userData, $uniqueCode);
            if ($winnerDetails['isWinner']) {
                // echo 'makeWinner 2<br>';
                FlowToken::where('flow_token', $flowTokenData->flow_token)
                    ->update([
                        'token_status' => $winnerDetails['rewardName'],
                        'data_id' => $winnerDetails['winId']
                    ]);
                $winnerData = Winner::where('id', $winnerDetails['winId'])->first();
                if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
                    $postData = [
                        'event_type' => 'Click',
                        'event_sub_type' => 'Match_Tickets_Not_Claimed'
                    ];
                } else {
                    $postData = [
                        'event_type' => 'Click',
                        'event_sub_type' => 'ThumsUp_Merch_Not_Claimed'
                    ];
                }
                $this->pushToCDP($postData, $userData);
                return AddUniqueCodeFlowHelper::getWonBumperRewardResponse($flowTokenData, $userData, $winnerData);
            }
        }

        // 1. if won all mas reward
        // 2. won today

        if (User::wonAllMassReward($userData)) {
            // echo 'makeWinner 3<br>';
            [$screen, $result] = AddUniqueCodeFlowHelper::getWeeklyQuotaOverResponse($flowTokenData->flow_token, $userData);
        } elseif (MassRewardCode::wonToday($userData)) {
            // echo 'makeWinner 4<br>';
            $today = date('Y-m-d');
            $key = $userData->id . '_' . $today;
            $winnerData = MassRewardCode::where('user_cashback_key', $key)->first();
            [$screen, $result] = AddUniqueCodeFlowHelper::getWonTodayResponse($flowTokenData->flow_token, $userData, $winnerData->amount);
        } else {
            // echo 'makeWinner 5<br>';
            foreach (MassRewardQuota::$rewardOrder as $amount) {
                // echo 'makeWinner 6<br>';
                if (MassRewardQuota::canBeWinner($amount)) {
                    $winnerResponse = MassRewardCode::assignRewardCode($userData, $amount, $uniqueCode);
                    $screen = null;
                    $result = null;
                    if ($winnerResponse['isWinner']) {
                        // echo 'makeWinner 7<br>';
                        FlowToken::where('flow_token', $flowTokenData->flow_token)
                            ->update([
                                'token_status' => FlowConstant::$statusAddCodeMassPrizeWinner,
                                'data_id' => $winnerResponse['win']
                            ]);

                        $winnerData = Winner::where('id', $winnerResponse['win'])->first();
                        [$screen, $result] = AddUniqueCodeFlowHelper::getWonMassRewardResponse($flowTokenData->flow_token, $userData, $winnerData);

                        $postData = [
                            'event_type' => 'Click',
                            'event_sub_type' => 'Win_PhonePe'
                        ];
                        $this->pushToCDP($postData, $userData);
                    }
                }
            }
            if (empty($screen)) {
                // echo 'makeWinner 8<br>';
                [$screen, $result] = AddUniqueCodeFlowHelper::getDailyQuotaOverResponse($flowTokenData->flow_token, $userData);
            }
        }
        return [$screen, $result];
    }

    protected function addCode($userData, $code, $actualCode)
    {
        $added = false;
        $isUsed = false;

        $status = true;
        while ($status) {
            $codeUsed = UniqueCode::codeExist($actualCode);
            if ($codeUsed) {
                $isUsed = true;
                $status = false;
                break;
            }

            $actualCodeUsed = UniqueCode::actualCodeExist($actualCode);
            if ($actualCodeUsed) {
                $isUsed = true;
                $status = false;
                break;
            }

            $codeCountAdded = DailyCodeCount::addValidCodeLog($userData, $code);
            if ($codeCountAdded) {
                $added = UniqueCode::addCode($userData, $code, $actualCode);
                if ($added) {
                    $this->addCodeLog($userData, $code, 1, '');
                    User::where('id', $userData->id)->update([
                        'last_unique_code' => $code,
                        'valid_code_count' => DB::raw('valid_code_count + 1'),
                        'unique_code_count' => DB::raw('unique_code_count + 1'),
                    ]);
                } else {
                    $isUsed = true;
                    $this->addCodeLog($userData, $code, 0, 'code not added');
                }
                $status = false;
            } else {
                $this->addCodeLog($userData, $code, 0, 'already won today');
            }
        }
        return [$added, $isUsed];
    }

    protected function getUsedCodeResponse($userData, $uniqueCode, $flowTokenData)
    {
        $this->addCodeLog($userData, $uniqueCode, 0, 'Code already used');
        $language = User::getLanguage($userData->language);

        $errorMessage = AddUniqueCodeFlowHelper::usedCodeError($language);
        [$screen, $result] = AddUniqueCodeFlowHelper::getAddCodeScreenResponse($flowTokenData, $userData, $errorMessage);
        return [$screen, $result];
    }

    protected function getInvalidUniqueCodeResponse($userData, $uniqueCode, $flowTokenData)
    {
        $this->addCodeLog($userData, $uniqueCode, 0, 'Invalid code');
        $invalidCodeCount = DailyCodeCount::getInvalidCodeCount($userData->id);
        $language = User::getLanguage($userData->language);

        if ($invalidCodeCount >= DailyCodeCount::$invalidCodeLimit) {
            [$screen, $result] = AddUniqueCodeFlowHelper::getDailyLimitOverResponse($flowTokenData->flow_token, $userData);
        } else {
            $errorMessage = AddUniqueCodeFlowHelper::invalidCodeError($language, $invalidCodeCount);
            [$screen, $result] = AddUniqueCodeFlowHelper::getAddCodeScreenResponse($flowTokenData, $userData, $errorMessage);
        }
        return [$screen, $result];
    }

    protected function addCodeLog($userData, $code, $valid, $invalidReason)
    {
        if (!$valid) {
            DailyCodeCount::addCodeLog($userData, $code, $valid, $invalidReason);
            User::where('id', $userData->id)->increment('unique_code_count');
        } else {
            UniqueCodeLog::addCodeLog($userData, $code, $valid, $invalidReason);
        }
    }

    ########## Ticket ##########

    protected function initTicketClaimForm($flowTokenData)
    {
        $userData = User::where('id', $flowTokenData->user_id)->first();
        $status = $flowTokenData->token_status;
        [$screen, $result] = ClaimFormFlowHelper::getTicketCityResponse($flowTokenData, $userData);
        $canClaim = $userData->is_ticket_winner && !$userData->ticket_expired && !$userData->claimed_ticket;


        if ($status == FlowConstant::$statusClaimFormClaimed || !$canClaim) {
            [$screen, $result] = FlowHelper::getExpiredTokenResponse($flowTokenData->flow_token);
        }

        return [$screen, $result];
    }

    protected function dataExchangeTicketClaimForm($input, $flowTokenData, $srcScreen)
    {
        $data = $this->getData($input, 'data', false);
        ApiLog::addLog(0, 'dataExchangeTicketClaimForm', [], $input);
        $userData = User::where('id', $flowTokenData->user_id)->first();
        if ($srcScreen == 'SELECT_CITY') {
            $cities = $this->getData($data, 'cities', false);
            return $this->validateAndUpdateTicketCities($userData, implode(',', $cities), $flowTokenData);
        }

        return $this->dataExchangeMerchClaimForm($input, $flowTokenData, $srcScreen);
    }

    protected function dataExchangeMerchClaimForm($input, $flowTokenData, $srcScreen)
    {
        ApiLog::addLog(0, 'dataExchangeMerchClaimForm', [], $input);
        $data = $this->getData($input, 'data', false);


        $name = $this->getData($data, 'name');
        $email = strtolower($this->getData($data, 'email'));
        $addressLine1 = $this->getData($data, 'address_line_1');
        $addressLine2 = $this->getData($data, 'address_line_2');
        $landmark = $this->getData($data, 'landmark');
        $pincode = $this->getData($data, 'pincode');

        $userData = User::where('id', $flowTokenData->user_id)->first();

        if (!empty($flowTokenData->nudge_at)) {
            FlowToken::where('flow_token', $flowTokenData->flow_token)->update(['nudge_at' => null]);
        }

        [$errorMessage, $state, $city] = $this->validateClaimFormData($input, $userData, $flowTokenData);

        if (!empty($errorMessage)) {
            return ClaimFormFlowHelper::getClaimFormResponse($flowTokenData, $userData, $errorMessage);
        }
        $summeryData = [
            'name' => $name,
            'email' => $email,
            'address_line_1' => $addressLine1,
            'address_line_2' => $addressLine2,
            'landmark' => $landmark,
            'pincode' => $pincode,
            'state' => $state,
            'city' => $city
        ];
        if ($srcScreen == 'SUMMARY') {
            $winnerData = Winner::where('id', $flowTokenData->data_id)->first();
            $claimSuccess = ClaimForm::claim($userData, $summeryData, $winnerData);
            if ($claimSuccess) {
                $this->updateUserDataFromClaim($summeryData, $userData, $winnerData);
                Winner::where('id', $flowTokenData->data_id)
                    ->update([
                        'claimed' => 1,
                        'claimed_at' => date('Y-m-d H:i:s'),
                    ]);


                if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
                    $postData = [
                        'event_type' => 'Click',
                        'event_sub_type' => 'Claim_Form_Match_tickets',
                    ];
                } else {
                    $postData = [
                        'event_type' => 'Click',
                        'event_sub_type' => 'Claim_Form_ThumsUp_Merch',
                    ];
                }
                $postData['email'] = $email;
                $postData['first_name'] = $name;
                if (!empty($addressLine1)) {
                    $postData['address_line1'] = $addressLine1;
                }
                if (!empty($addressLine2)) {
                    $postData['address_line2'] = $addressLine2;
                }
                $postData['address_city'] = ucfirst(strtolower($city));
                $postData['address_state'] = ucfirst(strtolower($state));
                $postData['geo_postal_code'] = $pincode;
                $this->pushToCDP($postData, $userData);

                FlowToken::where('flow_token', $flowTokenData->flow_token)
                    ->update([
                        'token_status' => FlowConstant::$statusClaimFormClaimed
                    ]);
            }
            return ClaimFormFlowHelper::getClaimFormSuccessResponse($flowTokenData, $userData);
        } else {
            return ClaimFormFlowHelper::getClaimFormSummaryResponse($flowTokenData, $userData, $summeryData);
        }
    }

    protected function validateAndUpdateTicketCities($userData, $selectedCity, $flowTokenData)
    {
        User::where('id', $userData->id)->update([
            'ticket_city' => $selectedCity
        ]);
        $postData = [
            'event_type' => 'Click',
            'event_sub_type' => 'Match_Tickets_Preferred_Cities'
        ];
        $this->pushToCDP($postData, $userData);
        return ClaimFormFlowHelper::getClaimFormResponse($flowTokenData, $userData);
    }

    ########## Merch ##########

    protected function initMerchClaimForm($flowTokenData)
    {
        $userData = User::where('id', $flowTokenData->user_id)->first();
        $status = $flowTokenData->token_status;
        [$screen, $result] = ClaimFormFlowHelper::getClaimFormResponse($flowTokenData, $userData);
        $canClaim = $userData->is_merch_winner && !$userData->merch_expired && !$userData->claimed_merch;

        if ($status == FlowConstant::$statusClaimFormClaimed || !$canClaim) {
            [$screen, $result] = FlowHelper::getExpiredTokenResponse($flowTokenData->flow_token);
        }

        return [$screen, $result];
    }

    protected function updateUserDataFromClaim($summeryData, $userData, Winner $winnerData)
    {
        if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
            $userUpdateList = ['claimed_ticket' => 1];
        } elseif ($winnerData->reward_name == GenericConstant::$winTypeMerch) {
            $userUpdateList = ['claimed_merch' => 1];
        }


        if (empty($userData->name) && !empty($summeryData['name'])) {
            $userUpdateList['name'] = Hash::encryptData($summeryData['name']);
        }
        if (empty($userData->email) && !empty($summeryData['email'])) {
            $emailEnc = Hash::encryptData(strtolower($summeryData['email']));
            $emailExist = User::where('email', $emailEnc)->exists();
            if (!$emailExist) {
                $userUpdateList['email'] = $emailEnc;
            }
        }

        if (empty($userData->address_line_1) && !empty($summeryData['address_line_1'])) {
            $userUpdateList['address_line_1'] = $summeryData['address_line_1'];
        }
        if (empty($userData->address_line_2) && !empty($summeryData['address_line_2'])) {
            $userUpdateList['address_line_2'] = $summeryData['address_line_2'];
        }
        if (empty($userData->landmark) && !empty($summeryData['landmark'])) {
            $userUpdateList['landmark'] = $summeryData['landmark'];
        }
        if (empty($userData->address_pincode) && !empty($summeryData['pincode'])) {
            $userUpdateList['address_pincode'] = $summeryData['pincode'];
        }
        try {
            User::where('id', $userData->id)->update($userUpdateList);
        } catch (QueryException) {
            User::where('id', $userData->id)->update(['claimed_bumper' => 1]);
        }
    }

    protected function validateClaimFormData($input, $userData, $flowTokenData)
    {
        $data = $this->getData($input, 'data', false);

        $name = $this->getData($data, 'name');
        $email = strtolower($this->getData($data, 'email'));
        $emailEnc = Hash::encryptData($email);

        $pincode = $this->getData($data, 'pincode');

        $errorMessage = [];
        $state = null;
        $city = null;

        if (empty($name)) {
            $errorMessage['name'] = 'Name is required';
        }
        if (empty($email)) {
            $errorMessage['email'] = 'Email is required';
        } elseif (!ClaimForm::isEmail($email)) {
            $errorMessage['email'] = 'Invalid email format';
        } else {
            $emailExist = ClaimForm::emailExist($emailEnc);
            if ($emailExist) {
                $errorMessage['email'] = 'Email already used';
            }
        }

        if (empty($pincode)) {
            $errorMessage['pincode'] = 'Pincode is required';
        } else {
            [$valid, $state, $city] = PinCode::isValidPincode($pincode);
            if (!$valid) {
                $errorMessage['pincode'] = 'Invalid pincode';
            }
        }
        if (empty($errorMessage)) {
            $winnerData = Winner::where('id', $flowTokenData->data_id)->first();

            if (
                ($winnerData->reward_name == GenericConstant::$winTypeTicket && $userData->ticket_expired) ||
                ($winnerData->reward_name == GenericConstant::$winTypeMerch && $userData->merch_expired)
            ) {
                $errorMessage['pincode'] = 'Your claim period has expired.';
            } elseif (($winnerData->reward_name == GenericConstant::$winTypeTicket && $userData->claimed_ticket) ||
                ($winnerData->reward_name == GenericConstant::$winTypeMerch && $userData->claimed_merch)
            ) {
                $errorMessage['pincode'] = 'You have already claimed!';
            }
        }
        return [$errorMessage, $state, $city];
    }

    ########## generic ##########

    protected function getEncryptedResponse($req, $res, $result, $screen)
    {
        $output = [];
        if ($screen) {
            $output['screen'] = $screen;
        }
        $output['data'] = $result;

        $aesKey = hex2bin($req->getAttribute('aesKey'));
        $initialVector = hex2bin($req->getAttribute('initialVector'));

        $obj = new FlowHash();

        $encOutput = $obj->encryptResponse($output, $aesKey, $initialVector);
        return $res->write($encOutput);
    }
}
