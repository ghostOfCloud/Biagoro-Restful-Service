<?php
    class User{
        //Database properties
        private $conn;
        private $registered_table = 'registered_users_table';
        private $profile_table = 'profile_table';
        private $stmt;

        //Users properties
        public $id;
        public $accesstoken;
        public $user_id='';
        public $phone_no='';
        public $location='';
        public $status=1;
        public $firstname='';
        public $lastname='';
        public $email='';
        public $dp='default.png';
        public $password_hash;

        public $otp;
        public $gen_time;
        public $_exp_time_interval = 10;    //expires after 10mins

        // MESSAGEBIRD API SETUPS
        //private $_apiAccessKey = '***********************************';
        private $_apiAccessKey = 'UPaSk2xxDx4uVkhizxYPjEb5A'; //test key
        public $originator = 'Oaksoft Digital Solution'; //account name
        public $recipients = array();
        public $body;

        // PAYSTACK API SETUPS
        public $_paystackSecretKey = 'sk_test_***********************************';
        private $bankListWithDetails = array();


        // Constructor with Database
        public function __construct($db) {
            $this->conn = $db;
        }
        
        public function generate_unique_id(){
            $explode = uniqid('', true);
            $exp = explode('.', $explode);
            return $value = end($exp);
        }

        public function isUniqueUserId($value){
            $check_unique_id = "SELECT user_id FROM $this->registered_table WHERE user_id=$value LIMIT 1";
            // Prepare & Execute query
            $this->stmt = $this->conn->prepare($check_unique_id);
            $this->stmt->execute();
            return $this->stmt->rowCount()==1 ? false : true;
        }

        public function formatUnique($value, $length=10){
            return substr($value, 0, $length);
        }

        public function uniqueOrderId(){
            $orderId = $this->generateRandomString(8);
            // Prepare & Execute query
            $this->stmt = $this->conn->prepare("SELECT order_id FROM orders_table WHERE order_id='$orderId' LIMIT 1");
            $this->stmt->execute();
            return $this->stmt->rowCount()==1 ? $this->uniqueOrderId() : $orderId;
        }

        public function generated_user_id(){
            /**METHOD 1 */
                //this place will be the function for generating the random student_identify
                // $check_unique_id = "SELECT id, user_id FROM $this->registered_table ORDER BY id DESC LIMIT 1";
                // $stmt = $this->conn->prepare($check_unique_id);
                // $stmt->execute();
                // $paramGetFields = $stmt->fetch(PDO::FETCH_OBJ);	

                // $unique_id = $stmt->rowCount() == 0 ? null : $paramGetFields->user_id;
                // return $value = $stmt->rowCount() == 0 ? '100000' : ++$unique_id;
            
            /**METHOD 2 */
                $value = $this->formatUnique($this->generate_unique_id(), 6);
                return $this->isUniqueUserId($value) ? $value : $this->generated_user_id();
            
        }

        public function generateRandomString($length=10) {
            $random = substr(md5(rand()), 0, $length);
            return $random;
        }
        // Fetch Users
        public function fetchUsers() {
            // Create query
            $query = 'SELECT * FROM ' . $this->registered_table . ' AS rut, profile_table AS pt
                        WHERE rut.user_id = pt.user_id
                        ORDER BY rut.id DESC';
        
            // Prepare & Execute query
            $this->stmt = $this->conn->prepare($query);
            $this->stmt->execute();

            return $this->stmt;
        }

        public function create() {
            // Create query
            $query1 = 'INSERT INTO ' . $this->registered_table . ' SET user_id = :user_id, phone_no = :phone_no, location = :location, password = :password, status = '.$this->status;
            $query2 = 'INSERT INTO ' . $this->profile_table . ' SET user_id = :user_id, firstname = :firstname, lastname = :lastname, location = :location, phone_no = :phone_no, email = :email, dp = :dp';

            // Prepare statement
            $stmt1 = $this->conn->prepare($query1);
            $stmt2 = $this->conn->prepare($query2);

            // Clean data
            $this->phone_no = htmlspecialchars(strip_tags($this->phone_no));
            $this->location = htmlspecialchars(strip_tags($this->location));
            $this->firstname = htmlspecialchars(strip_tags($this->firstname));
            $this->lastname = htmlspecialchars(strip_tags($this->lastname));
            $this->email = htmlspecialchars(strip_tags($this->email));

            // Bind data
            $stmt1->bindParam(':user_id', $this->user_id);
            $stmt1->bindParam(':phone_no', $this->phone_no);
            $stmt1->bindParam(':location', $this->location);
            $stmt1->bindParam(':password', $this->password_hash);

            $stmt2->bindParam(':user_id', $this->user_id);
            $stmt2->bindParam(':phone_no', $this->phone_no);
            $stmt2->bindParam(':firstname', $this->firstname);
            $stmt2->bindParam(':lastname', $this->lastname);
            $stmt2->bindParam(':location', $this->location);
            $stmt2->bindParam(':email', $this->email);
            $stmt2->bindParam(':dp', $this->dp);

            // Execute query
            if($stmt1->execute()) {
                if($stmt2->execute()){
                    return true;
                }else{
                    // Print error if something goes wrong
                    printf("Error: %s.\n", $stmt2->error);
                    return false;
                }
            }else{
                // Print error if something goes wrong
                printf("Error: %s.\n", $stmt1->error);
                return false;
            }
        }

        public function safesession() {
            session_start();
        }

        public function sendOtpMessage(){
            // send otp to user phone number...
            // set otp expiry time...

            require_once('../api/messagebird/autoload.php');
            $MessageBird = new \MessageBird\Client($this->_apiAccessKey); // Set your own API access key here.

            $Message             = new \MessageBird\Objects\Message();
            $Message->originator = $this->originator;
            $Message->recipients = $this->recipients;
            $Message->body       = $this->body;

            try {
                // $MessageResult = $MessageBird->messages->create($Message);
                // var_dump($MessageResult);
                $this->gen_time = time();
                // return $MessageResult ? true : false;
                return true;
            } catch (\MessageBird\Exceptions\AuthenticateException $e) {
                // That means that your accessKey is unknown
                exit('Wrong login');

            } catch (\MessageBird\Exceptions\BalanceException $e) {
                // That means that you are out of credits, so do something about it.
                exit('No balance');

            } catch (\Exception $e) {
                exit($e->getMessage());
            }
            return false;
        }

        public function validateCorrectPhoneNumer(){
            // Create query
            $query = 'SELECT phone_no FROM ' . $this->registered_table . '
                        WHERE '.$this->phone_no.' = phone_no LIMIT 1';
        
            // Prepare & Execute query
            $this->stmt = $this->conn->prepare($query);
            $this->stmt->execute();

            // $this->otp = $this->formatUnique($this->generate_unique_id(), 6);

            return $this->stmt->rowCount()==1 ? false : true;
        }

        public function requestForOtp(){
            // api for otp applies here...
            // echo 'sending otp request...';
            // generate otp
            print $tmp_otp = $this->generateRandomString(5);
            
            // send the otp to the user phone number...
            // return $this->

        }

        public function getBankCode($bank){
            $curl = curl_init();
            $url = 'https://api.paystack.co/bank';

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache",
                )
            ));
            $request = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                // echo $request;
                $this->bankListWithDetails = $result = json_decode($request, true);
                $result = json_decode($request, true);
                // print_r($result);
                if (array_key_exists('status', $result) && array_key_exists('data', $result) && ($result['status'] == '1')) {
                    for ($i=0; $i < count($result['data']); $i++) { 
                        $bankNames[] = $bankName = $result['data'][$i]['name'];
                        $bankCodes[] = $bankCode = $result['data'][$i]['code'];
                        if($bank==$bankName){
                            return $bankCode;
                        }
                    }
                }
            }
        }

        public function resolveAccount($accNumber, $bankCode){
            $curl = curl_init();
            $url="https://api.paystack.co/bank/resolve?account_number=$accNumber&bank_code=$bankCode";

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache",
                )
            ));
            $request = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                // echo $request;
                $this->bankListWithDetails = $result = json_decode($request, true);
                $result = json_decode($request, true);
                // print_r($result);
                if (array_key_exists('status', $result) && array_key_exists('data', $result) && ($result['status'] == '1')) {
                    return true;
                }
            }

            return false;
        }

        public function createTransferRecipient($accNo, $bankCode){
            $curl = curl_init();
            $url='https://api.paystack.co/transferrecipient';
            $data = array(
                "type" => "nuban",
                "name" => "User Accout $accNo",
                "account_number" => "$accNo",
                "bank_code" => "$bankCode",
                "currency" => "NGN",
            );
            $data = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
                CURLOPT_POSTFIELDS => $data
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            
            curl_close($curl);
            
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              $result = json_decode($response, true);
            //   print_r($result);
              if (array_key_exists('status', $result) && array_key_exists('data', $result) && ($result['status'] == '1')) {
                return $result['data']['recipient_code'];
              }
            }

            return false;
        }

        public function initializeTransfer($recipient, $amount, $reason){
            $curl = curl_init();
            $url='https://api.paystack.co/transfer';
            $data = array(
                "source" => "balance",
                "reason" => "$reason",
                "amount" => $amount,
                "recipient" => "$recipient"
            );
            $data = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
                CURLOPT_POSTFIELDS => $data
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            
            curl_close($curl);
            
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              $result = json_decode($response, true);
            //   print_r($result);
              if (array_key_exists('status', $result) && array_key_exists('data', $result) && ($result['status'] == '1')) {
                if($result['message']=="Transfer requires OTP to continue"){
                    return false;
                }
                return true;
              }
            }

            return false;
        }

        public function finalizeTransfer($transfer_code, $otp){
            $curl = curl_init();
            $url='https://api.paystack.co/transfer/finalize_transfer';
            $data = array(
                "transfer_code" => "$transfer_code",
                "otp" => "$otp"
            );
            $data = json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
                CURLOPT_POSTFIELDS => $data
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            
            curl_close($curl);
            
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              $result = json_decode($response, true);
            //   print_r($result);
              if (array_key_exists('status', $result) && array_key_exists('data', $result) && ($result['status'] == '1')) {
                //
                return true;
              }
            }

            return false;
        }

        public function disableOtpForTransfers(){
            $curl = curl_init();
            $url='https://api.paystack.co/transfer/disable_otp';
            // $data = array(
            //     "transfer_code" => "$transfer_code",
            //     "otp" => "$otp"
            // );
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache"
                )
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              $result = json_decode($response, true);
              if (array_key_exists('status', $result) && ($result['status'] == '1')) {
                return true;
              }
            }
            return false;
        }

        public function finalizedisableOtpForTransfers($otp){
            $curl = curl_init();
            $url='https://api.paystack.co/transfer/disable_otp_finalize';
            $data = array(
                "otp" => "$otp"
            );
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
                CURLOPT_POSTFIELDS => $data
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              $result = json_decode($response, true);
              if (array_key_exists('status', $result) && ($result['status'] == '1')) {
                return true;
              }
            }
            return false;
        }

        public function enableOtpForTransfers(){
            $curl = curl_init();
            $url='https://api.paystack.co/transfer/enable_otp';
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $this->_paystackSecretKey",
                    "Cache-Control: no-cache"
                )
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              $result = json_decode($response, true);
              if (array_key_exists('status', $result) && ($result['status'] == '1')) {
                return true;
              }
            }
            return false;
        }

        public function validateItemList($variable, $total){
            $itemsSum = 0;
            foreach ($variable as $key => $value) {
                foreach ($value as $keyInner => $valueInner) {
                    if(!is_numeric($valueInner)){
                        return false;
                    }
                    $itemsSum += $valueInner;
                    if(empty($keyInner) || empty($valueInner)){
                        return false;
                    }
                }
            }
            return $itemsSum==$total ? true: false;
        }

        public function createNewItemListRecord($orderId, $userId, $location, $order_list, $order_total){
            try {
                $order_list = json_encode($order_list);
                $sql = "INSERT INTO orders_table SET order_id='$orderId', user_id='$userId', location='$location', order_list='$order_list', order_total='$order_total'";
                // Prepare & Execute query
                $this->stmt = $this->conn->prepare($sql);
                return $this->stmt->execute() ? true : false;
            } catch (\Throwable $th) {
                // print($th);
                return false;
            }
        }

        public function checkLoggedUserInfo($sessionid){
            try{
                $query = $this->conn->prepare('select * from tblsession where id=:sessionid');
                $query->bindParam(':sessionid', $sessionid, PDO::PARAM_INT);
                // $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
                $query->execute();
                $row = $query->fetch(PDO::FETCH_ASSOC);
                $returned_userid = $row['userid'];
        

                $query = $this->conn->prepare('select * from registered_users_table where user_id=:userid');
                $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
                // $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
                $query->execute();
                $row = $query->fetch(PDO::FETCH_ASSOC);
                $account_no = $row['phone_no'];

                return array($returned_userid, $account_no);;
        
            }
            catch(PDOException $ex){
                return false;
            }
        }
        
        public function checkBalance($user_id, $account_no, $amount){
            try{
                $query = $this->conn->prepare('select * from user_fund_table where userid = :userid and account_no = :account_no');
                $query->bindParam(':userid', $user_id, PDO::PARAM_INT);
                $query->bindParam(':account_no', $account_no, PDO::PARAM_STR);
                $query->execute();

                $numRow = $query->rowCount();
                
                $row = $query->fetch(PDO::FETCH_ASSOC);
                $balance = $row['balance'];
        
                if(strlen($balance) === '' || $amount > $balance){
                    $response = new Response();
                    $response->setHttpStatusCode(405);
                    $response->setSuccess(false);
                    $response->setData($balance);
                    $response->_addMessages("Insufficient Balance. top up your account and try again later");
                    $response->send();
                    exit;
                }

                
                return $balance;
            }
            catch(PDOException $ex){
                $response = new Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->_addMessages("An Error Occured Please Try Again");
                $response->send();
                exit;
            }
        }

        public function initiateTransaction($user_id, $account_no, $amount, $address, $quantity, $transaction_id){
            try{   
                
                $status = "Pending";
                $this->conn->beginTransaction();
                $insert = $this->conn->prepare('INSERT INTO `customers_order`(`id`, `userid`, `account_no`, `amount`, `address`, `quantity`, `transaction_id`, `status`) VALUES(:userid, :account_no, :amount, :address, :quantity, :transaction_id, :status');
                $insert->bindParam(':userid', $user_id, PDO::PARAM_INT);
                $insert->bindParam(':account_no', $account_no, PDO::PARAM_INT);
                $insert->bindParam(':amount', $amount, PDO::PARAM_INT);
                $insert->bindParam(':address', $address, PDO::PARAM_STR);
                $insert->bindParam(':quantity', $quantity, PDO::PARAM_STR);
                $insert->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
                $insert->bindParam(':status', $status, PDO::PARAM_STR);
                $insert->execute();

                $lastSessionId = $this->conn->lastInsertId();

                $this->conn->commit();

                $returnData = array();
                $returnData['id'] = intval($lastSessionId);
                $returnData['address'] = $address;
                $returnData['quantity'] = $quantity;
                $returnData['amount'] = $amount;
                $returnData['transaction_id'] = $transaction_id;

                $response = new Response();
                $response->setHttpStatusCode(201);
                $response->setSuccess(true);
                $response->setData($returnData);
                $response->send();
                exit;
            }catch(PDOException $ex){
                $response = new Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->_addMessages("Unknown Error ");
                $response->send();
                exit;
            }
        }

        public function transaction($transaction_id, $returned_userid, $network, $status){
            try {
                $query = $this->conn->prepare('insert into transaction_table set userid =:userid, service =:network,  transaction_id =:transaction_id, status =:status');
                $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
                $query->bindParam(':network', $network, PDO::PARAM_STR);
                $query->bindParam(':transaction_id', $transaction_id, PDO::PARAM_STR);
                $query->bindParam(':status', $status, PDO::PARAM_STR);
                $query->execute();
    
                $response = new Response;
                $response->setHttpStatusCode(200);
                $response->setSuccess(false);
                ($status == "SUCCESSFUL" ? $response->_addMessages("Airtime top-up successful") : false);
                ($status == "FAILED" ? $response->_addMessages("Airtime top-up Failed") : false);
                $response->send();
                exit;
        
            } catch (PDOException $ex) {
                $response = new Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->_addMessages("An error occured pls try again later");
                $response->send();
                exit;
            }
        
        }
    
        public function updateBalance($user_id, $amount, $balance, $account_no){
            $new_balance = $amount - $balance;

            try{    
                $update = $this->conn->prepare('update user_fund_table set balance=:new_balance where userid=:userid and account_no=:account_no');
                $update->bindParam(':userid', $user_id, PDO::PARAM_INT);
                $update->bindParam(':new_balance', $new_balance, PDO::PARAM_INT);
                $update->bindParam(':account_no', $account_no, PDO::PARAM_INT);
                $update->execute();

                }catch(PDOException $ex){
                    $response = new Response();
                    $response->setHttpStatusCode(405);
                    $response->setSuccess(false);
                    $response->_addMessages("An error occured Updating Balance pls try again later");
                    $response->send();
                    exit;
                }

        }
    
        public function fundWallet($cardName, $cardNumber, $cardCvc, $cardPin){
            
        }
    
    }



?>