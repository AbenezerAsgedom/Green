<?php

require_once('conncection.php');

function session()
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

        $trueSession = ($stmt->num_rows > 0) ? true : false;

        // Close the statement and database connection
        $stmt->close();
        $conn->close();

        return $trueSession;
    } catch (Exception $e) {
        throw new Exception("Account information cannot be retrieved: " . $e->getMessage());
    }
}
