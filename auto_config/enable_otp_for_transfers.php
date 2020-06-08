<?php
require_once("db.php");
require_once("../model/Response.php");
require_once("../model/User.php");

$user = new User();
$enable_otp_for_transfers = $user->enableOtpForTransfers();
if($enable_otp_for_transfers==false){
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
    $response->_addMessages("OTP requirement for transfers has been enabled");
    $response->send();
    exit;
}
?>