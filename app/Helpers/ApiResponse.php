<?php

class ApiResponse {
    public static function success($data, int $status = 200): void {
        http_response_code($status);
        echo json_encode([
            "success" => true,
            "data"    => $data,
            "error"   => null,
        ]);
        exit;
    }

    public static function error(string $code, string $message, $details = null, int $status = 400): void {
        http_response_code($status);
        echo json_encode([
            "success" => false,
            "data"    => null,
            "error"   => [
                "code"    => $code,
                "message" => $message,
                "details" => $details,
            ],
        ]);
        exit;
    }
}
