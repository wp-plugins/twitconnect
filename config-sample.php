<?php
//************************************************************************************
//* Twitter Profile
//* 
//* By default, the plugin will display the url from the person's Twitter profile.
//* This is usually their personal blog or website.
//* If you would prefer to display the url to the person's Twitter profile instead,
//* set $twc_use_twitter_profile = 'Y'
//************************************************************************************
$twc_use_twitter_profile = 'N';

//************************************************************************************
//* Template
//* 
//* Customize the language that appears just before the Twit Connect Button.
//************************************************************************************
$twc_template = <<<KEEPME
<div id="twc_connect"><p><strong>Twitter Users!</strong><br />
Enter your personal information in the form or sign in with your Twitter account by clicking the button below.</p></div>
KEEPME;

?>