<?php

/**
 * Perform a database transaction for the given user and courses.
 * @param int $userId The user ID
 * @param string $Courses The courses
 * @param mysqli $conn The database connection
 * @return bool Whether the transaction was successful
 * @throws Exception If an error occurs during the transaction
 */
function request($userId, $Courses, $amount, $conn)
{
    try {
        $query = "SELECT * FROM transaction_bank WHERE MoodleUserId=? AND Status IS NULL LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $status = null;
        if ($row) {
            $query = "UPDATE transaction_bank SET MoodleUserId=?, Courses=?, Amount=? WHERE MoodleUserId=? AND Status=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('isiis', $userId, $Courses, $amouont, $userId, $status);
            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } else {
            $query = "INSERT INTO transaction_bank (MoodleUserId, Courses, Amount) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('is', $userId, $Courses, $amount);
            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        return false;
    }
}


/**
 * Retrieves transaction data for a specific user within the last 48 hours.
 * @param int $userId The ID of the user to retrieve transaction data for
 * @param mysqli $conn The database connection object
 * @return array Associative array containing the transaction data
 */
function retrieve($userId, $conn)
{
    $timestamp = date("Y-m-d H:i:s", strtotime('-48 hours'));
    try {
        $query = "SELECT * FROM transaction_bank WHERE MoodleUserId=? AND Timestamp BETWEEN ? AND NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('is', $userId, $timestamp);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return $row;
            } else {
                
            }
        } else {
            if ($stmt->error) {
                throw new Exception("Error executing SQL query: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
}


/**
 * Removes pending transaction requests for a specific user
 *
 * @param int $userId The user ID
 * @param mysqli $conn The database connection object
 * @return bool Always true
 */
function removeRequest($userId, $conn){
    // Select all pending transactions for the given user
    $query = "SELECT * FROM transaction_bank WHERE MoodleUserId = ? AND STATUS IS NULL";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        return false;
    }
    
    // Bind the user ID to the parameter
    $stmt->bind_param('i', $userId);
    
    // Execute the statement
    if (!$stmt->execute()) {
        return false;
    }
    
    // Get the result
    $result = $stmt->get_result();
    
    // If there are no pending transactions, return true
    if ($result->num_rows === 0) {
        return true;
    }
    
    // Otherwise, delete all pending transactions
    $query = "DELETE FROM transaction_bank WHERE MoodleUserId = ? AND STATUS IS NULL";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        return false;
    }
    
    // Bind the user ID to the parameter
    $stmt->bind_param('i', $userId);
    
    // Execute the statement
    if (!$stmt->execute()) {
        return false;
    }
    
    // Return true regardless
    return true;
}


/**
 * Removes old records from the transaction_bank table.
 * @param object $conn The database connection object
 * @throws Exception Error executing SQL query
 */
function remove($conn)
{
    $timestamp = date("Y-m-d H:i:s", strtotime('-48 hours'));
    $query = "SELECT COUNT(*) as cnt FROM transaction_bank WHERE Status IS NULL AND Timestamp < ?";
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $timestamp);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['cnt'] > 0) {
                $delete_query = "DELETE FROM transaction_bank WHERE Status IS NULL AND Timestamp < ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param('s', $timestamp);
                if ($delete_stmt->execute()) {
                    // do nothing
                } else {
                    throw new Exception("Error executing SQL query: " . $delete_stmt->error);
                }
            }
        } else {
            if ($stmt->error) {
                throw new Exception("Error executing SQL query: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
}



/**
 * Verify a bank transaction for the given user
 * 
 * @param int $userId The user ID
 * @param string $ttNumber The transaction tracking number
 * @param string $Bank The bank name
 * @param mysqli $conn The database connection
 * @return bool Whether the transaction was successfully verified
 * @throws Exception If an error occurs during the verification
 */
function verify($userId, $ttNumber, $Bank, $conn)
{
    try {
        // Select the transaction from the database
        $query = "SELECT * FROM transaction_bank WHERE MoodleUserId = ? AND Status IS NULL LIMIT 1";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception('Error preparing statement: ' . $conn->error);
        }
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            throw new Exception('Error executing statement: ' . $stmt->error);
        }
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception('Error getting result: ' . $stmt->error);
        }
        $row = $result->fetch_assoc();
        if ($row === null) {
            return false;
        }
        
        // Update the transaction
        $query = "UPDATE transaction_bank SET TTNumber = ?, Bank = ? WHERE MoodleUserId = ? AND Status IS NULL";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception('Error preparing statement: ' . $conn->error);
        }
        $stmt->bind_param('ssi', $ttNumber, $Bank, $userId);
        if (!$stmt->execute()) {
            throw new Exception('Error executing statement: ' . $stmt->error);
        }
        
        return true;
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        return false;
    }
}
