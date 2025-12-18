<?php

namespace App\Helper;

use App\Models\User;

class Whatsapp
{
    private $botNo;
    private $baseURL;
    private $authToken;

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->botNo = $this->container->WA_BOT_NO;
        $this->baseURL = $this->container->WA_BASE_URL;
        $this->authToken = $this->container->WA_AUTH_TOKEN;
    }


    public function getBumperForfeitedMessage($userData, $messageId)
    {
        $from = $this->botNo;
        $userLanguage = User::getLanguage($userData->language);

        [$templateName, $templateLang] = match ($userLanguage) {
            'hi' => ['otg_reward_forfeit_hi', 'hi'],
            'te' => ['otg_reward_forfeit_te', 'te'],
            'bn' => ['otg_reward_forfeit_bn', 'bn'],
            'pa' => ['otg_reward_forfeit_pa', 'pa'],
            'or' => ['otg_reward_forfeit_or', 'hi'],
            'gu' => ['otg_reward_forfeit_gu', 'gu'],
            'mr' => ['otg_reward_forfeit_mr', 'mr'],
            'ta' => ['otg_reward_forfeit_ta', 'ta'],
            default => ['otg_reward_forfeit_en', 'en'],
        };

        $to = Hash::decryptData($userData->mobile);

        return [
            [
                "from" => $from,
                "to" => "91" . $to,
                "messageId" => $messageId,
                "content" => [
                    "templateName" => $templateName,
                    "templateData" => [
                        "body" => [
                            "placeholders" => [Hash::decryptData($userData->name)],
                        ],
                        "buttons" => [
                            [
                                "type" => "FLOW"
                            ]
                        ]
                    ],
                    "language" => $templateLang
                ],
            ],
            $templateName
        ];
    }

    public function getNudgeTicketClaimFormMessage($userData, $messageId, $token)
    {
        $from = $this->botNo;
        $userLanguage = User::getLanguage($userData->language);

        [$templateName, $templateLang] = match ($userLanguage) {
            // 'ta' => ['otg_tn_merch_claim_nudge_en', 'ta'],
            'ta' => ['otg_tn_ticket_claim_nudge_en', 'en'],
            default => ['otg_tn_ticket_claim_nudge_en', 'en'],
        };

        $to = Hash::decryptData($userData->mobile);

        return [
            "from" => $from,
            "to" => "91" . $to,
            "messageId" => $messageId,
            "content" => [
                "templateName" => $templateName,
                "templateData" => [
                    "body" => [
                        "placeholders" => [],
                    ],
                    "buttons" => [
                        [
                            "type" => "FLOW",
                            "flowToken" => $token,
                        ]
                    ]
                ],
                "language" => $templateLang
            ],
        ];
    }

    public function getNudgeMerchClaimFormMessage($userData, $messageId, $token)
    {
        $from = $this->botNo;
        $userLanguage = User::getLanguage($userData->language);

        [$templateName, $templateLang] = match ($userLanguage) {
            'ta' => ['otg_tn_merch_claim_nudge_en', 'en'],
            default => ['otg_tn_merch_claim_nudge_en', 'en'],
        };

        $to = Hash::decryptData($userData->mobile);

        return [
            "from" => $from,
            "to" => "91" . $to,
            "messageId" => $messageId,
            "content" => [
                "templateName" => $templateName,
                "templateData" => [
                    "body" => [
                        "placeholders" => [],
                    ],
                    "buttons" => [
                        [
                            "type" => "FLOW",
                            "flowToken" => $token,
                        ]
                    ]
                ],
                "language" => $templateLang
            ],
        ];
    }

    public function getBuyOutMessage($userData, $messageId, $buyNowList)
    {
        $userLanguage = User::getLanguage($userData->language);
        $cards = [];
        foreach ($buyNowList as $buyNow) {
            $cards[] = [
                "header" => [
                    "type" => "IMAGE",
                    "mediaUrl" => $buyNow->url
                ],
                "body" => [
                    "placeholders" => []
                ]
            ];
            $templateName = $buyNow->template_name;
            $templateLang = $buyNow->language;
        }
        $to = Hash::decryptData($userData->mobile);

        return [
            "from" => $this->botNo,
            "to" => "91" . $to,
            "messageId" => $messageId,
            "content" => [
                "templateName" => $templateName,
                "templateData" => [
                    "body" => [
                        "placeholders" => []
                    ],
                    "carousel" => [
                        "cards" => $cards
                    ]
                ],
                "language" => $templateLang
            ]
        ];
    }

    ################################################################

    public function sendWhatsappMessage($messages)
    {
        $postBody = json_encode([
            "messages" => $messages
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseURL . '/whatsapp/1/message/template',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postBody,
            CURLOPT_HTTPHEADER => array(
                'Authorization: App ' . $this->authToken,
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
