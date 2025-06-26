<?php
/**
 * PayDisini API Integration
 * Handles QRIS payment processing
 */

class PayDisini {
    private $apiKey;
    private $baseUrl = 'https://paydisini.co.id/api/';
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: 'ff79be802563e5dc1311c227a72d17c1';
    }
    
    /**
     * Create QRIS payment
     */
    public function createPayment($amount, $orderId, $userEmail = null) {
        $data = [
            'key' => $this->apiKey,
            'request' => 'new',
            'unique_code' => $orderId,
            'service' => 11, // QRIS
            'amount' => $amount,
            'note' => 'RideMax Payment - Order #' . $orderId,
            'valid_time' => 3600, // 1 hour
            'type_fee' => 1 // Fee paid by merchant
        ];
        
        if ($userEmail) {
            $data['ewallet_phone'] = $userEmail;
        }
        
        return $this->makeRequest($data);
    }
    
    /**
     * Check payment status
     */
    public function checkStatus($uniqueCode) {
        $data = [
            'key' => $this->apiKey,
            'request' => 'status',
            'unique_code' => $uniqueCode
        ];
        
        return $this->makeRequest($data);
    }
    
    /**
     * Make API request
     */
    private function makeRequest($data) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'message' => 'HTTP Error: ' . $httpCode
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'Invalid JSON response'
            ];
        }
        
        return $result;
    }
}

// Global helper function
function createPayDisiniPayment($amount, $orderId, $userEmail = null) {
    $paydisini = new PayDisini();
    return $paydisini->createPayment($amount, $orderId, $userEmail);
}

function checkPayDisiniStatus($uniqueCode) {
    $paydisini = new PayDisini();
    return $paydisini->checkStatus($uniqueCode);
}
?>