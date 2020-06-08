<?php
require_once("db.php");
require_once("../model/Response.php");
require_once("../model/User.php");

$rawPostData = file_get_contents('php://input');
$jsonData = json_decode($rawPostData);
$otp = $jsonData->otp;

$user = new User();
$finalize_disable_otp_for_transfers = $user->finalizedisableOtpForTransfers($otp);
if($finalize_disable_otp_for_transfers==false){
    $response = new Response();
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->_addMessages("Error: Bad Request, try again later");
    $response->send();
    exit;
}else{
    $response = new Response();
    $response->setHttpStatusCode(200);
    $response->setSuccess(true);
    $response->_addMessages("OTP requirement for transfers has been disabled");
    $response->send();
    exit;
}
?>