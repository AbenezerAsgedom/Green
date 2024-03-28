<?php 

require_once('Bank_Function.php');
require_once('../connection.php');

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (isset($data) && $data !== null) {
    $userId = $data['userId'];
    $courses = $data['courses'];
    $amount = $data['amount'];
    $request = request($userId, $courses, $amount, $conn);
    if($request){
        echo json_encode(array('success' => true));
    }

}

        //  <div class="modal fade" id="staticBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        //     <div class="modal-dialog modal-dialog-centered">
        //         <div class="modal-content">
        //             <div class="modal-header">
        //                 <h5 class="modal-title text-center" id="staticBackdropLabel" style="color: green;">Enrolment request has been granted</h5>
        //             </div>
        //             <div class="modal-body text-center">
        //                 Please pay the amount using one of the banks we specified.<br>
        //                 Within 48 hours, send the Transaction number to the field below.<br><br>
        //                 <input type="text" class="form-control" id="txRef" placeholder="Transaction Number" required>
        //             </div>
        //             <div class="modal-footer">
        //                 <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        //             </div>
        //         </div>
        //     </div>
        // </div>
