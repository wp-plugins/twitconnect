<?php
/*
Plugin Name: Twit Connect
Author:  Shannon Whitley 
Author URI: http://voiceoftech.com/swhitley/
Plugin URI: http://www.voiceoftech.com/swhitley/?page_id=706
Description: Integrate Twitter and Wordpress.  Provides single-signon using oAuth and displays Twitter avatars.
Acknowledgments:  
  Adam Hupp  (email : adam at hupp.org / ahupp at facebook.com) - Facebook Plugin  
  Brooks Bennett (http://www.brooksskybennett.com/) - oAuth Popup
  Peter Denton (http://twibs.com/oAuthButtons.php) - 'Signin with Twitter' button
  Jaisen Mathai (http://www.jaisenmathai.com/blog/) - EpiOAuth
Version: 1.05
************************************************************************************
M O D I F I C A T I O N S
1. 03/23/2009 Shannon Whitley - Initial Release
2. 04/16/2009 Shannon Whitley - Option to display Twitter url.  
                                Display email address to encourage changing it.
                                Button bookmark.
                                Limit avatar display to Twitter subscribers.
3. 04/20/2009 Shannon Whitley   Config Page
                                Local oAuth Processing
                                Button image selection
4. 04/21/2009 Shannon Whitley   PHP 5 required for Epi.
5. 04/24/2009 Shannon Whitley   Workaround for removal of oauth_callback.
                                Removed the closeme.php page.
************************************************************************************
************************************************************************************
I N S T R U C T I O N S

Modify the Twit Connect template in the config.php file.

There are two ways to display the button:

1) Add the following code to your comment page where you want the button to appear:

    <!-- Begin Twit Connect -->
    <?php if(function_exists('twit_connect')){twit_connect();} ?>
    <!-- End Twit Connect -->

2) Or, simply allow this plugin to render the template where the code below 
   exists (usually already present but below the form):
 
    <?php do_action('comment_form', $post->ID); ?>

************************************************************************************
*/

if(!version_compare(PHP_VERSION, '5.0.0', '<'))
{
    include dirname(__FILE__).'/EpiCurl.php';
    include dirname(__FILE__).'/EpiOAuth.php';
    include dirname(__FILE__).'/EpiTwitter.php';
    include dirname(__FILE__).'/secret.php';
}


//************************************************************************************
//* Button Template - This is the default.
//* Copy the config-sample.php file to config.php and modify the text there.
//************************************************************************************
$twc_template = <<<KEEPME
<div id="twc_connect"><p><strong>Twitter Users!</strong><br />Enter your personal information in the form or sign in with your Twitter account by clicking the button below.</p></div>
KEEPME;
if(file_exists(dirname(__FILE__).'/config.php'))
{
    include(dirname(__FILE__).'/config.php');
}
$twc_template_temp = get_option("twc_template");
if(strlen($twc_template_temp) > 0)
{
    $twc_template = $twc_template_temp;
}

$twc_use_twitter_profile_temp = get_option('twc_use_twitter_profile');
if(strlen($twc_use_twitter_profile_temp) > 0)
{
    $twc_use_twitter_profile = $twc_use_twitter_profile_temp;
}


//************************************************************************************
$twc_local = get_option("twc_local");
$twc_btn_choice = get_option("twc_btn_choice");

if($twc_local == 'Y')
{
    $twc_url = WP_PLUGIN_URL.'/twitconnect';
    $twc_page = 'start.php';
    $twc_a = '"'.$twc_url.'/closeme.php"';
}
else
{
    $twc_url = 'http://mytweeple.com';
    $twc_page = 'twc.aspx';
    $twc_a = 'location.href+"#twcbutton"';
}

$twc_btn_image1 = "http://s3.amazonaws.com/static.whitleymedia/twitconnect.png";
$twc_btn_image2 = "http://s3.amazonaws.com/static.whitleymedia/twitter_button_1_lo.gif";

$twc_btn_image = $twc_btn_image1;
if($twc_btn_choice == '2')
{
   $twc_btn_image = $twc_btn_image2;
}

add_action('init', 'twc_init');
add_filter("get_avatar", "twc_get_avatar",10,4);
add_action('comment_form', 'twc_show_twit_connect_button');
add_action("admin_menu", "twc_config_page");
add_action("wp_head", "twc_wp_head");

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

function twc_wp_head()
{
    if(is_user_logged_in())
    {
        if(isset($_GET['oauth_token']))
        {
            echo '<script type="text/javascript">window.opener.twc_bookmark("");window.close();</script>';
        }  
    }
}

