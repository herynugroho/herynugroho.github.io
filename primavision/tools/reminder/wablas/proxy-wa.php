<?php
// Mengizinkan request dari mana saja (solusi CORS untuk GitHub Pages / Frontend External)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Menangani Preflight Request (OPTIONS) dari browser pengguna
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Mengambil data JSON dari frontend (Aplikasi WA Reminder HTML)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => false, "message" => "Data tidak valid atau kosong"]);
    exit;
}

$domain = rtrim($input['domain'], '/');
$token = $input['token'];

// Membaca parameter 'action' dari HTML (default ke 'send' jika tidak diisi)
$action = isset($input['action']) ? $input['action'] : 'send';

// LOGIKA DINAMIS: Menentukan Endpoint URL dan Struktur Data berdasarkan kebutuhan HTML
if ($action === 'check') {
    // Menuju API internal Wablas untuk validasi status nomor WA aktif/mati
    $url = $domain . "/api/check-phone";
    $data = [
        "phone" => $input['phone']
    ];
} else {
    // Menuju API internal Wablas untuk pelepasan broadcast pesan teks resmi
    $url = $domain . "/api/send-message";
    $data = [
        "phone" => $input['phone'],
        "message" => $input['message']
    ];
}

// Inisiasi proses transmisi cURL ke server Wablas
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

// Meneruskan response mentah dari server Wablas secara transparan kembali ke HTML
http_response_code($httpcode);
echo $response;
?>