<?php
require_once('../connection.php');
require_once('../functions.php');
require_once('Chapa_Functions.php');

try {
    $body = file_get_contents('php://input');
    $event = json_decode($body, true);
    if ($event === null || !is_array($event)) {
        throw new Exception('Invalid request body');
    }

    // Check if the required fields are present
    $requiredFields = ['status', 'tx_ref', 'payment_method'];
    foreach ($requiredFields as $field) {
        if (!isset($event[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $status = $event['status'];
    $txRef = $event['tx_ref'];
    $paymentMethod = $event['payment_method'];

    // Only process completed payments
    if ($status !== 'success') {
        http_response_code(200);
        echo 'Payment not completed';
        return;
    }

    $courses = retrieveCourseId($event, $conn);
    if (empty($courses)) {
        throw new Exception("Could not find any courses for transaction ref: $txRef");
    }

    foreach ($courses as $courseId) {
        $enrolStudents = enrolStudent($txRef, $courseId, $conn2);
        if (!$enrolStudents) {
            throw new Exception("Failed to enrol students for transaction ref: $txRef and courseId: $courseId");
        }
    }

    http_response_code(200);
    echo 'Payment processed successfully';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}

