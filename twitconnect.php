<?php
/*
Plugin Name: Twit Connect
Author:  Shannon Whitley 
Author URI: http://voiceoftech.com/swhitley/
Plugin URI: http://www.voiceoftech.com/swhitley/?p=683
Description: Integrate Twitter and Wordpress.  Provides single-signon and avatars.
Acknowledgments:  
  Adam Hupp  (email : adam at hupp.org / ahupp at facebook.com) - Facebook Plugin  
  Brooks Bennett (http://www.brooksskybennett.com/) - oAuth Popup
Version: .6
************************************************************************************
M O D I F I C A T I O N S
1. 03/23/2009 Shannon Whitley - Initial Release
************************************************************************************
************************************************************************************
I N S T R U C T I O N S

Modify the Twit Connect template in the config.php file.

There are two ways to display the button:

1) Add the following code to your comment page where you want the button to appear:

    <!-- Begin Twit Connect -->
    <?php twit_connect(); ?>
    <!-- End Twit Connect -->

2) Or, simply allow this plugin to render the template where the code below 
   exists (usually already present but below the form):
 
    <?php do_action('comment_form', $post->ID); ?>

************************************************************************************
*/

//************************************************************************************
//* Button Template - This is the default.
//* Copy the config-sample.php file to config.php and modify the text there.
//************************************************************************************
$twc_template = <<<KEEPME
<div id="twc_connect"><p><strong>Twtter Users!</strong><br />Enter your personal information below, or sign in with your Twitter account by clicking the button below.</p></div>
KEEPME;
if(file_exists(dirname(__FILE__).'/config.php'))
{
    include(dirname(__FILE__).'/config.php');
}
//************************************************************************************

add_action('init', 'twc_init');
add_filter("get_avatar", "twc_get_avatar",10,4);
add_action('comment_form', 'twc_show_twit_connect_button');

$twc_loaded = false;

function twit_connect()
{
    global $twc_loaded;
    if($twc_loaded)
    {
        return;
    }
    twc_show_twit_connect_button();
    $twc_loaded = true;
}

function twc_show_twit_connect_button($id='0')
{
    global $twc_loaded, $twc_template;

    if($twc_loaded || is_user_logged_in())
    {
        return;
    }

    echo '<style id="twc_styles_text" type="text/css">
        #twc_connect button{ 
        margin:0 7px 0 0; 
        background:none; 
        border:none; 
        cursor:pointer; 
        padding:5px 10px 6px 7px; /* Links */} 
        #twc_connect button{ 
        width:auto; 
        overflow:visible; 
        padding:4px 10px 3px 7px; /* IE6 */ 
        } 
        twc_connect button img{ 
        margin:0 3px -3px 0 !important; 
        padding:0; 
        border:none; 
        width:16px; 
        height:16px;}
    </style>';

    echo $twc_template;

    echo '<script type="text/javascript">
    if(document.getElementById("twc_connect"))
    {
        var button = document.createElement("button");
        button.id = "twc_button";
        button.setAttribute("class","btn");
        button.onclick = function(){window.open("http://mytweeple.com/twc.aspx?a="+escape(location.href), "twcWindow","width=800,height=400,left=150,top=100,scrollbar=no,resize=no");return false;};
        button.innerHTML = "<img src=\'http://s3.amazonaws.com/static.whitleymedia/twitconnect.png\' style=\'margin:0;\'>";
        document.getElementById("twc_connect").appendChild(button);}</script>';

    $twc_loaded = true;

}


function twc_init()
{
    if(!is_user_logged_in())
    {
        if(isset($_GET['twc_req_key']))
        {
            twc_TwitterInfoGet($_GET['twc_req_key']);
        }
    }
}

function twc_get_avatar($avatar, $id_or_email='',$size='32') {
  global $comment;

  if(is_object($comment))
  {
      $id_or_email = $comment;
  }

  if (!is_object($id_or_email)) {
    return $avatar;
  }

  if ($id_or_email->user_id > 0) {
    $user_info = get_userdata($id_or_email->user_id);
    $out = 'http://purl.org/net/spiurl/'.$user_info->user_login;
    $avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
    return $avatar;
  } else {
    return $avatar;
  }
}



function twc_TwitterInfoGet($req_key)
{

    if ( !class_exists('Snoopy') ) {
        include_once( ABSPATH . WPINC . '/class-snoopy.php' );
    } 

    $snoopy = new Snoopy();
    $snoopy->agent = 'Twit Connect (Snoopy)';
    $snoopy->host = $_SERVER[ 'HTTP_HOST' ];
    $snoopy->read_timeout = "180";
    $url = 'http://www.mytweeple.com/twc.aspx?twc_req_key='.urlencode($req_key);

    if(@$snoopy->fetchtext($url))
    {
        $results = $snoopy->results;
        twc_Login($results);
    } 
    else {
        $results = "Error contacting Twit Connect: ".$snoopy->error."\n";
        wp_die($results);
    }
   
}


function twc_Login($pdvUserinfo) {
  $userinfo = explode('|',$pdvUserinfo);
  if(count($userinfo) < 4)
  {
      wp_die("An error occurred while trying to contact Twit Connect.");
  }

  $userdata = array(
    'user_pass' => wp_generate_password(),
    'user_login' => $userinfo[1],
    'display_name' => $userinfo[2],
    'user_url' => $userinfo[3],
    'user_email' => 'nomail@nomail.com'
  );

  if(!function_exists('wp_insert_user'))
  {
      include_once( ABSPATH . WPINC . '/registration.php' );
  } 
  
  $wpuid = twc_twitteruser_to_wpuser($userinfo[0]);
  

  if(!$wpuid)
  {
      $wpuid = wp_insert_user($userdata);
      if($wpuid)
      {
         update_usermeta($wpuid, 'twcid', "$userinfo[0]");
      }
  }
  else
  {
    $userdata = array(
    'ID' => $wpuid,
    'user_login' => $userinfo[1],
    'display_name' => $userinfo[2],
    'user_url' => $userinfo[3],
    );
    wp_update_user( $userdata );
  }
  
  if($wpuid) {
     wp_set_auth_cookie($wpuid, true, false);
     wp_set_current_user($wpuid);
  }
}

function twc_get_user_by_meta($meta_key, $meta_value) {
  global $wpdb;
  $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
  return $wpdb->get_var($wpdb->prepare($sql, $meta_key, $meta_value));
}

function twc_twitteruser_to_wpuser($twcid) {
  return twc_get_user_by_meta('twcid', $twcid);
}

?>