<?php
require_once __DIR__ . '/../vendor/autoload.php';
$google_client_id = 'AAAAAAAAA';
$google_client_secret = 'AAAAAAAAAAAAA';
$google_redirect_url = 'https://moncamp.fr/api/google_callback.php'; 
$client = new Google_Client();
$client->setClientId($google_client_id);
$client->setClientSecret($google_client_secret);
$client->setRedirectUri($google_redirect_url);
$client->addScope('email');
$client->addScope('profile');
?>