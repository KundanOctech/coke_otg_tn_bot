<?php

use App\Middleware\ValidateUserMiddleware;
use App\Middleware\UserRegisteredMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\FlowMiddleware;
use App\Middleware\DashboardAuthMiddleware;

$app->group('/users', function () {
    $this->post('/unique-code', 'FlowController:addUniqueCode');
    $this->post('/claim-form-ticket', 'FlowController:ticketClaimForm');
    $this->post('/claim-form-merch', 'FlowController:merchClaimForm');
})
    ->add(new FlowMiddleware());


$app->group('/users', function () {
    $this->post('/register', 'UsersController:register');

    $this->group('', function () {
        $this->group('', function () {
            $this->post('/language', 'UsersController:setLanguage');
            $this->post('/brand', 'UsersController:chooseBrand');
            $this->post('/unsubscribe', 'UsersController:unsubscribe');
            $this->post('/my-wins', 'UsersController:myWins');

            $this->post('/can-take-survey', 'UsersController:canTakeSurvey');
            $this->post('/survey', 'UsersController:completeSurvey');

            $this->post('/flow-token', 'UsersController:getFlowToken');
            $this->post('/message-sent', 'UsersController:messageSent');
            $this->post('/profanity', 'UsersController:messageContainsProfanity');
            $this->post('/buy-now', 'UsersController:getBuyOutMessage');
        })
            ->add(new UserRegisteredMiddleware());

        $this->post('/action', 'UsersController:trackAction');
    })
        ->add(new ValidateUserMiddleware());
})
    ->add(new AuthMiddleware());

$app->post('/admin/deleteData/{key}', 'BackendController:deleteData');
$app->get('/sendCDP/{key}[/{batch}]', 'BackendController:sendCDP');
$app->get('/nudge/forfeited-message/{key}', 'BackendController:sendForfeitedNudgeMessage');
$app->get('/nudge/claim-form-nudge/{key}/{day}', 'BackendController:sendClaimFormNudge');
$app->get('/update-reward-carry-forward/{key}', 'BackendController:updateRewardCarryForward');
$app->get('/activate-bumper-reward/{key}', 'BackendController:activateBumperRewardCode');
$app->get('/make-roi-bumper-winner/{key}', 'BackendController:makeRoiBumperWinner');

$app->group('', function () {
    $this->post('/admin/getUserHistory/{key}', 'BackendController:getUserHistory');
    $this->post('/admin/getUniqueCodeHistory/{key}', 'BackendController:getUniqueCodeHistory');

    $this->get('/report/claim-form/{key}', 'DashboardController:claimForm');
})
    ->add(new DashboardAuthMiddleware());

$app->get('/users/test', 'UsersController:testFun');
$app->get('/flow/test', 'FlowController:testFun');
$app->get('/emailReport/{key}', 'DashboardController:emailReport');

$app->group('/looker-studio', function () {
    $this->get('/lookerData/{key}', 'DashboardController:lookerData');
    $this->get('/dayWiseData/{key}', 'DashboardController:dayWiseData');
    $this->get('/trafficWiseData/{key}', 'DashboardController:trafficWiseData');
    $this->get('/stateWiseDistribution/{key}', 'DashboardController:lsStateDistribution');
    $this->get('/journey-analysis[/{key}]', 'DashboardController:journeyAnalysis');
    $this->get('/unique-code-distribution/{key}', 'DashboardController:lsUniqueCodeDistribution');
});
