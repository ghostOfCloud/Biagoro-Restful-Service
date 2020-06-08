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
    // checking if userId, phoneNumber items, total and location is posted...
    if(!isset($jsonData->userId) || !isset($jsonData->phoneNumber) || !isset($jsonData->items) || !isset($jsonData->total) || !isset($jsonData->location)){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Invalid market list delivered by user!");
        $response->send();
        exit;
    }
    
    // collect posted data...
    $userId = $jsonData->userId;
    $phoneNumber = $jsonData->phoneNumber;
    $total = $jsonData->total;
    $location = $jsonData->location;
    // ARRAY OF ITEMS, KEYS TO BE THE ITEM AND VALUE TO BE THE PRICE OF THE ITEM
    $items = $jsonData->items;

    $user = new User($writeDB);
    // validate the itmes and their respective ptrices and checks if the sum of the prices equals total posted...
    if(!$user->validateItemList($items, $total)){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Invalid market list items delivered by user!");
        $response->send();
        exit;
    } 

    $order_list = $items;
    $orderId = $user->uniqueOrderId();      //gets unique order id...
    $makeList = $user->createNewItemListRecord($orderId, $userId, $location, $order_list, $total);  //dumps to DB
    if(!$makeList){
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Unable to create market list, try again later!");
        $response->send();
        exit;
    }else{
        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->_addMessages("Market List Created Successfully");
        $response->send();
        exit;
    }
}
?>