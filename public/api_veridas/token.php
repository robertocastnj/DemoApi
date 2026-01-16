<?php

require_once __DIR__ . "/env_loader.php";
loadEnv(__DIR__ . "/.env");

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// ENV
$apiKey   = $_ENV["XPRESSID_API_KEY"];
$tokenUrl = $_ENV["XPRESSID_URL"]; // /api/v3/token

// CONFIG_DATA (igual que Postman)
$configData = [
    "platform" => "web",
    "operationMode" => "idv",
    "flowSetup" => [
        "stages" => ["selfie", "document"],
           "options"=> [
            "selfie" => [
                "liveness" => "active",
                "challengeLength" => 2                
            ]
        ],
        "core" => [
            "confirmProcess" => true
        ]
    ]
];

// cURL init
$ch = curl_init($tokenUrl);

// multipart/form-data
$postFields = [
    'data'   => json_encode($configData),

    'texts'  => new CURLFile(__DIR__ . '/xpressid/texts/es.json', 'application/json'),
    'medias' => new CURLFile(__DIR__ . '/xpressid/medias/media.json', 'application/json'),
    'styles' => new CURLFile(__DIR__ . '/xpressid/styles/styles.json', 'application/json'),
];

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
    'apikey: ' . $apiKey
],

    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode(["curl_error" => curl_error($ch)]);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

if (!isset($data["access_token"])) {
    echo json_encode([
        "token_error" => "La API no regresó access_token",
        "raw_response" => $data
    ]);
    exit;
}

echo json_encode([
    "access_token" => $data["access_token"]
]);