function twc_show_twit_connect_button($id='0')
{
    global $twc_url,$twc_page,$twc_loaded, $twc_template, $user_email, $twc_btn_image, $twc_a;

    if(is_user_logged_in())
    {
        echo '<p>Your email address is '.'<a name="twcbutton" href="'.get_option('siteurl').'/wp-admin/profile.php">'.$user_email.'</a>.</p>';
    }

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
    function twc_bookmark(){
       var url=location.href;
       var temp = url.split("#");
       url = temp[0];
       url += "#twcbutton";
       location.href = url;
       location.reload();
    }
    if(document.getElementById("twc_connect"))
    {
        var button = document.createElement("button");
        button.id = "twc_button";
        button.setAttribute("class","btn");
        button.onclick = function(){window.open("'.$twc_url.'/'.$twc_page.'?a="+escape('.$twc_a.')+"&loc="+escape(location.href), "twcWindow","width=800,height=400,left=150,top=100,scrollbar=no,resize=no");return false;};
        button.innerHTML = "<img src=\''.$twc_btn_image.'\' style=\'margin:0;\'>";
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
        if(isset($_GET['oauth_token']))
        {
            twc_EpiConfirm();
        }
    }
}

function twc_get_avatar($avatar, $id_or_email='',$size='32') {
  global $comment;

  if(is_object($comment))
  {
      $id_or_email = $comment->user_id;
  }

  if (is_object($id_or_email)) {
     $id_or_email = $id_or_email->user_id;
  }

  if (get_usermeta($id_or_email, 'twcid')) {
    $user_info = get_userdata($id_or_email);
    $out = 'http://purl.org/net/spiurl/'.$user_info->user_login;
    $avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
    return $avatar;
  } else {
    return $avatar;
  }
}



function twc_TwitterInfoGet($req_key)
{
    global $twc_url,$twc_page;

    if ( !class_exists('Snoopy') ) {
        include_once( ABSPATH . WPINC . '/class-snoopy.php' );
    } 

    $snoopy = new Snoopy();
    $snoopy->agent = 'Twit Connect (Snoopy)';
    $snoopy->host = $_SERVER[ 'HTTP_HOST' ];
    $snoopy->read_timeout = "180";
    $url = $twc_url.'/'.$twc_page.'?twc_req_key='.urlencode($req_key);

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

function twc_EpiConfirm()
{
    global $consumer_key, $consumer_secret;
    $twitterObj = new EpiTwitter($consumer_key, $consumer_secret);

    $twitterObj->setToken($_GET['oauth_token']);
    $token = $twitterObj->getAccessToken();
    $twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);
    $twitterInfo= $twitterObj->get_accountVerify_credentials();
    $twitterInfo->response;
   
    twc_Login($twitterInfo->id.'|'.$twitterInfo->screen_name.'|'.$twitterInfo->name.'|'.$twitterInfo->url);
   
}

