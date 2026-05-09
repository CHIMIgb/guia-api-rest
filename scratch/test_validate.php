<?php
$ch = curl_init('http://localhost:8080/api/v1/auth/validate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
echo "Response: $response\n";
