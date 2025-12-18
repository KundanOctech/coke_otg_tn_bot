<?php

namespace App\Controllers;

use App\Constant\FlowConstant;
use App\Constant\GenericConstant;
use App\Helper\Hash;
use App\Helper\Whatsapp;
use App\Models\ClaimForm;
use App\Models\FlowToken;
use App\Models\MessageCount;
use App\Models\Survey;
use App\Models\UniqueCode;
use App\Models\User;
use App\Models\Winner;

class BackendHelperController extends Controller
{

    protected function getOverviewData($userData)
    {
        $dataHeader = [
            [
                "title" => "Overview",
                "dataIndex" => 0,
                "type" => "text"

            ],
            [
                "title" => "Data",
                "dataIndex" => 1,
                "type" => "text"

            ],
        ];
        $data = [
            ['Name', Hash::decryptData($userData->name)],
            ['Mobile', Hash::decryptData($userData->mobile)],
            ['Language', $userData->language],
            ['source', $userData->source],
            ['Unique code count', $userData->unique_code_count],
            ['Valid code count', $userData->valid_code_count],
            ['Mass reward count', $userData->cashback_count],
            ['Is ticket winner', $userData->is_ticket_winner ? 'Yes' :   'No'],
            ['Claimed ticket', $userData->claimed_ticket ? 'Yes' :   'No'],
            ['Ticket expired', $userData->ticket_expired ? 'Yes' :   'No'],
            ['Is merch winner', $userData->is_merch_winner ? 'Yes' :   'No'],
            ['Claimed merch', $userData->claimed_merch ? 'Yes' :   'No'],
            ['Merch expired', $userData->merch_expired ? 'Yes' :   'No'],
            ['Registered at', date('Y-m-d H:i:s', strtotime($userData->created_at))]
        ];
        return [$dataHeader, $data];
    }

    protected function getUniqueCodeData($userId)
    {
        $uniqueCodes = UniqueCode::where('user_id', $userId)
            ->orderBy('created_at')
            ->get();
        $dataHeader = [
            [
                "title" => "Sl.No.",
                "dataIndex" => 0,
                "type" => "text"
            ],
            [
                "title" => "Code",
                "dataIndex" => 1,
                "type" => "text"
            ],
            [
                "title" => "Added at",
                "dataIndex" => 2,
                "type" => "text"
            ]
        ];
        $data = [];
        $i = 1;
        foreach ($uniqueCodes as $codeData) {
            $data[] = [
                $i,
                $codeData->code,
                date('Y-m-d H:i:s', strtotime($codeData->created_at))
            ];
            $i++;
        }
        return [$dataHeader, $data];
    }

    protected function getRewardsData($userId)
    {
        $winners = Winner::where('user_id', $userId)
            ->orderBy('id')
            ->get();
        $dataHeader = [
            [
                "title" => "Unique code",
                "dataIndex" => 0,
                "type" => "text"
            ],
            [
                "title" => "Reward type",
                "dataIndex" => 1,
                "type" => "text"
            ],
            [
                "title" => "Reward Name",
                "dataIndex" => 2,
                "type" => "text"
            ],
            [
                "title" => "Won at",
                "dataIndex" => 3,
                "type" => "text"
            ]
        ];
        $data = [];
        foreach ($winners as $winner) {
            $data[] = [
                $winner->unique_code,
                $winner->reward_type,
                $winner->reward_name,
                date('Y-m-d H:i:s', strtotime($winner->created_at))
            ];
        }
        return [$dataHeader, $data];
    }
    protected function getClaimFormData($userId)
    {
        $claimFormData = ClaimForm::where('user_id', $userId)->first();
        $dataHeader = [
            [
                "title" => "Name",
                "dataIndex" => 0,
                "type" => "text"
            ],
            [
                "title" => "Email",
                "dataIndex" => 1,
                "type" => "text"
            ],
            [
                "title" => "Address 1",
                "dataIndex" => 2,
                "type" => "text"
            ],
            [
                "title" => "Address 2",
                "dataIndex" => 3,
                "type" => "text"
            ],
            [
                "title" => "Pincode",
                "dataIndex" => 4,
                "type" => "text"
            ],
            [
                "title" => "State",
                "dataIndex" => 5,
                "type" => "text"
            ],
            [
                "title" => "City",
                "dataIndex" => 6,
                "type" => "text"
            ],
            [
                "title" => "Landmark",
                "dataIndex" => 7,
                "type" => "text"
            ],
            [
                "title" => "Ticket City",
                "dataIndex" => 8,
                "type" => "text"
            ],
            [
                "title" => "Created at",
                "dataIndex" => 9,
                "type" => "text"
            ]
        ];
        if (!empty($claimFormData)) {
            $data = [[
                Hash::decryptData($claimFormData->name),
                Hash::decryptData($claimFormData->email),
                $claimFormData->address_1,
                $claimFormData->address_2,
                $claimFormData->pincode,
                $claimFormData->state,
                $claimFormData->city,
                $claimFormData->landmark,
                $claimFormData->ticket_city,
                date('Y-m-d H:i:s', strtotime($claimFormData->created_at))
            ]];
        } else {
            $data = [];
        }

        return [$dataHeader, $data];
    }

    protected function getSurveyData($userId)
    {
        $winners = Survey::where('user_id', $userId)
            ->orderBy('id')
            ->get();
        $dataHeader = [
            [
                "title" => "answer_1",
                "dataIndex" => 0,
                "type" => "text"
            ],
            [
                "title" => "answer_2",
                "dataIndex" => 1,
                "type" => "text"
            ],
            [
                "title" => "answer_3",
                "dataIndex" => 2,
                "type" => "text"
            ],
            [
                "title" => "Filed at",
                "dataIndex" => 3,
                "type" => "text"
            ]
        ];
        $data = [];
        foreach ($winners as $winner) {
            $data[] = [
                $winner->answer_1,
                $winner->answer_2,
                $winner->answer_3,
                date('Y-m-d H:i:s', strtotime($winner->created_at))
            ];
        }
        return [$dataHeader, $data];
    }

    protected function sendForfeitedNudgeWaMessage($winnerData, $cdpEvent)
    {
        $userData = User::where('id', $winnerData->user_id)->first();
        $waObj = new Whatsapp($this->container);
        $messageId = User::getUuid4Key();

        [$message, $templateId] = $waObj->getBumperForfeitedMessage($userData, $messageId);
        $waObj->sendWhatsappMessage([$message]);
        MessageCount::addTemplateMessageCount($userData, 'FORFEITED-NUDGE', $messageId, $templateId);

        $postData = [
            'event_type' => 'Click',
            'event_sub_type' => $cdpEvent
        ];
        $this->pushToCDP($postData, $userData);
    }

    protected function sendClaimFormNudgeMessage($winnerData, $day)
    {
        $userData = User::where('id', $winnerData->user_id)->first();
        if (empty($userData)) {
            return;
        }
        $messageId = User::getUuid4Key();

        $token = FlowToken::getFlowToken($userData->id, FlowConstant::$typeClaimForm . '_' . $day, false);
        $waObj = new Whatsapp($this->container);

        if ($winnerData->reward_name == GenericConstant::$winTypeTicket) {
            $message = $waObj->getNudgeTicketClaimFormMessage($userData, $messageId, $token);
        } else {
            $message = $waObj->getNudgeMerchClaimFormMessage($userData, $messageId, $token);
        }
        $waObj->sendWhatsappMessage([$message]);
        echo json_encode($message);
    }
}
