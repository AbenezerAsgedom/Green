<?php 

require_once('conncection.php');


/**
 * Fetches user's moodle account information.
 *
 * @throws Exception if the account information cannot be retrieved
 * @return Account the fetched account
 */
function fetchAccount(){
    try {

    } catch (Exception $e) {
        throw new Exception("Account information cannot be retrieved: " . $e->getMessage());
    }
}