<?php

Flight::route('GET /api/v1/auth/test', function() {
    ApiResponse::success(['message' => 'Ruta de autenticación funcionando correctamente']);
});
