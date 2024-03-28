<?php 
require_once('Bank_Function.php');

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (isset($data) && $data !== null) {
    $userId = $data['userId']; 
    $Courses = $data['courses'];
    $amount = $data ['amount'];
    $request = request($userId, $Courses, $amount, $conn);

    if($request == true){
        echo json_encode(array('success' => true));
    }

}