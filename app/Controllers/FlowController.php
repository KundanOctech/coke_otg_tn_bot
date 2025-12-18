<?php

namespace App\Controllers;

use App\Helper\FlowHelper;
use App\Models\FlowToken;
use App\Models\User;

// ping
// INIT
// data_exchange
// BACK

class FlowController extends FlowHelperController
{

    public function addUniqueCode($req, $res)
    {
        $input = $req->getAttribute('inputBody');

        $action = strtolower($this->getData($input, 'action'));
        $flowToken = strtoupper($this->getData($input, 'flow_token'));
        $srcScreen = $this->getData($input, 'screen');

        $flowTokenData = null;
        if (!empty($flowToken)) {
            $flowTokenData = FlowToken::where('flow_token', $flowToken)->first();
        }

        $result = $this->pingAddCode();
        $screen = '';

        if ($action !== 'ping') {
            $hasError = false;
            [$screen, $result] = FlowHelper::getEmptyTokenResponse();
            if (empty($flowTokenData) || strtotime($flowTokenData->expires_at) < strtotime('now')) {
                [$screen, $result] = FlowHelper::getExpiredTokenResponse($flowToken);
                $hasError = true;
            }

            if ($hasError) {
                return $this->getEncryptedResponse($req, $res, $result, $screen);
            }
        }

        if ($action === 'init') {
            [$screen, $result] = $this->initAddCode($flowTokenData);
        } elseif ($action === 'back') {
            [$screen, $result] = $this->backAddCode($flowTokenData);
        } elseif ($action === 'data_exchange') {
            [$screen, $result] = $this->dataExchangeAddCode($input, $flowTokenData, $srcScreen);
        }

        return $this->getEncryptedResponse($req, $res, $result, $screen);
    }

    public function ticketClaimForm($req, $res)
    {
        $input = $req->getAttribute('inputBody');

        $action = strtolower($this->getData($input, 'action'));
        $srcScreen = $this->getData($input, 'screen');
        $flowToken = strtoupper($this->getData($input, 'flow_token'));

        $flowTokenData = null;
        if (!empty($flowToken)) {
            $flowTokenData = FlowToken::where('flow_token', $flowToken)->first();
        }

        $result = $this->pingAddCode();
        $screen = '';

        if ($action !== 'ping') {
            $hasError = false;
            [$screen, $result] = FlowHelper::getEmptyTokenResponse();
            if (empty($flowTokenData) || strtotime($flowTokenData->expires_at) < strtotime('now')) {
                [$screen, $result] = FlowHelper::getExpiredTokenResponse($flowToken);
                $hasError = true;
            }

            if ($hasError) {
                return $this->getEncryptedResponse($req, $res, $result, $screen);
            }
        }

        if ($action === 'init' || $action === 'back') {
            [$screen, $result] = $this->initTicketClaimForm($flowTokenData);
        } elseif ($action === 'data_exchange') {
            [$screen, $result] = $this->dataExchangeTicketClaimForm($input, $flowTokenData, $srcScreen);
        }
        return $this->getEncryptedResponse($req, $res, $result, $screen);
    }

    public function merchClaimForm($req, $res)
    {
        $input = $req->getAttribute('inputBody');

        $action = strtolower($this->getData($input, 'action'));
        $srcScreen = $this->getData($input, 'screen');
        $flowToken = strtoupper($this->getData($input, 'flow_token'));

        $flowTokenData = null;
        if (!empty($flowToken)) {
            $flowTokenData = FlowToken::where('flow_token', $flowToken)->first();
        }

        $result = $this->pingAddCode();
        $screen = '';

        if ($action !== 'ping') {
            $hasError = false;
            [$screen, $result] = FlowHelper::getEmptyTokenResponse();
            if (empty($flowTokenData) || strtotime($flowTokenData->expires_at) < strtotime('now')) {
                [$screen, $result] = FlowHelper::getExpiredTokenResponse($flowToken);
                $hasError = true;
            }

            if ($hasError) {
                return $this->getEncryptedResponse($req, $res, $result, $screen);
            }
        }

        if ($action === 'init' || $action === 'back') {
            [$screen, $result] = $this->initMerchClaimForm($flowTokenData);
        } elseif ($action === 'data_exchange') {
            [$screen, $result] = $this->dataExchangeMerchClaimForm($input, $flowTokenData, $srcScreen);
        }
        return $this->getEncryptedResponse($req, $res, $result, $screen);
    }

    public function testFun()
    {
        // die();
        $token = '1963c6d7-e44c-423f-8817-0c14f194b789';
        $input = [
            "data" => [
                "unique_code" => "Rfjdkdjdjd",
                "submit" => true
            ],
            "flow_token" => "1963c6d7-e44c-423f-8817-0c14f194b789",
            "screen" => "UNIQUE_CODE",
            "action" => "data_exchange",
            "version" => "3.0"
        ];

        $flowTokenData = FlowToken::where('flow_token', $token)->first();
        $srcScreen = 'QNA';
        // $response = $this->initTicketClaimForm($input, $flowTokenData, $srcScreen);
        // $response = $this->initTicketClaimForm($flowTokenData);
        // $userData = User::where('id', $flowTokenData->user_id)->first();
        echo 'testFun<br>';
        $response = $this->dataExchangeAddCode($input, $flowTokenData, $srcScreen);
        echo json_encode($response);
    }
}
