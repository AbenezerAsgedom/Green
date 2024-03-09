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

// The etemar5_transaction was successful
// Connect to the database securely


try {
    $dbHost     = 'localhost';
    $dbName     = 'etemar5_transaction';
    $dbUsername = 'etemar5_mood435';
    $dbPassword = '(S9X8-W3pH';

    // Create a PDO database connection
    $db = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUsername, $dbPassword);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json', true, 200);
    echo json_encode(array('Success' => false, 'error' => $e->getMessage()));
    exit();
}

// Update the etemar5_transaction in the database
$stmt = $db->prepare("UPDATE transaction_chapa SET Status = :status, Via = :via, Reference = :ref WHERE Txn_Id = :txn");
$stmt->bindValue(':status', $status, PDO::PARAM_STR);
$stmt->bindValue(':via', $via, PDO::PARAM_STR);
$stmt->bindValue(':txn', $txn_id, PDO::PARAM_STR);
$stmt->bindValue(':ref', $ref, PDO::PARAM_STR);


if (!$stmt->execute()) {
    throw new Exception('Error updating etemar5_transaction status');
}

// Get the etemar5_transaction details
$stmt = $db->prepare("SELECT * FROM transaction_chapa WHERE Txn_Id = :id");
$stmt->bindValue(':id', $txn_id, PDO::PARAM_STR);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {



    $paymentOption = $row['Duration'];
    $jsonData = json_decode($row['Subject'], true);
    $subjects = isset($jsonData['subject']) ? $jsonData['subject'] : null;
    $stream = $jsonData['stream'];
    $grade = $row['Grade'];
    $UserId = $row['MoodleUserId'];
    $enrolstartdate = time();
    $timestamp = 0;



    if (!empty($jsonData)) {
        // Create a new PDO database connection
        $dbHost = 'localhost';
        $dbName = 'etemar5_mood435';
        $dbUsername = 'etemar5_mood435';
        $dbPassword = '(S9X8-W3pH';

        $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }


        
        // This condition checks if The chosen product is ESSLCE or nor
        if ($subjects == null && $grade == 'ESSLCE') {

            $timestamp = strtotime('+3 months', $enrolstartdate);


            // $query = "SELECT mdlwj_user.id, mdlwj_user.firstname, mdlwj_user_enrolments.id, mdlwj_enrol.id, mdlwj_course.fullname, mdlwj_course.id, mdlwj_course_categories.name, mdlwj_course_categories.id 
            //             FROM mdlwj_user 
            //             JOIN mdlwj_user_enrolments ON mdlwj_user.id = mdlwj_user_enrolments.userid
            //             JOIN mdlwj_enrol ON mdlwj_user_enrolments.enrolid = mdlwj_enrol.id 
            //             JOIN mdlwj_course ON mdlwj_enrol.courseid = mdlwj_course.id
            //             JOIN mdlwj_course_categories ON mdlwj_course.category = mdlwj_course_categories.id
            //             WHERE mdlwj_user.id = ?";

            if ($stream == 'Natural') {
                $stream = 'natural_exam';
            } else if ($stream == 'Social') {
                $stream = 'social_exam';
            }


            $conn->autocommit(FALSE); // turn off autocommit
            $conn->begin_transaction();


            try {
                $stmt = 'SELECT mdlwj_course.id FROM mdlwj_course 
                            JOIN mdlwj_course_categories ON mdlwj_course.category = mdlwj_course_categories.id
                            WHERE mdlwj_course_categories.idnumber = ?';
                $stmt = $conn->prepare($stmt);
                $stmt->bind_param("s", $stream);
                $stmt->execute();
                $result = $stmt->get_result();



                if ($result->num_rows > 0) {
                    // Fetch all rows
                    $rows = $result->fetch_all(MYSQLI_ASSOC);
                } else {
                    echo $stream;
                    header('Content-Type: application/json', true, 200);
                    echo json_encode(array('Success' => false, 'error' => $stmt->error));
                    exit();
                }



                foreach ($rows as $row) {
                    // Get the course ID from the current row
                    $courseID = $row['id'];

                    $role = 5;
                    $method = 'manual';

                    // Insert values
                    $stmt = $conn->prepare("INSERT INTO mdlwj_enrol (courseid, enrol, roleid, enrolenddate, customtext2) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issis", $courseID, $method, $role, $timestamp, $UserId);

                    if ($stmt->execute()) {
                        $last_id = $conn->insert_id;
                        // Insertion successful
                        $stmt = $conn->prepare("INSERT INTO mdlwj_user_enrolments (enrolid, userid, timestart, timeend, modifierid, timecreated, timemodified) VALUES (?, ?, UNIX_TIMESTAMP(), ?, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
                        $stmt->bind_param("iis", $last_id, $UserId, $timestamp);

                        if ($stmt->execute()) {
                            // Assuming $conn is the MySQLi database connection object
                            $query = "SELECT * FROM mdlwj_context WHERE instanceid = ? AND contextlevel = 50";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $courseID);

                            if ($stmt->execute()) {
                                $result = $stmt->get_result();
                                $row = $result->fetch_assoc();
                                if ($row) {
                                    $contextID = $row['id'];

                                    if ($contextID) {
                                        $query = "INSERT INTO mdlwj_role_assignments (roleid, contextid, userid, timemodified) VALUES (?, ?, ?, ?)";
                                        $stmt = $conn->prepare($query);
                                        $roleID = 5;
                                        $timenow = time();
                                        $stmt->bind_param("iiis", $roleID, $contextID, $UserId, $timenow);
                                        if ($stmt->execute()) {
                                            echo 'DONE';
                                        }
                                    }
                                }
                            } else {
                                // Handle the query execution failure
                                // Display an error message or perform error handling
                                echo "Error executing query: " . $stmt->error;
                            }
                        }
                    } else {
                        header('Content-Type: application/json', true, 200);
                        echo json_encode(array('Success' => false, 'error' => $res));
                        exit();
                    }
                }


                // Commit the etemar5_transaction
                $conn->commit();
            } catch (Exception $e) {
                // Rollback the etemar5_transaction in case of an error
                $conn->rollBack();
                // Exception occurred
                echo "An error occurred: " . $e->getMessage();
            }
        } else {

            $timestamp = strtotime('+3 months', $enrolstartdate);
            
            foreach ($subjects as $subject) {
                
                if (is_numeric($subject)) {
                                        
                    $subject = intval($subject);
                    $query = "SELECT mdlwj_user.id, mdlwj_user.username, mdlwj_user_enrolments.id AS userEnrolmentID, mdlwj_user_enrolments.enrolid, mdlwj_user_enrolments.timeend, mdlwj_enrol.id AS enrolID, mdlwj_course.fullname, mdlwj_course.id AS courseID 
                                FROM mdlwj_user  
                                JOIN mdlwj_user_enrolments ON mdlwj_user_enrolments.userid = mdlwj_user.id 
                                JOIN mdlwj_enrol ON mdlwj_enrol.id = mdlwj_user_enrolments.enrolid 
                                JOIN mdlwj_course ON mdlwj_enrol.courseid = mdlwj_course.id WHERE mdlwj_user.id = ? AND mdlwj_course.id = ?";

                    $stmt = $conn->prepare($query);
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
                            $updateStmt = $conn->prepare($query);
                            $updateStmt->bind_param("iisi", $timestamp, $timestamp, $UserId, $courseID);
                            $updateStmt->execute();
                        } else {
                            $stmt = $conn->prepare("SELECT id FROM mdlwj_course WHERE id = ?");
                            $stmt->bind_param("i", $subject);
                            $stmt->execute();

                            $isResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                            if (count($isResult) > 0) {
                                $courseID = $isResult[0]['id'];

                                $role = 5;
                                $method = 'manual';
                                // No values found, then insert values
                                $stmt = $conn->prepare("INSERT INTO mdlwj_enrol (courseid, enrol, roleid, enrolenddate, customtext2) VALUES (?, ?, ?, ?, ?)");
                                $stmt->bind_param("issis", $courseID, $method, $role, $timestamp, $UserId);
                                $stmt->execute();

                                if ($stmt->affected_rows > 0) {
                                    $last_id = $stmt->insert_id;
                                    echo $last_id;
                                    // Insertion successful 
                                    $stmt = $conn->prepare("INSERT INTO mdlwj_user_enrolments (enrolid, userid, timestart, timeend, modifierid, timecreated, timemodified) VALUES (?, ?, UNIX_TIMESTAMP(), ?, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
                                    $stmt->bind_param("iis", $last_id, $UserId, $timestamp);

                                    if ($stmt->execute()) {
                                        // Assuming $conn is the mysqli database connection object
                                        $query = "SELECT * FROM mdlwj_context WHERE instanceid = ? AND contextlevel = 50";
                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param("i", $courseID);

                                        if ($stmt->execute()) {
                                            $result = $stmt->get_result()->fetch_assoc();
                                            if ($result) {
                                                $contextID = $result['id'];

                                                if ($contextID) {
                                                    $query = "INSERT INTO mdlwj_role_assignments (roleid, contextid, userid, timemodified) VALUES (?, ?, ?, ?)";
                                                    $stmt = $conn->prepare($query);
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
                        $conn->commit();
                    } catch (Exception $e) {
                        // Rollback the transaction in case of an error
                        $conn->rollback();
                        // Exception occurred
                        echo "An error occurred: " . $e->getMessage();
                    }
                } else {
                    
                    $shortName = getSubjectShortName($subject, $grade);

                    $query = "SELECT mdlwj_user.id, mdlwj_user.username, mdlwj_user_enrolments.id AS userEnrolmentID, mdlwj_user_enrolments.enrolid, mdlwj_user_enrolments.timeend, mdlwj_enrol.id AS enrolID, mdlwj_course.fullname, mdlwj_course.id AS courseID 
                              FROM mdlwj_user 
                              JOIN mdlwj_user_enrolments ON mdlwj_user_enrolments.userid = mdlwj_user.id 
                              JOIN mdlwj_enrol ON mdlwj_enrol.id = mdlwj_user_enrolments.enrolid 
                              JOIN mdlwj_course ON mdlwj_enrol.courseid = mdlwj_course.id 
                              WHERE mdlwj_user.id = ? AND mdlwj_course.shortname = ?";

                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $UserId, $shortName);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();

                    try {


                        if (count($row) > 0) {

                            try {

                                $courseID = $row['courseID'];
                                $conn->autocommit(false);
                                // Start the transaction
                                $conn->query('START TRANSACTION');

                                // Perform your update operation here
                                $query = "UPDATE mdlwj_user_enrolments
                                          JOIN mdlwj_enrol ON mdlwj_user_enrolments.enrolid = mdlwj_enrol.id
                                          SET mdlwj_user_enrolments.timeend = ?, mdlwj_enrol.enrolenddate = ?
                                          WHERE mdlwj_user_enrolments.userid = ? AND mdlwj_enrol.courseid= ? ";
                                $updateStmt = $conn->prepare($query);
                                $updateStmt->execute([$timestamp, $timestamp, $UserId, $courseID]);

                                // Commit the transaction
                                $conn->commit();
                                $conn->autocommit(true);
                            } catch (Exception $e) {
                                // Rollback the transaction in case of an error
                                $conn->rollBack();
                                $conn->autocommit(true);
                                // Handle the exception
                                echo "Error: " . $e->getMessage();
                            }
                        } else {
                            // Start the transaction

                            $stmt = $conn->prepare("SELECT id FROM mdlwj_course WHERE shortname = ?");
                            $stmt->bind_param("s", $shortName);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = mysqli_fetch_assoc($result);

                            if ($conn->errno) {
                                echo "Error: " . $conn->error;
                            }
                            $conn->autocommit(FALSE);
                            $conn->begin_transaction();


                            if ($result->num_rows > 0) {

                                $courseID = $row['id'];

                                $role = 5;
                                $method = 'manual';
                                // No values found, then insert values
                                $stmt = $conn->prepare("INSERT INTO mdlwj_enrol (courseid, enrol, roleid, enrolenddate, customtext2) VALUES (?, ?, ?, ?, ?)");
                                $stmt->bind_param("isiss", $courseID, $method, $role, $timestamp, $UserId);
                                $stmt->execute();

                                if ($stmt->affected_rows > 0) {
                                    $last_id = $conn->insert_id;
                                    echo $last_id;
                                    // Insertion successful 
                                    $stmt = $conn->prepare("INSERT INTO mdlwj_user_enrolments (enrolid, userid, timestart, timeend, modifierid, timecreated, timemodified) VALUES (?, ?, UNIX_TIMESTAMP(), ?, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
                                    $stmt->bind_param("iis", $last_id, $UserId, $timestamp);
                                    if ($stmt->execute()) {
                                        $query = "SELECT * FROM mdlwj_context WHERE instanceid = ? AND contextlevel = 50";
                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param("i", $courseID);
                                        if ($stmt->execute()) {
                                            $result = $stmt->get_result();
                                            $row = $result->fetch_assoc();
                                            if ($row) {
                                                $contextID = $row['id'];
                                                if ($contextID) {
                                                    $query = "INSERT INTO mdlwj_role_assignments (roleid, contextid, userid, timemodified) VALUES (?, ?, ?, ?)";
                                                    $stmt = $conn->prepare($query);
                                                    $roleID = 5;
                                                    $timenow = time();
                                                    $stmt->bind_param("iiii", $roleID, $contextID, $UserId, $timenow);
                                                    if ($stmt->execute()) {
                                                        echo 'DONE';
                                                        $conn->commit();
                                                    }
                                                }
                                            }
                                        } else {
                                            // Handle the query execution failure
                                            // Display an error message or perform error handling
                                            echo "Error executing query: " . $conn->error;
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Rollback the etemar5_transaction in case of an error
                        $conn->rollBack();
                    }
                }
            }
        }
    }
}
http_response_code(200);
