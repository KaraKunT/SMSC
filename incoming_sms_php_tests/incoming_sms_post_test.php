<?php
/**
 * SMS Gateway - Incoming SMS POST Test Handler
 * PHP 7.4 Uyumlu
 * 
 * Bu dosya POST yöntemiyle gelen SMS bildirimlerini test etmek için kullanılır.
 * Tüm gelen veriler log.txt dosyasına kaydedilir.
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Başlık ayarları
header('Content-Type: application/json; charset=utf-8');

// Log dosyası yolu
$logFile = __DIR__ . '/log.txt';

// POST verilerini al
$rawInput = file_get_contents('php://input');
$postData = json_decode($rawInput, true);

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
    'method' => 'POST',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
    'raw_input' => $rawInput,
    'post_data' => $postData,
    'headers' => getallheaders(),
    'server_vars' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? '',
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
    ]
];

// SMS verilerini çıkar
$smsData = [
    'from' => $postData['from'] ?? null,
    'to' => $postData['to'] ?? null,
    'message' => $postData['message'] ?? null,
    'received_at' => $postData['received_at'] ?? null,
    'customer_uuid' => $postData['customer_uuid'] ?? null,
    'message_id' => $postData['message_id'] ?? null,
];

// Parçalı SMS kontrolü
if (isset($postData['is_multi_part']) && $postData['is_multi_part']) {
    $smsData['is_multi_part'] = true;
    $smsData['total_parts'] = (int)($postData['total_parts'] ?? 1);
    $smsData['reference'] = $postData['reference'] ?? null;
} else {
    $smsData['is_multi_part'] = false;
}

// Özel parametreler
$standardFields = ['from', 'to', 'message', 'received_at', 'customer_uuid', 'message_id', 'is_multi_part', 'total_parts', 'reference'];
$customParams = [];
if (is_array($postData)) {
    foreach ($postData as $key => $value) {
        if (!in_array($key, $standardFields)) {
            $customParams[$key] = $value;
        }
    }
}
$smsData['custom_params'] = $customParams;

// Mesaj analizi
$messageAnalysis = [];
if ($smsData['message']) {
    $messageAnalysis = [
        'length' => strlen($smsData['message']),
        'word_count' => str_word_count($smsData['message']),
        'contains_url' => preg_match('/https?:\/\/[^\s]+/', $smsData['message']) ? true : false,
        'contains_phone' => preg_match('/\b\d{10,}\b/', $smsData['message']) ? true : false,
        'encoding' => mb_detect_encoding($smsData['message'], 'UTF-8, ISO-8859-1, ASCII', true),
    ];
}

// Zaman analizi
$timeAnalysis = [];
if ($smsData['received_at']) {
    $receivedTime = (int)$smsData['received_at'];
    $currentTime = time();
    $timeAnalysis = [
        'received_timestamp' => $receivedTime,
        'current_timestamp' => $currentTime,
        'delay_seconds' => $currentTime - $receivedTime,
        'received_formatted' => date('Y-m-d H:i:s', $receivedTime),
    ];
}

// Log verilerini hazırla
$logData = [
    'request_data' => $requestData,
    'sms_data' => $smsData,
    'message_analysis' => $messageAnalysis,
    'time_analysis' => $timeAnalysis,
    'processed_at' => date('Y-m-d H:i:s'),
];

// Log dosyasına yaz
$logEntry = str_repeat('=', 80) . "\n";
$logEntry .= "POST REQUEST - " . date('Y-m-d H:i:s') . "\n";
$logEntry .= str_repeat('=', 80) . "\n";
$logEntry .= json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Yanıt hazırla
$response = [
    'success' => true,
    'message' => 'SMS bildirimi POST yöntemiyle başarıyla alındı',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => 'POST',
    'data' => [
        'from' => $smsData['from'],
        'to' => $smsData['to'],
        'message_length' => $messageAnalysis['length'] ?? 0,
        'is_multi_part' => $smsData['is_multi_part'],
        'custom_params_count' => count($customParams),
        'processing_delay' => $timeAnalysis['delay_seconds'] ?? 0,
    ]
];

// Başarı durumunu simüle et (bazen hata döndür test için)
$shouldFail = isset($postData['simulate_error']) && $postData['simulate_error'] === true;
if ($shouldFail) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Simüle edilmiş hata - Test amaçlı';
    $response['error_code'] = 'SIMULATED_ERROR';
} elseif (!$postData || !isset($postData['from']) || !isset($postData['to'])) {
    // Eksik veri kontrolü
    http_response_code(400);
    $response['success'] = false;
    $response['message'] = 'Gerekli alanlar eksik (from, to)';
    $response['error_code'] = 'MISSING_REQUIRED_FIELDS';
}

// Özel yanıt verileri ekle (müşteri isteğine göre)
if (isset($postData['return_custom_data']) && $postData['return_custom_data']) {
    $response['custom_response'] = [
        'customer_reference' => 'CUST_' . uniqid(),
        'processing_id' => 'PROC_' . time(),
        'next_action' => 'acknowledge',
        'additional_info' => 'Bu test yanıtıdır',
    ];
}

// JSON yanıt gönder
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


?>
