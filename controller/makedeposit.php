<?php
require_once("db.php");
require_once("../model/Response.php");
require_once("../model/User.php");


try {
    $writeDB = DB::connectWriteDB();
} catch (PDOException $ex) {
    error_log("Connection Error -".$ex, 0);
    $response = new Response;
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->_addMessages("Database Connection Error");
    $response->send();
    exit;
}

if(array_key_exists("sessionid", $_GET)){
    $sessionid = $_GET["sessionid"];

    if($sessionid === '' || !is_numeric($sessionid)){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Invalid Session Id.... Please retry Login in");
        $response->send();
        exit;
    }

    if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $response->_addMessages("Access Denied due to invalid access token") : false);
        (strlen($_SERVER['HTTP_AUTHORIZATION']) < 1 ? $response->_addMessages("Invalid Access token") : false);
        $response->send();
        exit;
    }

    $accesstoken = $_SERVER['HTTP_AUTHORZATION'];

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Request method not allowed");
        $response->send();
        exit;
    }

    sleep(2);

    if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Request Header not set to application/JSON header");
        $response->send();
        exit;
    }

    $rawPostData = file_get_contents('php://input');

    if(!$jsonData = json_decode($rawPostData)){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Request Body not set to a valid json type");
        $response->send();
        exit;
    }

    $email = $jsonData->email;
    $amount = $jsonData->amount;
    $refrence = "";
    $callback_url = "";
    $currency = $jsonData->currency;
    



}



?>