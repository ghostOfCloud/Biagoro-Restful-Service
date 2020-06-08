<?php
    // Headers
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Methods, Authorization, X-Requested-With');

    require_once("db.php");
    require_once("../model/Response.php");
    require_once("../model/User.php");

    // Instantiate DB & connect
    try{
        $writeDB = DB::connectWriteDB();
    }catch(PDOException $ex){
        error_log("Connection Error -".$ex, 0);
        $response = new Response;
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->_addMessages("Database Connection Error");
        $response->send();
        exit;
    }


    // Get raw posted data
    $data = json_decode(file_get_contents("php://input"));

    // Instantiate User & store details
    $user = new User($writeDB);

    // Instantiate Response
    $response = new Response();
    
    $user->phone_no = $data->phone_no;
    $user->location = $data->location;

    if(isset($data->httpRequest) and $data->httpRequest=='otp'){
        // otp request

        // validate for correct phone number and get an otp when valid...
        // $tmp_otp = $user->formatUnique($user->generate_unique_id(), 6);
        if($user->validateCorrectPhoneNumer()){
            $user->otp = $user->formatUnique($user->generate_unique_id(), 6);
            $user->recipients[] = $user->phone_no;
            $user->body = 'Your OTP is '.$user->otp;
            if($user->sendOtpMessage()){
                $response->_data = array('phone_no' => $user->phone_no, 'location' => $user->location, 'otp' => $user->otp, 'gen_time' => $user->gen_time);
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->_addMessages("OTP Sent Successfully");
                $response->send();
                exit;
            }else{
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->_addMessages("OTP Not Sent");
                $response->send();
                exit;
            }
        }else{
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            $response->_addMessages("Phone Number Exists");
            $response->send();
            exit;
        }
    }elseif (isset($data->httpRequest) and $data->httpRequest=='otpConfirmation') {
        if($data->otp_1==$data->otp_2 and floor((time()-$data->gen_time)/60)<=$user->_exp_time_interval){
            //otp matched and not expired...
            $response->_data = array('phone_no' => $user->phone_no, 'location' => $user->location, 'otp' => $data->otp_1, 'status' => 'confirmed');
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->_addMessages("OTP Confirmed Successfully");
            $response->send();
            exit;
        }else{
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            !($data->otp_1==$data->otp_2) ? $response->_addMessages("OTP does not match"): true;
            !(floor((time()-$data->gen_time)/60)<=$user->_exp_time_interval) ? $response->_addMessages("Otp Expired!"): true;
            $response->send();
            exit;
        }
    }elseif (isset($data->httpRequest) and $data->httpRequest=='accountCompletion'){
        if($data->password_1==$data->password_2) {
            $options = ['cost'=>11];
            $user->password_hash = password_hash($data->password_1, PASSWORD_BCRYPT, $options);
            $user->user_id = $user->generated_user_id();
            $user->firstname = $data->firstname;
            $user->lastname = $data->lastname;
            $user->dp = isset($data->dp) ? $data->dp: $user->dp;
            if($user->create()){
                $response->_data = array('phone_no' => $user->phone_no, 'location' => $user->location,'firstname'=>$user->firstname, 'lastname'=>$user->lastname, 'password'=>$data->password_1);
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->_addMessages("Account Created Successfully");
                $response->send();
                exit;
            }
        }else{
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            $response->_addMessages("User password does not match");
            $response->send();
            exit;
        } 
    }
?>