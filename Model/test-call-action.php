<?php
 function callAction(string $api)
 {
     rateLimitingRequests($api);
     echo 'will $this->makeRequest for ' . $api . PHP_EOL;
     //$result = $this->makeRequest($type, $api, $args, $this->token, $body, $logOutput);
     //return $this->format($result, $format);
 }

 /**
  * Rate limiting for Lengow API
  */
 function rateLimitingRequests(string $api): void
 {
     $wait = 0;
     if ($api === '/v3.0/orders') {
         $wait = getWaitLimitOrderRequests();
     }

     else if ($api === '/v3.0/orders/actions/') {
         $wait = getWaitLimitActionRequests();
     }

     else if ($api === '/v3.0/orders/moi/') {
         $wait = getWaitLimitOrderRequests();
     }

     if ($wait > 0) {
         echo "Will sleep for $wait seconds for limit ".$api . PHP_EOL;
         sleep($wait);
     }
 }

 /**
  * Limit the number of order requests
  */
 function getWaitLimitOrderRequests()
 {
     static $nbRequest = 0;
     static $timeStart = null;
     if ($timeStart === null) {
        $timeStart = time();
     }
     $nbRequest++;
     echo 'nbRequest Orders: ' . $nbRequest . PHP_EOL;
     if ($nbRequest >= 500) {
         $timeDiff = time() - $timeStart;
         $nbRequest = 0;
         $timeStart = time();
         if ($timeDiff < 60) {
             return (60 - $timeDiff);
         }
     }

     return null;
 }

 /**
  * Limit the number of action requests
  */
 function getWaitLimitActionRequests()
 {
     static $nbRequest = 0;
     static $timeStart = null;
     if ($timeStart === null) {
        $timeStart = time();
     }
     $nbRequest++;
     echo 'nbRequest Action: ' . $nbRequest . PHP_EOL;
     if ($nbRequest >= 500) {
         $timeDiff = time() - $timeStart;
         $nbRequest = 0;
         $timeStart = time();
         if ($timeDiff < 60) {
             return (60 - $timeDiff);
         }
     }

     return null;
 }


 for ($i=0; $i < 1000; $i++) {
     callAction('/v3.0/orders');
     callAction('/v3.0/orders/moi/');
     callAction('/v3.0/orders/actions/');
 }
