<?php

namespace Chapa;

// require_once('session.php');
require_once('php.php');
require_once ("Chapa/vendor/autoload.php");
// require_once('functions.php');
// require_once('Connection.php');

// redirectUserToLogin($trueSession);

use Chapa\Chapa;
use Chapa\Model\PostData;


$body = file_get_contents('php://input');
parse_str($body, $data);
// $json = json_encode($array);


// $data = json_decode($json, true);

if ($data) {

    $chapa = new Chapa('CHASECK_TEST-xiT0ExPOP3N1B6uFJIcgffJl6JvulskG');
    $transactionRef = Util::generateToken();
    $postData = new PostData();

    if ($bank == 'block') {

        $conn1 = new \mysqli($host, $username, $password, 'etemar5_transaction');

        if ($conn1->connect_error) {
            die("Database connection failed: " . $conn1->connect_error);
        }

        $status = '';

        $sql = "SELECT * FROM transaction_bank WHERE MoodleUserId = ? AND Status = ?";
        $stmt = $conn1->prepare($sql);
        $stmt->bind_param("is", $data['userId'], $status);
        

        $userId = intval($data['userId']); // Sanitize userId as an integer
        $status = $conn1->real_escape_string($status); // Sanitize status as a string

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Delete the retrieved row
            $deleteSql = "DELETE FROM transaction_bank WHERE MoodleUserId = ? AND Status = ?";
            $deleteStmt = $conn1->prepare($deleteSql);
            $deleteStmt->bind_param("is", $userId, $status);
            $deleteStmt->execute();
        }
    }

    if ($data['is'] == 'false') {


        $amount = $data['totalCost'];
        $userId = $data['userId'];
        $grade = $data['grade'];
        $phone = $data['phoneNumber'];
        $reason = 'Payment for ' . $grade . " " . implode(", ", $data['subject']);
        $arr = $data;
        unset($data['phoneNumber'], $data['grade'], $data['userId'], $data['totalCost'], $data['terms']);

        $host = 'localhost'; // Replace with your database host
        $dbname = 'etemar5_mood435'; // Replace with your database name
        $username = 'etemar5_mood435'; // Replace with your database username
        $password = '(S9X8-W3pH'; // Replace with your database password

        // Create a new mysqli instance
        $conn = new \mysqli($host, $username, $password, $dbname);

        $conn1 = new \mysqli($host, $username, $password, 'etemar5_transaction');

        // Check if the connection was successful
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        if ($conn1->connect_error) {
            die("Database connection failed: " . $conn1->connect_error);
        }

        // Prepare the SELECT query
        $query = "SELECT * FROM mdlwj_user WHERE id = ?";

        // Prepare the query statement
        $statement = $conn->prepare($query);

        $statement->bind_param('i', $userId);

        // Execute the query
        $statement->execute();

        // Get the result set
        $result = $statement->get_result();

        // Fetch the row as an associative array
        $row = $result->fetch_assoc();

        // Process the fetched data
        if ($row) {
            $firstname = $row['firstname'];
            $lastname = $row['lastname'];
            $email = $row['email'];
            $subject = json_encode($data);

            // $subjectString = implode(', ', $subject);

            $postData->amount($amount)
                ->currency('ETB')
                ->email($email)
                ->firstname($firstname)
                ->lastname($lastname)
                ->transactionRef($transactionRef)
                ->returnUrl('https://project.etemari.net')
                ->customizations(
                    array(
                        'customization[title]' => 'eTemari.net',
                        'customization[description]' => $reason
                    )
                );
            // } else {
            //     // echo "No row found.";
            // }

            $fullname = $firstname . " " . $lastname;
            $status = ' ';

            $response1 = $chapa->initialize($postData);

            $response2 = $chapa->verify($transactionRef);
            if ($response2->getStatusCode() == 200) {
                // echo 'Payment not verified because ' . $response2->getMessage();
            } else {
                // Assuming you already have an active mysqli connection object named $conn
                $stmt = $conn1->prepare("INSERT INTO transaction_chapa (MoodleUserId, Fullname, Txn_Id, Phone, Grade, Subject, Reason, Amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issssssd', $userId, $fullname, $transactionRef, $phone, $grade, $subject, $reason, $amount);


                if ($stmt->execute()) {
                    $checkoutUrl = $response1->getData()->checkout_url;
                }
               else {
                    $checkoutUrl = $stmt->error;
                }
                header('Content-Type: application/json');
                echo json_encode(array('success' => true, 'option' => 'Chapa', 'paymentUrl' => $checkoutUrl));
                exit();
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'OOc' => $body, 'paymentUrl' => null));
            exit();
        }
    } else if ($data['is'] == 'true') {

        $amount = $data['totalCost'];
        $userId = $data['userId'];
        $grade = $data['grade'];
        $phone = $data['phoneNumber'];
        $reason = 'Payment for upgrade' . $grade . " " . implode(", ", $data['subject']);

        unset($data['phoneNumber'], $data['grade'], $data['userId'], $data['totalCost'], $data['terms']);

        $host = 'localhost'; // Replace with your database host
        $dbname = 'etemar5_mood435'; // Replace with your database name
        $username = 'etemar5_mood435'; // Replace with your database username
        $password = '(S9X8-W3pH'; // Replace with your database password

        // Create a new mysqli instance
        $conn = new \mysqli($host, $username, $password, $dbname);

        $conn1 = new \mysqli($host, $username, $password, 'etemar5_transaction');

        // Check if the connection was successful
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        if ($conn1->connect_error) {
            die("Database connection failed: " . $conn1->connect_error);
        }

        // Prepare the SELECT query
        $query = "SELECT * FROM mdlwj_user WHERE id = ?";

        // Prepare the query statement
        $statement = $conn->prepare($query);

        $statement->bind_param('i', $userId);

        // Execute the query
        $statement->execute();

        // Get the result set
        $result = $statement->get_result();

        // Fetch the row as an associative array
        $row = $result->fetch_assoc();

        // Process the fetched data
        if ($row) {
            $firstname = $row['firstname'];
            $lastname = $row['lastname'];
            $email = $row['email'];
            $subject = json_encode($data);

            // $subjectString = implode(', ', $subject);

            $postData->amount($amount)
                ->currency('ETB')
                ->email($email)
                ->firstname($firstname)
                ->lastname($lastname)
                ->transactionRef($transactionRef)
                ->returnUrl('https://project.etemari.net')
                ->customizations(
                    array(
                        'customization[title]' => 'eTemari.net',
                        'customization[description]' => $reason
                    )
                );
            // } else {
            //     // echo "No row found.";
            // }

            $fullname = $firstname . " " . $lastname;
            $status = ' ';

            $response1 = $chapa->initialize($postData);

            $response2 = $chapa->verify($transactionRef);
            if ($response2->getStatusCode() == 200) {
                // echo 'Payment not verified because ' . $response2->getMessage();
            } else {
                // Assuming you already have an active mysqli connection object named $conn
                $stmt = $conn1->prepare("INSERT INTO transaction_chapa (MoodleUserId, Fullname, Txn_Id, Phone, Grade, Subject, Reason, Amount) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issssssd', $userId, $fullname, $transactionRef, $phone, $grade, $subject, $reason, $amount);


                if ($stmt->execute()) {
                    $checkoutUrl = $response1->getData()->checkout_url;
                }
                header('Content-Type: application/json');
                echo json_encode(array('success' => true, 'option' => 'Chapa', 'paymentUrl' => $checkoutUrl));
                exit();
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'OOc' => $body, 'paymentUrl' => null));
            exit();
        }
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'OOd' => $body, 'paymentUrl' => null));
    exit();
}