function twc_Login($pdvUserinfo) {
  global $twc_use_twitter_profile;

  $userinfo = explode('|',$pdvUserinfo);
  if(count($userinfo) < 4)
  {
      wp_die("An error occurred while trying to contact Twit Connect.");
  }

  //Use the url from the Twitter profile.
  $user_url = $userinfo[3];

  if($twc_use_twitter_profile == 'Y')
  {
      //Use the Twitter profile.
      $user_url = 'http://twitter.com/'.$userinfo[1];
  }

  $userdata = array(
    'user_pass' => wp_generate_password(),
    'user_login' => $userinfo[1],
    'display_name' => $userinfo[2],
    'user_url' => $user_url,
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

//*****************************************************************************
//* twc_config_page - WordPress admin page
//*****************************************************************************
function twc_config_page()
{
	add_submenu_page("options-general.php", "Twit Connect",
		"Twit Connect", 10, __FILE__, "twitconnect_configuration");
}

//*****************************************************************************
//* twitconnect_configuration - WordPress admin page processing
//*****************************************************************************
function twitconnect_configuration()
{
        global $twc_btn_image1, $twc_btn_image2, $twc_template;

		// Save Options
		if (isset($_POST["twc_save"])) {
			// ...the options are updated.
			update_option('twc_consumer_key', stripslashes($_POST["twc_consumer_key"]) );
			update_option('twc_consumer_secret', stripslashes($_POST["twc_consumer_secret"]) );
                        update_option('twc_btn_choice', $_POST["twc_btn_choice"]);
                        if(!version_compare(PHP_VERSION, '5.0.0', '<'))
                        {
                              update_option('twc_local', $_POST["twc_local"]);
                        }
                        else
                        {
                              wp_die('PHP 5 or greater is required to run Self-Hosted oAuth.');
                        }
                        update_option('twc_template', stripslashes($_POST["twc_template"]));
                        update_option('twc_use_twitter_profile', $_POST["twc_use_twitter_profile"]);
                        $secret_file = dirname(__FILE__).'/secret.php';
                        $fh = fopen($secret_file, 'w') or die("Can't open secret file");
                        $stringData = '<?php'."\n";
                        $stringData .= '$consumer_key = \''.stripslashes($_POST["twc_consumer_key"]).'\';'."\n";
                        $stringData .= '$consumer_secret = \''.stripslashes($_POST["twc_consumer_secret"]).'\';'."\n";
                        $stringData .= '?>'."\n";
                        fwrite($fh, $stringData);
                        fclose($fh);
		}
		
		// Get the Data
		$twc_consumer_key = get_option('twc_consumer_key');
		$twc_consumer_secret = get_option('twc_consumer_secret');
                $twc_btn_choice = get_option('twc_btn_choice');
                $twc_local = get_option('twc_local');
                $twc_template_temp = $twc_template;
                $twc_template = get_option('twc_template');
                if(strlen($twc_template) == 0)
                {
                    $twc_template = $twc_template_temp;
                }
                $twc_use_twitter_profile = get_option('twc_use_twitter_profile');

                $twc_use_twitter_profile = $twc_use_twitter_profile == 'Y' ?
			"checked='true'" : "";

                $twc_local = $twc_local == 'Y' ?
			"checked='true'" : "";
		
                $btn1 = $twc_btn_choice == '1' ?
			"checked='true'" : "";
		$btn2 = $twc_btn_choice == '2' ?
			"checked='true'" : "";

?>
    <h3>Twit Connect Configuration</h3>
    <form action='' method='post' id='twc_conf'>
      <table cellspacing="20" width="60%">
        <tr>
        <td valign="top">Self-Hosted oAuth</td>
        <td>
          <input type='checkbox' name='twc_local' value='Y' 
            <?php echo $twc_local ?>/> (PHP 5 required)
            <br/><small>Check this box to use your own Consumer Key and Consumer Secret.</small>
            <br/><small>For this option, you must register a new application at <a href="http://twitter.com/oauth/">Twitter.com</a></small>
          </td>
        </tr>
        <tr>
          <td width="20%" valign="top">Consumer Key</td>
          <td>
            <input type='text' name='twc_consumer_key' value='<?php echo $twc_consumer_key ?>' size="50" />
                <br/><small>
                  (Optional) Your application consumer key from Twitter.com.
                </small>
              
          </td>
        </tr>
        <tr>
          <td width="20%" valign="top">Consumer Secret</td>
          <td>
            <input type='text' name='twc_consumer_secret' value='<?php echo $twc_consumer_secret ?>' size="50" />
                <br/><small>
                  (Optional) Your application consumer secret from Twitter.com.
                </small>
          </td>
        </tr>
        <tr>
        <td valign="top">Select a Button</td>
        <td>
          <input type='radio' name='twc_btn_choice' value='1' 
            <?php echo $btn1 ?>/> <img src="<? echo $twc_btn_image1 ?>" /><br/><br/>
          <input type='radio' name='twc_btn_choice' value='2' 
            <?php echo $btn2 ?>/> <img src="<? echo $twc_btn_image2 ?>" />
          </td>
        </tr>
        <tr>
        <td valign="top">Author Link</td>
        <td>
          <input type='checkbox' name='twc_use_twitter_profile' value='Y' 
            <?php echo $twc_use_twitter_profile ?>/>
            <br/><small>Check this box if you would like the author link to point to the author's Twitter profile (http://twitter.com/{username}).</small>
          </td>
        </tr>
   <tr>
          <td valign="top">Intro Text</td>
          <td>
            <textarea name='twc_template' rows="5" cols="50"><?php echo $twc_template; ?></textarea>
            <br/>
            <small>The text that appears above the Twit Connect button.</small>
          </td>
        </tr>
      </table>
      <p class="submit">
        <input type='submit' name='twc_save' value='Save Settings' />
      </p>
    </form>
<?php
			
}

?>