<?php
require_once('../Connection.php');
require_once('Santim_Functions.php');

try {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    
    if (isset($data) && $data !== null) {
        $paymentUrl = generatePaymentURL($data, $conn);

        if ($paymentUrl !== false) {  // If the payment URL was successfully created
            $response = [
                'success' => true,
                'paymentUrl' => $paymentUrl
            ];
        } else {
            throw new Exception('Failed to generate payment URL');
        }
    } else {
        throw new Exception('Data is null or not set');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
