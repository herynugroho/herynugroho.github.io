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
$phone = isset($input['phone']) ? $input['phone'] : '';
$action = isset($input['action']) ? $input['action'] : 'send';

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

if ($action === 'check') {
    // 1. LOGIKA CEK NOMOR: Menyesuaikan 100% dengan dokumentasi Wablas (GET Request)
    $url = "https://bdg.wablas.com/check-phone-number?phones=" . urlencode($phone);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: " . $token,
        "url: " . $domain, // Domain disisipkan di header sesuai syarat dokumentasi Wablas
        "Accept: application/json"
    ]);
} else {
    // 2. LOGIKA KIRIM PESAN: (POST Request ke Domain Masing-masing)
    $url = $domain . "/api/send-message";
    $data = [
        "phone" => $phone,
        "message" => isset($input['message']) ? $input['message'] : ''
    ];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: " . $token,
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
}

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 3. FILTER CEK NOMOR: Menerjemahkan jawaban rumit Wablas agar HTML kita paham
if ($action === 'check') {
    $resData = json_decode($response, true);
    $isValid = false;

    // API Cek Nomor Wablas biasanya mengembalikan array 'data' dengan properti 'status' => 'valid' atau 'invalid'
    if (isset($resData['data']) && is_array($resData['data'])) {
        foreach ($resData['data'] as $item) {
            if (isset($item['status']) && strtolower($item['status']) === 'valid') {
                $isValid = true;
                break;
            }
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => $isValid,
        "message" => $isValid ? "Valid" : "Invalid",
        "raw_wablas" => $resData // (Opsional) Disimpan jika ingin melihat respon mentahnya nanti
    ]);
    exit;
}

// Untuk perintah 'send', teruskan jawaban asli Wablas ke HTML
http_response_code($httpcode);
echo $response;
?>