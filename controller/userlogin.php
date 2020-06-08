<?php
require_once("db.php");
require_once("../model/Response.php");

try{
    $writeDB = DB::connectWriteDB();
}catch(PDOException $ex){
    error_log("Connection Error -".$ex, 0);
    $response = new Response;
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->_addMessages("Database Connection Error");
    $response->send();
    exit;
}


if(array_key_exists("sessionid", $_GET)){

    $sessionid = $_GET['sessionid'];

    if($sessionid === '' ||  !is_numeric($sessionid)){
        $response = new Response;
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        ($sessionid === '' ? $response->_addMessages("Session ID cannot be blank") : false);
        (!is_numeric($sessionid) ? $response->_addMessages("Session ID must be Numeric") : false);
        $response->send();
        exit;
    }

    if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1){
        $response = new Response;
        $response->setHttpStatusCode(401);
        $response->setSuccess(false);
        (!isset($_SERVER['HTTP_AUTHORIZATION']) ? $response->_addMessages("Access token is missing from the header") : false);
        (strlen($_SERVER['HTTP_AUTHORIZATION']) < 1 ? $response->_addMessages("Access token cannot be blank") : false);
        $response->send();
        exit;
    }

    $accessToken = $_SERVER['HTTP_AUTHORIZATION'];

    if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
         
        try {
            $query = $writeDB->prepare('delete from tblsession where id = :sessionid and accesstoken = :accesstoken');
            $query->bindParam('sessionid', $sessionid, PDO::PARAM_INT);
            $query->bindParam('accesstoken', $accessToken, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response;
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->_addMessages("Log out failed using access token, please refresh and try again later");
                $response->send();
                exit;
            }

            $returnData = array();
            $returnData['id'] = intval($sessionid);

            $response = new Response;
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->_addMessages("Logged out Succesful");
            $response->$returnData($returnData);
            $response->send();
            exit;

        } catch (PDOException $ex) {
            $response = new Response;
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->_addMessages("Logout Failed -- Please try again");
            $response->send();
            exit;
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

        if($_SERVER['CONTENT_TYPE'] !== 'application://json'){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->_addMessages("Content type header not set to JSON");
            $response->send();
            exit;
        }

        $rawPostData = file_get_contents('php://input');

        if(!$jsonData = json_decode($rawPostData)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->_addMessages("Request body is not valid JSON");
            $response->send();
            exit;
        }

        if(!isset($jsonData->refreshtoken) || strlen($jsonData->refreshtoken) < 1){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            (!isset($jsonData->refreshtoken) ? $response->_addMessages("Refresh token not found") : false);
            (strlen($jsonData->refreshtoken) < 1 ? $response->_addMessages("Refresh Token cannot be blank") : false);
            $response->send();
            exit;
        }

        try {
            $refreshToken = $jsonData->refreshtoken;

            $query =$writeDB->prepare('select tblsession.id as sessionid, tblsession.userid as userid, accesstoken, refreshtoken, accesstokenexp, refreshtokenexp from tblsession, registered_users_table where tblsession.userid = registered_users_table.userid and tblsession.id = :sessionid and tblsession.accesstoken = :accesstoken and tblsession.refreshtoken = :refreshtoken');
            $query->bindParam(':sessionid', $sessionid, PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $accessToken, PDO::PARAM_STR);
            $query->bindParam(':refreshtoken', $refreshToken, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->_addMessages("access token or refresh token is incorrect for session id");
                $response->send();
                exit;
            }

            $row = $query->fetch(PDO::FETCH_ASSOC);
            $returned_userid = $row['userid'];
            $returned_accesstoken = $row['accesstoken'];
            $returned_refreshtoken = $row['refreshtoken'];
            $returned_accesstokenexp = $row['accessokenexp'];
            $returned_refreshtokenexp = $row['refreshtokenexp'];
            
            if(strtotime($returned_refreshtokenexp) < time()){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->_addMessages("Refresh token expired - please log in again");
                $response->send();
                exit;
            }

            $accessToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
            $refreshToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
            $accessToken_expiry_seconds = 1200;
            $refreshToken_expiry_seconds = 1209600; 


            $query= $writeDB->prepare('update tblsessions (accesstoken, accesstokenexp, refreshtoken, refreshtokenexp) values (:accessToken, date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND) where id= :sessionid and userid = :userid and accesstoken = :returned_accesstoken and refreshtoken = :returned_refreshtoken )');
            $query->bindParam(':sessionid', $sessionid, PDO::PARAM_INT);
            $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
            $query->bindParam(':accesstoken', $returned_accesstoken, PDO::PARAM_STR);
            $query->bindParam(':refreshtoken', $returned_refreshtoken, PDO::PARAM_STR);
            $query->bindParam(':accesstokenexpiryseconds', $accessToken_expiry_seconds, PDO::PARAM_STR);
            $query->bindParam(':refreshtokenexpiryseconds', $refreshToken_expiry_seconds, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if($rowCount === 0){
                $response = new Response();
                $response->setHttpStatusCode(401);
                $response->setSuccess(false);
                $response->_addMessages("They was an issue refreshing access token please try again");
                $response->send();
                exit;
            }

            $returnData = array();
            $returnData['userid'] = $returned_userid;
            $returnData['sessionid'] = $sessionid;
            $returnData['accesstoken'] = $accessToken;
            $returnData['refreshtoken'] = $refreshToken;
            $returnData['accesstokenex'] = $accessToken_expiry_seconds;
            $returnData['refreshtokenexp'] = $refreshToken_expiry_seconds;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->_addMessages("token refresh successfully");
            $response->setData($returnData);
            $response->send();
            exit;








        } catch (PDOException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->_addMessages("They was an issue refreshing access token please try again");
            $response->send();
            exit;
        }

    }
    else{
        $response = new Response;
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Request Method Not Allowed");
        $response->send();
        exit;
    }
}
elseif (empty($_GET)) {
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        $response = new Response;
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Request Method not Allowed");
        $response->send();
        exit;
    }

    sleep(2);

    if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
        $response = new Response;
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->_addMessages("Content Type header not set to JSON");
        $response->send();
        exit;
    }

    $rawPostData = file_get_contents('php://input');

    if(!$jsonData = json_decode($rawPostData)){
        $response = new Response;
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->_addMessages("Request Body not a valid Json Data");
        $response->send();
        exit;
    }

    if(!isset($jsonData->phoneNumber) || !isset($jsonData->password)){
        $response = new Response;
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (!isset($jsonData->phoneNumber) ? $response->_addMessages("PhoneNumber not Supplied") : false);
        (!isset($jsonData->password) ? $response->_addMessages("Password not Supplied") : false);
        $response->send();
        exit;
    }

    if(strlen($jsonData->phoneNumber) < 1 || strlen($jsonData->phoneNumber) > 13 || strlen($jsonData->password) < 8 || strlen($jsonData->password) >25){
        $response = new Response;
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        (strlen($jsonData->phoneNumber) < 1 ? $response->_addMessages("Phone Number Cannot be Blank") : false);
        (strlen($jsonData->phoneNumber) > 13 ? $response->_addMessages("Phone Number Cannot  greater than 13 characters") : false);
        (strlen($jsonData->password) < 1 ? $response->_addMessages("Password field cannot be blank") : false);
        (strlen($jsonData->password) > 255 ? $response->_addMessages("password field must be less than 8 or greater than 25 characters") : false);
        $response->send();
        exit;
    }

    try {
        $phoneNumber = $jsonData->phoneNumber;
        $password = $jsonData->password;

        $query = $writeDB->prepare('select * from registered_users_table where phone_no = :phone');
        $query->bindParam(':phone', $phoneNumber, PDO::PARAM_STR);
        $query->execute();
        $rowCount = $query->rowCount();

        if($rowCount === 0 || $rowCount > 1){
            $response = new Response;
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->_addMessages("Invalid User or password");
            $response->send();
            exit;
        }

        $row = $query->fetch(PDO::FETCH_ASSOC);

        $id = $row['id'];
        $phoneNumber = $row['phone_no'];
        $location = $row['location'];
        $dbpassword = $row['password'];
        $returned_userid = $row['user_id'];

        if(!password_verify($password, $dbpassword )){
            $response = new Response;
            $response->setHttpStatusCode(401);
            $response->setSuccess(false);
            $response->_addMessages("Login Failed - Wrong Username or Password ");
            $response->send();
            exit;
        }

        $accessToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
        $refreshToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
        $accessToken_expiry_seconds = 1200;
        $refreshToken_expiry_seconds = 1209600;   
    } 
    catch (PDOException $ex) {
        $response = new Response;
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->_addMessages("There was an error logging in");
        $response->send();
        exit;    
    }

    try {

        $writeDB->beginTransaction();

        $query = $writeDB->prepare('insert into tblsession (userid, accesstoken, accesstokenexp, refreshtoken, refreshtokenexp) values(:userid, :accesstoken, date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND) )');
        // $query = $writeDB->prepare('insert into tblsession (userid = :userid, accesstoken =:accesstoken , accesstokenexp, refreshtoken, refreshtokenexp)  , date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND) )');
        $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
        $query->bindParam(':accesstoken', $accessToken, PDO::PARAM_STR);
        $query->bindParam(':refreshtoken', $refreshToken, PDO::PARAM_STR);
        $query->bindParam(':accesstokenexpiryseconds', $accessToken_expiry_seconds, PDO::PARAM_STR);
        $query->bindParam(':refreshtokenexpiryseconds', $refreshToken_expiry_seconds, PDO::PARAM_STR);
        $query->execute();

        $lastSessionId = $writeDB->lastInsertId();

        $writeDB->commit();

        $returnData = array();
        $returnData['id'] = intval($lastSessionId);
        $returnData['accesstoken'] = $accessToken;
        $returnData['accesstokenexp'] = $accessToken_expiry_seconds;
        $returnData['refreshtoken'] = $refreshToken;
        $returnData['refreshtokenexp'] = $refreshToken_expiry_seconds;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->setData($returnData);
        $response->send();
        exit;
        
    } catch (PDOException $ex) {
        $writeDB->rollBack();
        $response = new Response;
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->_addMessages("They was an Issue Logging at the moment in Please Try Again");
        $response->send();
        exit;
    }

}
else {
    $response = new Response;
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->_addMessages("Endpoint not found");
    $response->send();
    exit;
}



?>