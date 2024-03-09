<?php
/**
 * Generates a payment URL based on the provided data and user information.
 *
 * @param array $data The data containing total cost, user ID, department, phone number, and subjects
 * @param object $conn The database connection for executing queries
 * @throws Exception If an error occurs during the payment URL generation process
 * @return string The generated payment URL
 */
function generatePaymentURL($data, $conn, $conn2)
{
    $chapa = new Chapa(getenv('CHAPA_API_KEY'));
    $transactionRef = Util::generateToken();
    $postData = new PostData();

    if (empty($data)) {
        throw new Exception("No payment data provided.");
    }

    $amount = filter_var($data['totalCost'] ?? null, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if ($amount === false || $amount <= 0) {
        throw new Exception("Total cost is missing or invalid in payment data.");
    }

    $userId = filter_var($data['userId'] ?? null, FILTER_SANITIZE_NUMBER_INT);
    if ($userId === false) {
        throw new Exception("User ID is missing or invalid in payment data.");
    }

    $phone = filter_var($data['phoneNumber'] ?? null, FILTER_SANITIZE_NUMBER_INT);
    if ($phone === false) {
        throw new Exception("Phone number is missing or invalid in payment data.");
    }

    $subject = json_encode(array_map('filter_var', array_diff_key($data, array_flip(['totalCost', 'userId', 'phoneNumber', 'terms'])), array_fill(0, count(array_diff_key($data, array_flip(['totalCost', 'userId', 'phoneNumber', 'terms']))), FILTER_SANITIZE_STRING)));

    // Assuming you already have an active mysqli connection object named $conn
    $query = "SELECT firstname, lastname, email FROM mdlwj_user WHERE id = ?";

    $stmt = $conn2->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $firstname = null;
    $lastname = null;
    $email = null;
    $stmt->bind_result($firstname, $lastname, $email);
    $stmt->fetch();
    $stmt->close();

    if ($firstname === null || $lastname === null || $email === null) {
        throw new Exception("No row found in the database for the provided user ID.");
    }

    $postData->amount($amount)
        ->currency('ETB')
        ->email($email)
        ->firstname($firstname)
        ->lastname($lastname)
        ->transactionRef($transactionRef)
        ->returnUrl('https://green.et')
        ->customizations(
            array(
                'customization[title]' => 'green.et'
            )
        );

    $response1 = $chapa->initialize($postData);
    $checkoutUrl = $response1->getData()->checkout_url;

    $stmt = $conn->prepare("INSERT INTO transaction_chapa (MoodleUserId, Txn_Id, Phone, Courses, Amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $userId, $transactionRef, $phone, $subject, $amount);
    $stmt->execute();
    $stmt->close();

    $result = array('success' => true, 'option' => 'Chapa', 'paymentUrl' => $checkoutUrl);

    header('Content-Type: application/json');
    return $result;
}

/**
 * Retrieve the course ID from the database based on the transaction reference.
 *
 * @param array $data The data containing the transaction reference.
 * @param object $conn The database connection object.
 * @return array|null The array of courses or null if no courses are found.
 */
function retrieveCourseId($data, $conn)
{
    $Init_Id = $data['tx_ref'];
    if ($Init_Id !== null) {
        $selectStmt = $conn->prepare("SELECT Courses FROM transaction_chapa WHERE Txn_Id = ?");
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


