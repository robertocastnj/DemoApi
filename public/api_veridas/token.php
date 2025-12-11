<?php

require_once __DIR__ . "/env_loader.php";
loadEnv(__DIR__ . "/.env");

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// VARS DEL ENV 
$apiKey = $_ENV["XPRESSID_API_KEY"];
$xpressidUrl = $_ENV["XPRESSID_URL"];

// CONFIG_DATA de postman
$payload = [
    "data" => [
        "platform" => "web",
        "operationMode" => "idv",
        "flowSetup" => [
            "core" => [
                "confirmProcess" => true
            ],
            "stages" => [
                "selfie","document"
            ]
        ]
    ]
];

$ch = curl_init();

// evita error SSL porque esta trayendo problemas
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt_array($ch, [
    CURLOPT_URL => $xpressidUrl,  
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: $apiKey" 
    ]
]);

$response = curl_exec($ch);
$error = curl_error($ch);

if ($error) {
    echo json_encode(["php_error" => $error]);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

if (!isset($data["access_token"])) {
    echo json_encode([
        "token_error" => "La API NO regresó access_token",
        "raw" => $data
    ]);
    exit;
}

echo json_encode([
    "access_token" => $data["access_token"]
]);
exit;

?>
