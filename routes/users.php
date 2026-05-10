<?php
use App\Helpers\ApiResponse;

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

$userController = new UserController();
$apiPrefix = $_ENV['API_PREFIX'] ?? '/api/v1';

Flight::group($apiPrefix . '/users', function() use ($userController) {
    Flight::route('GET /', [$userController, 'index']);
    Flight::route('GET /@id:[0-9]+', [$userController, 'show']);
    Flight::route('POST /', [$userController, 'create']);
    Flight::route('PUT /@id:[0-9]+', [$userController, 'update']);
    Flight::route('PATCH /@id:[0-9]+', [$userController, 'update']);
    Flight::route('PATCH /@id:[0-9]+/status', [$userController, 'updateStatus']);
    Flight::route('DELETE /@id:[0-9]+', [$userController, 'delete']);
}, [new AuthMiddleware()]);
