<?php

require_once('index.php');

/**
 * Generate a payment URL using the provided data and connection.
 *
 * @param mixed $data The data used to generate the payment URL.
 * @param mixed $conn The database connection.
 * @return string|null The generated payment URL or null if the data is invalid.
 * @throws Exception If an error occurs during the generation of the payment URL.
 */
function generatePaymentURL($data, $conn)
{
    $PRIVATE_KEY_IN_PEM = "-----BEGIN EC PRIVATE KEY-----\nMHcCAQEEIOMaKKZ/7V3zfvNSWkPU8dPWdMoQAtF+pXMoX77N3hjxoAoGCCqGSM49\nAwEHoUQDQgAE5QIQ+7iWonO2SXHg3amW83snCudYp3+her8JaeMU9mIxDhgvQk9w\nWtUvFQTe16IAb/c0UoJtBdJx5HJ+Z/CPLg==\n-----END EC PRIVATE KEY-----\n";
    $GATEWAY_MERCHANT_ID = '9e2dab64-e2bb-4837-9b85-d855dd878d2b';
    $successRedirectUrl = 'https://project.etemari.net/my/courses.php';
    $failureRedirectUrl = 'https://project.etemari.net/Sponsor/Selection/firstpage.php';
    // $notifyUrl = 'https://project.etemari.net/Sponsor/SantimPay/notify.php';
    $notifyUrl = 'https://webhook.site/6c3668d9-6c6c-4205-831c-aee0d1162fea';
    $cancelRedirectUrl = 'https://project.etemari.net/Sponsor/Selection/firstpage.php';

    $client = new SantimpaySDK($GATEWAY_MERCHANT_ID, $PRIVATE_KEY_IN_PEM);

    if ($data === null) {
        return null;
    } else {
        if (is_array($data)) {
            $phone = $data['phone'];
            if (preg_match('/^(09|07)/', $phone)) {
                $phone = '+251' . substr($phone, 1);
            }
            $courses = $data['courses'];
            $applicantId = $data['applicantId'];
            $applicantId = json_encode($applicantId);
            $newId = $data['newId'];
            $coursesAsString = json_encode($courses);

            // Rest of the code that uses $data as an array
        } else {
            // Handle the case when $data is not an array
            return null;
        }
        $Reason = 'Payment for Enrolment';

        try {
            $count = 0;
            do {
                $id = random_int(1, 1000000000); // Generate a random integer
                $idS = (string) $id;

                // Prepare a SELECT statement to check if the ID exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM transaction_sp WHERE Initiation_Id = ?");
                $stmt->bind_param('s', $idS);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();
            } while ($count > 0); // Repeat if the ID already exists

            $idAsString = (string) $id;

            // Prepare an INSERT statement to add the new ID to the database
            $stmt = $conn->prepare("INSERT INTO transaction_sp (Initiation_Id, RequestId, applicantId, Phone, Amount, Reason, Courses) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisssss', $id, $newId, $applicantId, $phone, $data['totalCost'], $Reason, $coursesAsString);

            if ($stmt->execute()) {
                // Generate the payment URL
                $paymentUrl = $client->generatePaymentURL($idAsString, $data['totalCost'], $Reason, $successRedirectUrl, $failureRedirectUrl, $notifyUrl, $phone, $cancelRedirectUrl);

                // Return the payment URL
                return $paymentUrl;
            }
        } catch (\Exception $e) {
            // Handle exception
            return null;
        }
    }

    return null;
}

/**
 * Retrieve the ID from the event data and update the transaction in the database.
 *
 * @param mixed $event The event data containing transaction information
 * @param object $conn The database connection
 * @throws Exception If the SQL query execution fails
 */
function retrieveId($event, $conn)
{
    $Txn_Id = $event['txnId'];
    $Via = $event['paymentVia'];
    $Status = $event['Status'];
    $Init_Id = $event['thirdPartyId'];

    $applicantIds = array(); // Array to store applicantIds

    $stmt = $conn->prepare("UPDATE transaction_sp
    SET Via = ?, Status = ?, Txn_Id = ?
    WHERE Initiation_Id = ?");

    $stmt->bind_param("ssss", $Via, $Status, $Txn_Id, $Init_Id);

    if ($stmt->execute()) {
        $selectStmt = $conn->prepare("SELECT applicantId FROM transaction_sp WHERE Initiation_Id = ?");
        $selectStmt->bind_param("s", $Init_Id);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();

        if ($selectResult->num_rows > 0) {
            while ($row = $selectResult->fetch_assoc()) {
                // $applicantIdData = $row['applicantId'];

                // Check if the "applicant" key exists
                if (isset($row)) {
                    // $applicants = $applicantIdData['applicant'];
                    // Iterate over the values
                    $row = json_decode($row['applicantId'], true);
                    foreach ($row as $index => $applicant) {
                        // Add the applicantId to the array
                        $applicantIds[] = $applicant;
                    }
                } else {
                    // The "applicant" key does not exist
                    echo "No 'applicant' key found in the JSON data.";
                }
            }
        } else {
            // No rows found with the given Initiation_Id
            echo "No applicant found with the given Initiation ID.";
        }

        $selectStmt->close();
    }

    $stmt->close();

    $jsonApplicantIds = json_encode($applicantIds);

    // Return the JSON-encoded string
    return $jsonApplicantIds;
}


/**
 * Retrieve the course ID based on the event and database connection.
 *
 * @param mixed $event The event data containing the thirdPartyId
 * @param object $conn The database connection
 * @return mixed|null The retrieved course ID or null if not found
 */
function retrieveCourseId($event, $conn)
{
    $Init_Id = $event['thirdPartyId'];
    if ($Init_Id !== null) {
        $selectStmt = $conn->prepare("SELECT Courses FROM transaction_sp WHERE Initiation_Id = ?");
        $selectStmt->bind_param("s", $Init_Id);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();

        if ($selectResult->num_rows > 0) {
            $row = $selectResult->fetch_assoc();
            $courses = json_decode($row['Courses'], true);
            $selectStmt->close();

            return $courses;
        } else {
            // No rows found with the given Initiation_Id
            echo "No applicant found with the given Initiation ID.";
        }

        $selectStmt->close();
    }
}
