<?php

include dirname(__FILE__).'/EpiCurl.php';
include dirname(__FILE__).'/EpiOAuth.php';
include dirname(__FILE__).'/EpiTwitter.php';
include dirname(__FILE__).'/secret.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);

$loc = $_GET['loc'];

$uri = explode('#',$loc);
$url = $uri[0];

$_SESSION['oauth_callback'] = $url;

header('Location:'.$twitterObj->getAuthorizationUrl());

?>
