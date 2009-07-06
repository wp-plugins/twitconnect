<?php

include dirname(__FILE__).'/EpiCurl.php';
include dirname(__FILE__).'/EpiOAuth.php';
include dirname(__FILE__).'/EpiTwitter.php';
include dirname(__FILE__).'/secret.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);

$loc = $_GET['loc'];

$uri = explode('#',$loc);
$url = $uri[0];

if(strpos($url,'?') === false)
{
    $url .= '?';
}
else
{
    $url .= '&';
}

$url .= 'oauth_token=oauth_token_replacement#twcbutton';


header('Location:'.$twitterObj->getAuthorizationUrl() . '&oauth_callback='.urlencode($_GET['a'].'?a='.urlencode($url)));

?>
