<?php
// scratch/test_dual_token.php

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init('http://localhost:8080' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

echo "====================================\n";
echo "1. HACIENDO LOGIN\n";
echo "====================================\n";
$loginResponse = makeRequest('/api/v1/auth/login', 'POST', [
    'usuario' => 'admin_juan',
    'contrasena' => 'secret123'
]);

if (!$loginResponse['success']) {
    die("Error en login: " . json_encode($loginResponse['error']) . "\n");
}

$accessToken = $loginResponse['data']['access_token'];
$refreshToken = $loginResponse['data']['refresh_token'];

echo "Login exitoso!\n";
echo "  [+] Access Token: " . substr($accessToken, 0, 30) . "...\n";
echo "  [+] Refresh Token: $refreshToken\n\n";


echo "====================================\n";
echo "2. VALIDANDO EL ACCESS TOKEN\n";
echo "====================================\n";
$validateResponse = makeRequest('/api/v1/auth/validate', 'GET', null, $accessToken);
if ($validateResponse['success']) {
    echo "Access Token validado! Le quedan " . $validateResponse['data']['expires_in_seconds'] . " segundos de vida.\n\n";
} else {
    echo "Fallo al validar: " . json_encode($validateResponse['error']) . "\n\n";
}


echo "====================================\n";
echo "3. REFRESCO DE TOKENS (Simulando que pasó el tiempo)\n";
echo "====================================\n";
echo "Enviando el Refresh Token guardado...\n";

$refreshResponse = makeRequest('/api/v1/auth/refresh', 'POST', [
    'refresh_token' => $refreshToken
]);

if (!$refreshResponse['success']) {
    die("Error al refrescar: " . json_encode($refreshResponse['error']) . "\n");
}

$newAccessToken = $refreshResponse['data']['access_token'];
$newRefreshToken = $refreshResponse['data']['refresh_token'];

echo "Refresco exitoso!\n";
echo "  [+] NUEVO Access Token: " . substr($newAccessToken, 0, 30) . "...\n";
echo "  [+] NUEVO Refresh Token: $newRefreshToken\n\n";


echo "====================================\n";
echo "4. PROBANDO ROTACIÓN DE SEGURIDAD\n";
echo "====================================\n";
echo "Intentando usar el Refresh Token VIEJO de nuevo (Simulando un hacker que se lo robó)...\n";

$hackerResponse = makeRequest('/api/v1/auth/refresh', 'POST', [
    'refresh_token' => $refreshToken // Usamos el viejo a propósito
]);

if (!$hackerResponse['success']) {
    echo "¡Seguridad funcionando! El servidor rechazó el token viejo:\n";
    echo "  Motivo: " . $hackerResponse['error']['message'] . "\n\n";
} else {
    echo "Peligro: El servidor aceptó el token viejo.\n\n";
}

echo "====================================\n";
echo "TEST FINALIZADO CON ÉXITO\n";
echo "====================================\n";
