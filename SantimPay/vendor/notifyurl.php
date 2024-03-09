<?php
require_once('SantimPay/Santim_Functions.php');
require_once('functions.php');
require_once('Connection.php');


$body = file_get_contents('php://input');

$event = json_decode($body, true);

if (isset($event['Status']) && $event['Status'] === 'COMPLETED') {
    $applicantId = validateTransaction($event, $conn);

    if ($applicantId != null) {
        echo 'failed';
    } else {
        $row = json_decode($json, true);

        try {
            if (empty($row)) {
                throw new Exception("Empty or invalid JSON data.");
            }

            foreach ($row['applicants'] as $applicant) {
                // Perform operations on $applicant here
                $enrolResult = enrol($applicant, $conn, $conn2);

                if ($enrolResult === false) {
                    throw new Exception("Enrolment failed for applicant: " . $applicant['name']);
                }else{
                    $changeStatus = changeStatus($applicant, $conn);
                }

                
            }
        } catch (Exception $e) {
            // Handle the exception and display the error message
            echo "Error: " . $e->getMessage();
        }
    }
}
