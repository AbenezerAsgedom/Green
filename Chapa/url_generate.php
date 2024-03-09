<?php
namespace Chapa;

require_once ("Chapa/vendor/autoload.php");
require_once("connection.php");

use Chapa\Chapa;
use Chapa\Model\PostData;

$body = file_get_contents('php://input');
parse_str($body, $data);

if ($data) {
    $URL = generatePaymentURL($data, $conn, $conn2);

    $paymentURL = json_encode($URL);

    header('Content-Type: application/json');
    echo $paymentURL;
}