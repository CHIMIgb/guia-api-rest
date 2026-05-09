<?php

Flight::route('GET /api/v1/users/test', function() {
    ApiResponse::success(['message' => 'Ruta de usuarios funcionando correctamente']);
});
