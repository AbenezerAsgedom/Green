<?php

require_once('connection.php');

/**
 * Retrieves the session information from the database and checks if the session is valid.
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

/**
 * Redirect user to login if session is not active.
 * @param bool $session description
 */
function redirectUserToLogin($trueSession)
{
    if (!$trueSession) {
        // Output the Bootstrap modal when the user is not allowed on the page
        echo '
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                            <a href="http://courses.green.et" class="btn btn-primary">Go to Login</a>
                        </div>
                    </div>
                </div>
            </div>
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

/**
 * Fetches the user's account information from the database based on the session ID.
 * @param object $conn The database connection object
 * @throws Exception if the account information cannot be retrieved
 * @return array An array containing the user's full name and user ID
 */
function fetchAccount($conn)
{
    require_once('../config.php');
    $session = session_id();
    try {
        // Retrieve the user's ID from the database based on the session ID
        $query = 'SELECT userid FROM mdlwj_sessions WHERE sid = ? ';

        $stms = $conn->prepare($query);
        if ($stms === false) {
            throw new Exception($conn->error);
        }

        $stms->bind_param('s', $session);
        if (!$stms->execute()) {
            throw new Exception($stms->error);
        }

        $result = $stms->get_result();
        if ($result === false) {
            throw new Exception($conn->error);
        }

        $row = $result->fetch_assoc();
        if ($row === null) {
            throw new Exception("The user's session cannot be found in the database.");
        }

        $UID = $row['userid'];

        // Retrieve the user's information from the database based on the user's ID
        $query = 'SELECT * FROM mdlwj_users WHERE id = ?';

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param('i', $UID);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception($conn->error);
        }

        $row = $result->fetch_assoc();
        if ($row === null) {
            throw new Exception("The user's account information cannot be found in the database.");
        }

        $fullname = $row['firstname'] . ' ' . $row['lastname'];

        return array(
            'fullname' => $fullname,
            'UID' => $UID
        );
    } catch (Exception $e) {
        echo "Account information cannot be retrieved: " . $e->getMessage();
    }
}

/**
 * Enrols a student in the specified courses.
 * @param mixed $UID The unique identifier of the student
 * @param string $courses A JSON string representing the list of course IDs
 * @param object $conn2 The database connection object
 * @throws Exception Exception message
 * @return bool
 */
function enrolStudent($UID, $courses, $conn2)
{
    try {
        $conn2->begin_transaction();
        $courseIds = json_decode($courses, true);

        foreach ($courseIds as $courseId) {
            $enrolstartdate = time();
            $timestamp = strtotime('+3 months', $enrolstartdate);

            $role = 5;  // ROLE OF STUDENT IN MOODLE DB
            $enrol = 'manual';

            // Insert enrolment record
            $query = "INSERT INTO mdlwj_enrol (courseid, enrol, roleid, enrolenddate) VALUES (?, ?, ?, ?)";
            $stmt = $conn2->prepare($query);
            if ($stmt === false) {
                // handle prepare error
                throw new Exception($conn2->error);
            }
            $stmt->bind_param("isii", $courseId, $enrol, $role, $timestamp);
            if (!$stmt->execute()) {
                echo "Error: enrol insert failed: " . $stmt->error;
                return false;
            }

            $last_id = $conn2->insert_id;

            // Insert user's enrolment record
            $query = "INSERT INTO mdlwj_user_enrolments (enrolid, userid, timestart, timeend, modifierid, timecreated, timemodified) VALUES (?, ?, UNIX_TIMESTAMP(), ?, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";

            $stmt = $conn2->prepare($query);
            if ($stmt === false) {
                // handle prepare error
                throw new Exception($conn2->error);
            }

            $stmt->bind_param("iis", $last_id, $UID, $timestamp);
            if (!$stmt->execute()) {
                echo "Error: user_enrolments insert failed: " . $stmt->error;
                return false;
            }

            // Select context ID
            $query = "SELECT * FROM mdlwj_context WHERE instanceid = ? AND contextlevel = 50";

            $stmt = $conn2->prepare($query);
            if ($stmt === false) {
                // handle prepare error
                throw new Exception($conn2->error);
            }

            $stmt->bind_param('i', $courseId);
            if (!$stmt->execute()) {
                echo "Error: context select failed: " . $stmt->error;
                return false;
            }

            $result1 = $stmt->get_result();
            $row = $result1->fetch_assoc();
            if (!$row) {
                // Handle the case when the context is not found
                throw new Exception("Context not found");
            }

            $contextID = $row['id'];

            if ($contextID) {
                // Assign the role to the user
                $query = "INSERT INTO mdlwj_role_assignments (roleid, contextid, userid, timemodified) VALUES (?, ?, ?, ?)";

                $stmt = $conn2->prepare($query);
                if ($stmt === false) {
                    // handle prepare error
                    throw new Exception($conn2->error);
                }
                $roleID = 5;
                $timenow = time();
                $stmt->bind_param("iiis", $roleID, $contextID, $UID, $timenow);
                if (!$stmt->execute()) {
                    throw new Exception("Error executing query: " . $stmt->error);
                }
            } else {
                // Handle the case when the context ID is not found
                throw new Exception("Context ID not found");
            }
        }
        $conn2->commit();
        return true;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Fetches courses for a user based on their department.
 * @param int $UID The user ID
 * @param object $conn2 The database connection
 * @throws Exception If an error occurs during database operations
 * @return void
 */
function fetchCourses($UID, $conn2)
{
    try {
        $data = null;
        $catID = null;
        // Get the department of the user
        $field = 'Deparment';
        $query = "SELECT mdlwj_user_info_data.data FROM mdlwj_user_info_data
            JOIN mdlwj_user_field ON mdlwj_user_field.id = mdlwj_user_info_data.fieldid
            WHERE userid = ? AND mdlwj_user_field = ?";
        $stmt = $conn2->prepare($query);
        if ($stmt === false) {
            throw new Exception($conn2->error);
        }
        $stmt->bind_param("is", $UID, $field);
        $stmt->execute();
        $stmt->bind_result($data);
        $stmt->fetch();
        $stmt->close();

        // Get the ID of the category with the same ID number as the department
        $query = "SELECT id FROM mdlwj_course_categories WHERE idnumber = ?";
        $stmt = $conn2->prepare($query);
        if ($stmt === false) {
            throw new Exception($conn2->error);
        }
        $stmt->bind_param("s", $data);
        $stmt->execute();
        $stmt->bind_result($catID);
        $stmt->fetch();
        $stmt->close();

        $query = "SELECT * FROM mdlwj_course WHERE categoryid = ? AND id NOT IN (
            SELECT enrol.courseid
            FROM mdlwj_enrol
            JOIN mdlwj_user_enrolments ON mdlwj_user_enrolments.enrolid = mdlwj_enrol.id
            JOIN mdlwj_course ON mdlwj_course.id = mdlwj_enrol.courseid
            WHERE categoryid = ? AND mdlwj_user_enrolments.userid = ?
        )";
        $stmt = $conn2->prepare($query);
        if ($stmt === false) {
            throw new Exception($conn2->error);
        }
        $stmt->bind_param("iii", $catID, $catID, $UID);
        $stmt->execute();
        $result = $stmt->get_result();
        echo '
            <div class="container bg-primary mt-4">
                <div class="row container">';
                    while ($row = $result->fetch_assoc()) {
                        echo '
                            <div class="col-md-3 col-xl-3">
                                <div class="card selectable mb-4" style="border-radius: 15px;" data-price="5">
                                    <div class="bg-image hover-overlay ripple ripple-surface ripple-surface-light" data-mdb-ripple-color="light">
                                        <img src="https://static.vecteezy.com/system/resources/previews/020/413/410/original/isometric-expert-team-for-data-analysis-business-statistic-management-consulting-marketing-landing-page-template-concept-suitable-for-diagrams-infographics-and-other-asset-vector.jpg" style="border-top-left-radius: 15px; border-top-right-radius: 15px;" class="img-fluid" alt="Laptop" />
                                        <a href="#!">
                                            <div class="mask"></div>
                                        </a>
                                    </div>
                                    <div class="card-body pb-0">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <p><a href="#!" class="text-dark">' . $row['thematic_area'] . '</a></p>
                                                <p class="small text-muted">Courses</p>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="my-0" />
                                    <hr class="my-0" />
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center pb-2 mb-1">
                                            <button type="button" class="btn btn-sm btn-outline-success">Select</button>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                        }
                    echo '
                </div>
            </div>';
        $stmt->close();
    } catch (Exception $e) {
        // Handle exception
    }
}

