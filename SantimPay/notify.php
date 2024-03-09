<?php
require_once('../connection.php');
require_once('../functions.php');
require_once('Santim_Functions.php');

try {
    $body = file_get_contents('php://input');
    $event = json_decode($body, true);

    if (isset($event['Status']) && $event['Status'] === 'COMPLETED') {
        $courses = retrieveCourseId($event, $conn);
        $initiationId = $event['thirdPartyId'];

        // Access each applicantId value and perform your desired action
        foreach ($courses as $courseId) {
            $enrolStudents = enrolStudent($id, $courseId, $conn2);
            if (!$enrolStudents) {
                throw new Exception("Failed to enrol students for applicantId: $id and courseId: $courseId");
            }
        }
    }
} catch (Exception $e) {
    // Handle exceptions and display error message
    echo "Error: " . $e->getMessage();
}
