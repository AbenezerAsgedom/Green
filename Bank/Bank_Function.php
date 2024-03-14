<?php 

/**
 * Perform a database transaction for the given user and courses.
 * @param int $userId The user ID
 * @param string $Courses The courses
 * @param mysqli $conn The database connection
 * @return bool Whether the transaction was successful
 * @throws Exception If an error occurs during the transaction
 */
function request($userId, $Courses, $conn){
    try{
        $query = "SELECT * FROM transaction_bank WHERE MoodleUserId=? AND Status IS NULL LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $status = null;
        if($row){
            $query = "UPDATE transaction_bank SET MoodleUserId=?, Courses=?, Timestamp=NOW() WHERE MoodleUserId=? AND Status=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('isis', $userId, $Courses, $userId, $status);
            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        }else{
            $query = "INSERT INTO transaction_bank (MoodleUserId, Courses, Timestamp, Status) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('is', $userId, $Courses);
            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        }
    }catch(Exception $e){
        echo 'Caught exception: ',  $e->getMessage(), "\n";
        return false;
    }
}


/**
 * Retrieves transaction data for a specific user within the last 48 hours.
 * @param int $userId The ID of the user to retrieve transaction data for
 * @param mysqli $conn The database connection object
 * @return array Associative array containing the transaction data
 */
function retrieve($userId, $conn){
    $timestamp = date("Y-m-d H:i:s", strtotime('-48 hours'));
    try {
        $query = "SELECT * FROM transaction_bank WHERE MoodleUserId=? AND Timestamp BETWEEN ? AND NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('is', $userId, $timestamp);
        if($stmt->execute()){
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row;
        }else{
            if ($stmt->error) {
                throw new Exception("Error executing SQL query: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}



/**
 * Removes old records from the transaction_bank table.
 * @param object $conn The database connection object
 * @throws Exception Error executing SQL query
 */
function remove($conn){
    $timestamp = date("Y-m-d H:i:s", strtotime('-48 hours'));
    $query = "DELETE FROM transaction_bank WHERE Status IS NULL AND Timestamp < ?";
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $timestamp);
        if($stmt->execute()){
            //do nothing
        }
        else{
            throw new Exception("Error executing SQL query: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}


