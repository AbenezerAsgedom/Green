<?php

require_once __DIR__ . "/Model/ResponseData.php";

use Chapa\Model\ResponseData;

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // No data posted
    exit;
}

// Get the JSON payload from Chapa
$payload = file_get_contents('php://input');

// Create a ResponseData object from the payload
$responseData = new ResponseData($payload, http_response_code());

// Connect to the database
$databaseHost = 'localhost';
$databaseName = 'etemar5_project';
$databaseUsername = 'etemaritest';
$databasePassword = 'eTemari@123';

try {
    $db = new PDO("mysql:host=$databaseHost;dbname=$databaseName", $databaseUsername, $databasePassword);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the INSERT statement
    $stmt = $db->prepare("INSERT INTO transaction_chapa (transaction_id, Status, data) VALUES (:status_code, :message, :status, :data)");

    // Bind the parameters
    $stmt->bindParam(':status_code', $responseData->getStatusCode());
    $stmt->bindParam(':message', $responseData->getMessage());
    $stmt->bindParam(':status', $responseData->getStatus());
    $stmt->bindParam(':data', json_encode($responseData->getData()));

    // Execute the statement
    $stmt->execute();

    echo "Data inserted successfully into the database.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
