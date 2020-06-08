<?php

 class Response {

    private $_success;
    private $_httpStatusCode;
    private $_messages = array();
    private $_data = array();
    private $_toCache = false;
    private $_responseData = array();

    function __construct(){
        $this->setSuccess(true);
        $this->setHttpStatusCode(200);
        // $this->_addMessages("The call was successfull");
    }

    public function setSuccess($success){
        $this->_responseData['success'] = $this->_success = $success;
    }

    public function setHttpStatusCode($httpStatusCode){
        $this->_responseData['statusCode'] = $this->_httpStatusCode = $httpStatusCode;
    }

    public function _addMessages($messages){
        array_push($this->_messages, $messages);
    }

    public function setData($data){
        $this->_data[] = $data;
    }

    public function setToCache($toCache){
        $this->_toCache = $toCache;
    }

    public function setResponseData($responseData){
        $this->_responseData[] = $responseData;
    }

    public function send(){
        header('content-type: application/json;charset=utf-8');
        if($this->_toCache == true){
            header('Cache-control: max-age=60');
        }else{
            header('Cache-control: no-cache, no-store');
        }

        if(($this->_success !== false && $this->_success !== true) || !is_numeric($this->_httpStatusCode) ){
            http_response_code(500);

            $this->_responseData['statusCode'] = 500;
            $this->_responseData['success'] = false;
            $this->_addMessages('Respose Data Error ');
            $this->_responseData['meassages'] = $this->_messages;
        }else{
            http_response_code($this->_httpStatusCode);

            $this->_responseData['statusCode'] = $this->_httpStatusCode;
            $this->_responseData['success'] = $this->_success;
            $this->_responseData['messages'] = $this->_messages; 
            $this->_responseData['data'] = $this->_data;
        }

        echo json_encode($this->_responseData);
    }

}

?>