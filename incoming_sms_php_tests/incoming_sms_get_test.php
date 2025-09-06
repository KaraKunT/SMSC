<?php
/**
 * SMS Gateway - Incoming SMS GET Test Handler
 * PHP 7.4 Uyumlu
 * 
 * Bu dosya GET yöntemiyle gelen SMS bildirimlerini test etmek için kullanılır.
 * Tüm gelen veriler log.txt dosyasına kaydedilir.
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Başlık ayarları
header('Content-Type: application/json; charset=utf-8');

// Log dosyası yolu
$logFile = __DIR__ . '/log.txt';

/**
 * PHP 7.4 uyumlu getallheaders fonksiyonu
 */
if (!function_exists('getallheaders')) {
	function getallheaders() {
		 $headers = [];
		 foreach ($_SERVER as $name => $value) {
			  if (substr($name, 0, 5) == 'HTTP_') {
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			  }
		 }
		 return $headers;
	}
}

// Gelen tüm verileri topla
$requestData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => 'GET',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'get_params' => $_GET,
    'headers' => getallheaders(),
    'server_vars' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? '',
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? '',
    ]
];

// JWT Token kontrolü
$authToken = $_GET['auth_token'] ?? null;
$jwtValid = false;
$jwtPayload = null;

if ($authToken) {
    $jwtPayload = parseJWTToken($authToken);
    $jwtValid = ($jwtPayload !== null);
}

// SMS verilerini çıkar
$smsData = [
    'from' => $_GET['from'] ?? null,
    'to' => $_GET['to'] ?? null,
    'message' => $_GET['message'] ?? null,
    'received_at' => $_GET['received_at'] ?? null,
    'auth_token' => $authToken,
    'jwt_valid' => $jwtValid,
    'jwt_payload' => $jwtPayload,
];

// Parçalı SMS kontrolü
if (isset($_GET['total_parts']) && $_GET['total_parts'] > 1) {
    $smsData['is_multi_part'] = true;
    $smsData['total_parts'] = (int)$_GET['total_parts'];
    $smsData['reference'] = $_GET['reference'] ?? null;
} else {
    $smsData['is_multi_part'] = false;
}

// Özel parametreler
$customParams = [];
foreach ($_GET as $key => $value) {
    if (!in_array($key, ['from', 'to', 'message', 'received_at', 'auth_token', 'total_parts', 'reference'])) {
        $customParams[$key] = $value;
    }
}
$smsData['custom_params'] = $customParams;

// Log verilerini hazırla
$logData = [
    'request_data' => $requestData,
    'sms_data' => $smsData,
    'processed_at' => date('Y-m-d H:i:s'),
];

// Log dosyasına yaz
$logEntry = str_repeat('=', 80) . "\n";
$logEntry .= "GET REQUEST - " . date('Y-m-d H:i:s') . "\n";
$logEntry .= str_repeat('=', 80) . "\n";
$logEntry .= json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Yanıt hazırla
$response = [
    'success' => true,
    'message' => 'SMS bildirimi GET yöntemiyle başarıyla alındı',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => 'GET',
    'data' => [
        'from' => $smsData['from'],
        'to' => $smsData['to'],
        'message_length' => $smsData['message'] ? strlen($smsData['message']) : 0,
        'is_multi_part' => $smsData['is_multi_part'],
        'jwt_valid' => $jwtValid,
        'custom_params_count' => count($customParams),
    ]
];

// Başarı durumunu simüle et (bazen hata döndür test için)
$shouldFail = isset($_GET['simulate_error']) && $_GET['simulate_error'] === 'true';
if ($shouldFail) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Simüle edilmiş hata - Test amaçlı';
    $response['error_code'] = 'SIMULATED_ERROR';
}

// JSON yanıt gönder
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

/**
 * Basit JWT token parse fonksiyonu (güvenlik kontrolü yapmaz, sadece payload'ı çıkarır)
 */
function parseJWTToken($token) {
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        // Payload kısmını decode et
        $payload = base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT));
        $decoded = json_decode($payload, true);
        
        return $decoded ?: null;
    } catch (Exception $e) {
        return null;
    }
}


?>
