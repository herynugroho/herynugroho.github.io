<?php
// Mengizinkan request dari mana saja (solusi CORS untuk GitHub Pages)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Menangani Preflight Request (OPTIONS) dari browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Mengambil data JSON dari frontend (GitHub Pages)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => false, "message" => "Data tidak valid"]);
    exit;
}

$domain = rtrim($input['domain'], '/');
$token = $input['token'];
$url = $domain . "/api/send-message";

// Data yang akan dikirim ke Wablas
$data = [
    "phone" => $input['phone'],
    "message" => $input['message']
];

// Inisiasi cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: " . $token,
    "Content-Type: application/json",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Mengembalikan respons Wablas ke frontend
http_response_code($httpcode);
echo $response;
?>