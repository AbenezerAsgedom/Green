<?php

require_once('conncection.php');

/**
 * Retrieves the session information from the database and checks if the session is valid.
 *
 * @return boolean
 * @throws Exception Account information cannot be retrieved: description of exception
 */
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

function redirectUserToLogin($session)
{
    if (!$session) {
        // Output the Bootstrap modal when the user is not allowed on the page
        echo '
        <head>
             <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
            <div class="modal fade" id="notAllowedModal" tabindex="-1" aria-labelledby="notAllowedModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="notAllowedModalLabel">Access Denied</h5>
                        </div>
                        <div class="modal-body text-center text-danger lead">
                            <p>You are not allowed to access this page.</p>
                        </div>
                        <div class="modal-footer">
                            <a href="http://localhost/moodle/login/index.php" class="btn btn-primary">Go to Login</a>
                        </div>
                    </div>
                </div>
            </div>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                $(document).ready(function() {
                    $("#notAllowedModal").modal("show");
                });
            </script>
        ';

        exit();
    }
}
