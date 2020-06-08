<?php
require_once("db.php");
require_once("../model/Response.php");
require_once("../model/User.php");

$user = new User();
$disable_otp_for_transfers = $user->disableOtpForTransfers();
if($disable_otp_for_transfers==false){
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
    $response->_addMessages("OTP has been sent to business account mobile number to finalize disabling");
    $response->send();
    exit;
}
?>