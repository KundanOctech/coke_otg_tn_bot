<?php
require __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App([
    'setting' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
        'db' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => 'y26_01_otg_tn',
            'username' => 'root',
            'password' => 'root',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix'    => 'otg_tn_',
        ]
    ]
]);

$container = $app->getContainer();

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['setting']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        $str = base64_decode(json_encode([
            "statusCode" => 404,
            "message" => "Invalid request",
        ]));
        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode(['resp' => $str]));
    };
};

date_default_timezone_set("Asia/Kolkata");

$container['CDS_BASE_URL'] = "apac.gcds.coke.com";
$container['CDS_CLIENT_ID'] = "52055b17-5a2d-4f16-8956-dcee28cc047f";
$container['CDS_API_KEY'] = "km5f19535bO9Fb4l4280nB0DBw9B3494233551vp";

// $container['CDS_BASE_URL'] = "beta.apac.gcds.coke.com";
// $container['CDS_CLIENT_ID'] = "fb4a945f-761a-4792-b663-56287d96872a";
// $container['CDS_API_KEY'] = "Pm56B5d11fpB79BU4a12GBf77t535A0C7a2da6mz";

// $container['MIXCODE_BASE_URL'] = "https://test-mixcodes.coke.com";
// $container['MIXCODE_PROGRAM_ID'] = "01JYK4EARJPQQRP42236XZVDGC";
// $container['MIXCODE_SECRET_KEY'] = "MDFKWUs0RUFSSlBRUVJQNDIyMzZYWlZER0M6MjBkODg3YjUtNjUzMi00ZDEwLWE5ZDItZGE2Y2JjNGQyZjZi";

$container['MIXCODE_BASE_URL'] = "https://mixcodes.coke.com";
$container['MIXCODE_PROGRAM_ID'] = "01K9PFH3HRF1W3XDYFC6YP5WMP";
$container['MIXCODE_SECRET_KEY'] = "MDFLOVBGSDNIUkYxVzNYRFlGQzZZUDVXTVA6YWUwNDUyNDctOTljYy00MzNkLTk3YjYtM2RiMWU5MmVlZGMy";

// $container['WA_BOT_NO'] = "918130658881";
// $container['WA_BASE_URL'] = "https://3v4zdv.api.infobip.com";
// $container['WA_AUTH_TOKEN'] = "d6a0c6725636b5fabbf56a7ada137cc1-7ad6546a-3c6f-4852-ac18-85a622add089";

$container['WA_BOT_NO'] = "919220370800";
$container['WA_BASE_URL'] = "https://d92p28.api.infobip.com/";
$container['WA_AUTH_TOKEN'] = "4f20214b047991695e0c77f4a28ecf3d-316f9a8b-a6ea-4573-9904-e4c25d95a50f";


$container['db'] = function ($container) use ($capsule) {
    return $capsule;
};

$container["UsersController"] = function ($container) {
    return new \App\Controllers\UsersController($container);
};
$container["FlowController"] = function ($container) {
    return new \App\Controllers\FlowController($container);
};

$container["DashboardController"] = function ($container) {
    return new \App\Controllers\DashboardController($container);
};

$container["BackendController"] = function ($container) {
    return new \App\Controllers\BackendController($container);
};


require __DIR__ . './../app/routes.php';
