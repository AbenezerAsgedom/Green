<?php

require_once('conncection.php');


/**
 * Fetches user's moodle account information.
 *
 * @throws Exception if the account information cannot be retrieved
 * @return Account the fetched account
 */
function fetchAccount()
{
    try {
        require_once('../config.php');
        $session = session_id();

        $trueSession = false;

        $UID = 0;

        $sql = "SELECT * FROM mdlwj_sessions WHERE sid = ? AND userid != ?";

        // Prepare the statement
        $stmt = $conn->prepare($sql);

        // Bind the session ID to the parameter placeholder
        $stmt->bind_param("si", $session, $UID);

        // Execute the statement
        $stmt->execute();

        // Fetch the result
        $stmt->store_result();

        // Check if the value exists
        if ($stmt->num_rows > 0) {

            $trueSession = true;
        } else {
            // No data found in the database, redirect the user to a specific URL
            $trueSession = false;
        }

        // Close the statement and database connection
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        throw new Exception("Account information cannot be retrieved: " . $e->getMessage());
    }
}
