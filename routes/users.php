<?php
use App\Helpers\ApiResponse;

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

$userController = new UserController();

Flight::group('/api/v1/users', function() use ($userController) {
    Flight::route('GET /', [$userController, 'index']);
    Flight::route('GET /@id', [$userController, 'show']);
}, [new AuthMiddleware()]);
