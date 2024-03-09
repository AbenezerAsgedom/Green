<?php

require_once('../../../../../functions.php');

// Set appropriate error reporting level
error_reporting(E_ALL);
ini_set('display_errors', 0);


// Retrieve the request's body
$body = file_get_contents('php://input');
// Parse the JSON
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

// Sanitize the input data
$status = filter_var($event['status'], FILTER_SANITIZE_SPECIAL_CHARS);
$txn_id = filter_var($event['tx_ref'], FILTER_SANITIZE_SPECIAL_CHARS);
$via = filter_var($event['payment_method'], FILTER_SANITIZE_SPECIAL_CHARS);
$ref = filter_var($event['reference'], FILTER_SANITIZE_SPECIAL_CHARS);

$stmt = $conn->prepare("UPDATE transaction_chapa SET Status = :status, Via = :via, Reference = :ref WHERE Txn_Id = :txn");
$stmt->bindValue(':status', $status, PDO::PARAM_STR);
$stmt->bindValue(':via', $via, PDO::PARAM_STR);
$stmt->bindValue(':txn', $txn_id, PDO::PARAM_STR);
$stmt->bindValue(':ref', $ref, PDO::PARAM_STR);


if (!$stmt->execute()) {
    throw new Exception('Error updating etemar5_transaction status');
}

// Get the etemar5_transaction details
$stmt = $conn->prepare("SELECT * FROM transaction_chapa WHERE Txn_Id = :id");
$stmt->bindValue(':id', $txn_id, PDO::PARAM_STR);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {

    $jsonData = json_decode($row['Courses'], true);
    $subjects = isset($jsonData['Courses']) ? $jsonData['Courses'] : null;
    $grade = $row['Department'];
    $UserId = $row['MoodleUserId'];
    $enrolstartdate = time();
    $timestamp = 0;



    if (!empty($jsonData)) {
        // Create a new PDO database connection
        $dbHost = 'localhost';
        $dbName = 'etemar5_mood435';
        $dbUsername = 'etemar5_mood435';
        $dbPassword = '(S9X8-W3pH';

        $conn2 = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

        // Check connection
        if ($conn2->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        $timestamp = strtotime('+3 months', $enrolstartdate);

        foreach ($subjects as $subject) {


            $subject = intval($subject);
            $query = "SELECT mdlwj_user.id, mdlwj_user.username, mdlwj_user_enrolments.id AS userEnrolmentID, mdlwj_user_enrolments.enrolid, mdlwj_user_enrolments.timeend, mdlwj_enrol.id AS enrolID, mdlwj_course.fullname, mdlwj_course.id AS courseID 
                                FROM mdlwj_user  
                                JOIN mdlwj_user_enrolments ON mdlwj_user_enrolments.userid = mdlwj_user.id 
                                JOIN mdlwj_enrol ON mdlwj_enrol.id = mdlwj_user_enrolments.enrolid 
                                JOIN mdlwj_course ON mdlwj_enrol.courseid = mdlwj_course.id WHERE mdlwj_user.id = ? AND mdlwj_course.id = ?";

            $stmt = $conn2->prepare($query);
            $stmt->bind_param("ii", $UserId, $subject);
            $stmt->execute();

            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $conn->autocommit(FALSE);
            // Start the transaction
            $conn->begin_transaction();

            try {
                if (count($result) > 0) {
                    $courseID = $result[0]['courseID'];

                    // Perform your update operation here
                    $query = "UPDATE mdlwj_user_enrolments
                                        JOIN mdlwj_enrol ON mdlwj_user_enrolments.enrolid = mdlwj_enrol.id
                                        SET mdlwj_user_enrolments.timeend = ?, mdlwj_enrol.enrolenddate = ?
                                        WHERE mdlwj_user_enrolments.userid = ? AND mdlwj_enrol.courseid= ? ";
                    $updateStmt = $conn2->prepare($query);
                    $updateStmt->bind_param("iisi", $timestamp, $timestamp, $UserId, $courseID);
                    $updateStmt->execute();
                } else {
                    $stmt = $conn2->prepare("SELECT id FROM mdlwj_course WHERE id = ?");
                    $stmt->bind_param("i", $subject);
                    $stmt->execute();

                    $isResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    if (count($isResult) > 0) {
                        $courseID = $isResult[0]['id'];

                        $role = 5;
                        $method = 'manual';
                        // No values found, then insert values
                        $stmt = $conn2->prepare("INSERT INTO mdlwj_enrol (courseid, enrol, roleid, enrolenddate, customtext2) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issis", $courseID, $method, $role, $timestamp, $UserId);
                        $stmt->execute();

                        if ($stmt->affected_rows > 0) {
                            $last_id = $stmt->insert_id;
                            echo $last_id;
                            // Insertion successful 
                            $stmt = $conn2->prepare("INSERT INTO mdlwj_user_enrolments (enrolid, userid, timestart, timeend, modifierid, timecreated, timemodified) VALUES (?, ?, UNIX_TIMESTAMP(), ?, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
                            $stmt->bind_param("iis", $last_id, $UserId, $timestamp);

                            if ($stmt->execute()) {
                                // Assuming $conn is the mysqli database connection object
                                $query = "SELECT * FROM mdlwj_context WHERE instanceid = ? AND contextlevel = 50";
                                $stmt = $conn2->prepare($query);
                                $stmt->bind_param("i", $courseID);

                                if ($stmt->execute()) {
                                    $result = $stmt->get_result()->fetch_assoc();
                                    if ($result) {
                                        $contextID = $result['id'];

                                        if ($contextID) {
                                            $query = "INSERT INTO mdlwj_role_assignments (roleid, contextid, userid, timemodified) VALUES (?, ?, ?, ?)";
                                            $stmt = $conn2->prepare($query);
                                            $roleID = 5;
                                            $timenow = time();
                                            $stmt->bind_param("iiii", $roleID, $contextID, $UserId, $timenow);
                                            if ($stmt->execute()) {
                                                echo 'DONE';
                                            }
                                        }
                                    }
                                } else {
                                    // Handle the query execution failure
                                    // Display an error message or perform error handling
                                    $error = $stmt->error;
                                    echo "Error executing query: " . $error;
                                }
                            }
                        }
                    }
                }

                // Commit the transaction
                $conn2->commit();
            } catch (Exception $e) {
                // Rollback the transaction in case of an error
                $conn2->rollback();
                // Exception occurred
                echo "An error occurred: " . $e->getMessage();
            }
        }
    }

}

http_response_code(200);
