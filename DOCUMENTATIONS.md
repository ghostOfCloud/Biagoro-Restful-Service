# AUTHOR: _OAKSOFT_

**check** /controler/airtimetopup.php form line 162 for **PAYSTACK SUPER TRANSFER OFFICIAL INTEGRATION**
check **auto_config\disable_otp_for_transfers.php, auto_config\enable_otp_for_transfers.php and auto_config\finalize_disable_otp_for_transfers.php** for some paystack autoconfig OTP setups...

# registerUser.php documentations... 
url = **http://biagoro.com.ng/biagoro-api**/controller/registerUser.php
# NB: OTP expires after 10 mins of delivery
#   FIRST CALL expects... [POST]
    {
        "phone_no": "08011111111",
        "location": "Enugu",
        "httpRequest": "otp"
    }
    (FALSE) RESPONSE FROM FIRST CALL...
        {
            "success": false,
            "statusCode": 405,
            "messages": [
                "The call was successfull",
                "Phone Number Exists"
            ],
            "data": []
        }
    (TRUE) RESPONSE FROM FIRST CALL...
        {
            "success": true,
            "statusCode": 200,
            "messages": [
                "The call was successfull",
                "OTP Sent Successfully"
            ],
            "data": {
                "phone_no": "08011111112",
                "location": "Enugu",
                "otp": "123640",
                "gen_time": 1586270459      //otp generated time
            }
        }

#   SECOND CALL expects... [POST]
url = **http://biagoro.com.ng/biagoro-api**/controller/registerUser.php
    {
        "phone_no": "08011111111",
        "location": "Enugu",
        "httpRequest": "otpConfirmation",   //api uses it to know what to process
        "otp_1": "985079",                  //sent otp
        "gen_time": "1585122462",           //generated time
        "otp_2": "985079"                   //confirmation otp
    }
    (FALSE) RESPONSE FROM SECOND CALL...
        {
            "success": false,
            "statusCode": 405,
            "messages": [
                "The call was successfull",
                "OTP does not match",
                "Otp Expired!"
            ],
            "data": []
        }
    (TRUE) RESPONSE FROM SECOND CALL...
        {
            "success": true,
            "statusCode": 200,
            "messages": [
                "The call was successfull",
                "OTP Confirmed Successfully"
            ],
            "data": {
                "phone_no": "08011111112",
                "location": "Enugu",
                "otp": "985079",
                "status": "confirmed"
            }
        }

#   THIRD/LAST CALL expects... [POST]
url = **http://biagoro.com.ng/biagoro-api**/controller/registerUser.php
    {
        "phone_no": "08011111111",
        "location": "Enugu",
        "httpRequest": "accountCompletion", //api uses it to know what to process
        "firstname": "Scorpion",
        "lastname": "Code",
        "password_1": "1234567890",         //user password
        "password_2": "1234567890",         //confirmation password
        "dp": "default.png"                 //default profile picture
    }
    (FALSE) RESPONSE FROM THIRD CALL...
        {
            "success": false,
            "statusCode": 405,
            "messages": [
                "The call was successfull",
                "User password does not match"
            ],
            "data": []
        }
    (TRUE) RESPONSE FROM THIRD CALL...
        {
            "success": true,
            "statusCode": 200,
            "messages": [
                "The call was successfull",
                "Account Created Successfully"
            ],
            "data": {
                "phone_no": "08011111111",
                "location": "Enugu",
                "firstname": "Scorpion",
                "lastname": "Code",
                "password": "1234567890"
            }
        }


# User Login Documentation
# Url === **biagoro.com.ng/biagoro-api/userlogin.php**
# userlogin.php expect a [POST], [PATCH] and [DELETE]..
# The Post is for a fresh Login which creates a access token that expires every 2mins of app domancy
# The [PATCH] is to refresh the access token before it expiry deadline
# The [DELETE] is to log out a user the delete will be called every 1mins 50secs or will be called once a user access token expires
# accesstoken = $_SERVER['HTTP_AUTHORIZATION] this will be set on every request posted once a user is logged in 

# Here [POST] method url = **biagoro.com.ng/biagoro-api/userlogin.php**
{
    "phoneNumber":"08147153986",
    "password":password123"
}
(TRUE)
{
    "success":true,
    "statusCode":201,
    "messages":[
        "User logged in Suceessfully"
    ],
    "data":[
        "id":"2",
        "accesstoken":"hashed value",
        "accesstokenexp":"hashed value expiry time",
        "refreshtoken":"hashed refresh token",
        "refreshtokenexp":"hashed refresh token expiry"
    ]
}

# Here [PATCH] method url = **biagoro.com.ng/biagoro-api/userlogin.php?sessionid=id** 
# Here [POST] method url = **biagoro.com.ng/biagoro-api/userlogin.php**



# processShopping.php documentations... 
# processShopping.php expects this below...[POST]
url = **http://biagoro.com.ng/biagoro-api**/controller/processShopping.php?sessionid=12345
    {
        "userId": "987919",
        "phoneNumber": "08012345678",
        "location": "Enugu",
        "items": [
            {"item1":10},
            {"item2":20},
            {"item3":30},
            {"item4":35},
            {"item5":5}
        ],
        "total": 100
    }
    (FALSE) RESPONSE FROM THIS CALL...
        {
            "success": false,
            "statusCode": 405,
            "messages": [
                "The call was successfull",
                "Unable to create market list, try again later!"
            ],
            "data": []
        }
    (TRUE) RESPONSE FROM THIS CALL...
        {
            "success": true,
            "statusCode": 200,
            "messages": [
                "The call was successfull",
                "Market List Created Successfully"
            ],
            "data": []
        }


# Gasrefill.php documentation
# Gasrefill URL ---- biagoro.com.ng/biagoro-api/controller/gasrefill.php?userid=id
{
    "kgram":"50",
    "amount":"2500",
    "address":"The Delivery address entered by the customer"
}

# When the Access token is not set to the server header or expired $_SERVER['HTTP_AUTHORIZATION']
(FALSE) RESPONSE FROM THIS CALL
    {
        "success": false,
        "statusCodde":405,
        "messages":[
            "Invalid Access Token",
        ]
        "data":[],
    }

# (FALSE) Response Due to Insufficient Balance
{
    "success":false,
    "statusCode":405,
    "messages":[
        "Insufficient Balance Top-Up"
    ]
}
