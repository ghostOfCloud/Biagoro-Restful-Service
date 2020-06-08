<?php  
               $url = "mobilenig.com/API/bills/user_check?username=TOBECHIPASCHAL&api_key=053847262bf0cca7b80f3b212732c8c9&service=GOTV&number=7028918813";
               $ch = curl_init();
               curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); //return as a variable
               curl_setopt($ch, CURLOPT_URL, $url);
               curl_setopt($ch, CURLOPT_HEADER, false);
               curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
               $response = curl_exec($ch);
               curl_close($ch);
               $array = json_decode($response, true); //decode the JSON response
               
               $request = array();
        //        $request = $response["trans_id"];
               $request = $response["details"]["accountStatus"];
               $request = $response["details"]["firstName"];
               $request = $response["details"]["lastName"];
               $request = $response["details"]["customerType"];
               $request = $response["details"]["customerNumber"];
               
               echo($request);
        //        {"details":
        //                 {
        //                 "accountStatus":"SUSPENDED",
        //                 "firstName":"TOBECHI",
        //                 "lastName":"PASCHAL",
        //                 "customerType":"GOTVSUD",
        //                 "invoicePeriod":1,
        //                 "dueDate":"2001-01-01T00:00:00+01:00",
        //                 "customerNumber":102785612
        //                 }
        //         }
