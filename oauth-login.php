<?php
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('10700573976-a9t9s8oc5iidm6kop2e1rdomtmbb1q49.apps.googleusercontent.com'); 
$client->setClientSecret('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'); 
$client->setRedirectUri('https://antiquewhite-bear-449942.hostingersite.com/oauth-callback.php'); 
$client->addScope('email');
$client->addScope('profile');

use Google_Service_Oauth2; 

$oauth2 = new Google_Service_Oauth2($client); 

$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit;
?>
