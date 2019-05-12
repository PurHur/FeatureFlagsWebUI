<?php

require __DIR__ . '/vendor/autoload.php';

// this apps includes a super fast http server.
// run in terminal like php run.php 8080

use React\EventLoop\Factory;

$internalFeatureFlags = new \FeatureFlags\FeatureFlags(
    new \FeatureFlags\ArrayFlagConfiguration(array(
    'deliver_static_files' => true,
    'login_box' => true,
)));

$loop = React\EventLoop\Factory::create();


$staticFileDeliveryHelper = new \FeatureFlagsWebUI\Helper\StaticFileDeliveryHelper();
$errorPageHelper = new \FeatureFlagsWebUI\Helper\ErrorPageHelper();


$server = new \React\Http\Server(function (\Psr\Http\Message\ServerRequestInterface $request) use ($loop,$internalFeatureFlags, $staticFileDeliveryHelper, $errorPageHelper) {
    if ($internalFeatureFlags->isActive('deliver_static_files')) {
        // normal http requests
        if ($staticFileDeliveryHelper->isStaticFile($request)) {
            return $staticFileDeliveryHelper->deliverStaticFile($request);
        }
    }

    // backend stuff
    if ($internalFeatureFlags->isActive('follow_dynamic_routes')) {
        return 'route';
    }

    return $errorPageHelper->return404Page($request);
});


$port = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 0;

$socket = new React\Socket\Server($port, $loop);
$server->listen($socket);

// register very basic debugging
$server->on('error', function (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});

$loop->run();




