<?php
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('10700573976-ocata2iovvvs7m2cgf3ghs2c417stf98.apps.googleusercontent.com'); 
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
