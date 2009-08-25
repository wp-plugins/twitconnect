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
  Alexander Morris (http://www.vlogolution.com) - Unique account fix
Version: 1.5
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
6. 06/02/2009 Shannon Whitley   Separately identify Twitter accounts using a suffix.
                                Check for existing user prior to add.
                                Change the user name if changed on Twitter.
                                New button image.
7. 06/09/2009 Shannon Whitley   Bug fix for suffix not initialized.                             
8. 07/06/2009 Shannon Whitley   Twit Connect can optionally appear on the login page.
9. 07/09/2009 Shannon Whitley   Bug fix for missing '=' in login code.
10. 08/25/2009 Shannon Whitley  Changed redirect to javascript. 
                                Fix for cross-domain issues.
                                Redirect page after login is configurable.
                                Comment field check to prevent loss of comments.
************************************************************************************
************************************************************************************
I N S T R U C T I O N S

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
//************************************************************************************
$twc_template = <<<KEEPME
<div id="twc_connect"><p><strong>Twitter Users</strong><br />Enter your personal information in the form or sign in with your Twitter account by clicking the button below.</p></div>
KEEPME;

$twc_login_text = <<<KEEPME2
<div id="twc_connect"><p><strong>Twitter Users</strong><br />Register or Login using your Twitter account by clicking the button below.</p></div><br/></br>
KEEPME2;

$twc_login_text_temp = get_option("twc_login_text");
if(strlen($twc_login_text_temp) > 0)
{
    $twc_login_text = $twc_login_text_temp;
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
$twc_user_login_suffix = get_option("twc_user_login_suffix");
if(empty($twc_user_login_suffix))
{
    $twc_user_login_suffix = '@twitter';
    update_option('twc_user_login_suffix', $twc_user_login_suffix);
}

$twc_btn_choice = get_option("twc_btn_choice");

if($twc_local == 'Y')
{
    $twc_url = WP_PLUGIN_URL.'/twitconnect';
    $twc_page = 'start.php';
    $twc_a = '';
}
else
{
    $twc_url = 'http://mytweeple.com';
    $twc_page = 'twc.aspx';
    $twc_a = 'location.href+"#twcbutton"';
}

$twc_btn_image1 = "http://s3.amazonaws.com/static.whitleymedia/twitconnect.png";
$twc_btn_image2 = "http://s3.amazonaws.com/static.whitleymedia/twitter_button_1_lo.gif";
$twc_btn_image3 = "http://s3.amazonaws.com/static.whitleymedia/twitter_signin.png";

$twc_btn_image = $twc_btn_image1;
if($twc_btn_choice == '2')
{
   $twc_btn_image = $twc_btn_image2;
}
if($twc_btn_choice == '3')
{
   $twc_btn_image = $twc_btn_image3;
}

$twc_redirect = get_option('twc_redirect');

add_action('init', 'twc_init');
add_filter("get_avatar", "twc_get_avatar",10,4);
add_action('comment_form', 'twc_show_twit_connect_button');
add_action("admin_menu", "twc_config_page");
add_action("wp_head", "twc_wp_head");

if(get_option('twc_add_to_login_page') == 'Y')
{
    add_action('login_form', 'twc_login_form');
}

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

function twc_login_form()
{
    twc_show_twit_connect_button(0,'login');
}


function twc_wp_head()
{
    if(is_user_logged_in())
    {
        echo '<script type="text/javascript">if(window.opener){if(window.opener.document.getElementById("twc_connect")){window.opener.twc_bookmark("");window.close();}}</script>';
    }
}


function twc_show_twit_connect_button($id='0',$type='comment')
{
    global $twc_url,$twc_page,$twc_loaded, $twc_template, $twc_login_text, $user_email, $twc_btn_image, $twc_a, $twc_redirect;

    if(is_user_logged_in())
    {
        if($type == 'login')
        {
            echo '<script type="text/javascript">if(window.opener){if(window.opener.document.getElementById("twc_connect")){window.opener.twc_bookmark("");window.close();}}</script>';        
        }
        else
        {
            echo '<p>Your email address is '.'<a name="twcbutton" href="'.get_option('siteurl').'/wp-admin/profile.php">'.$user_email.'</a>.</p>';
        }
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

    if($type == 'login')
    {
        echo $twc_login_text;
    }
    else
    {
        echo $twc_template;
    }

    echo '<script type="text/javascript">
    function twc_bookmark(){
       var url=location.href;
       if(url.indexOf("wp-login.php") > 0)
       {
           url = "'.$twc_redirect.'";
           location.href = url;
       }
       else
       {
           var temp = url.split("#");
           url = temp[0];
           url += "#twcbutton";
           location.href = url;
           location.reload();
       }
    }
    if(document.getElementById("twc_connect"))
    {
        var url = location.href;

        var button = document.createElement("button");
        button.id = "twc_button";
        button.setAttribute("class","btn");
        button.onclick = function(){
            if(document.getElementById("comment"))
            {
                if(document.getElementById("comment").value.length > 0)
                {
                    alert("The comment field must be blank before you Sign in with Twitter.\r\nPlease copy your comment into a text editor and clear the comment field.");
                    return false;
                }
            }
            window.open("'.$twc_url.'/'.$twc_page.'?a="+escape('.$twc_a.')+"&twcver=2&loc="+escape(url), "twcWindow","width=800,height=400,left=150,top=100,scrollbar=no,resize=no");return false;};
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
  global $comment, $twc_user_login_suffix;

  if(is_object($comment))
  {
      $id_or_email = $comment->user_id;
  }

  if (is_object($id_or_email)) {
     $id_or_email = $id_or_email->user_id;
  }

  if (get_usermeta($id_or_email, 'twcid')) {
    $user_info = get_userdata($id_or_email);
    $out = 'http://purl.org/net/spiurl/'. str_replace($twc_user_login_suffix,"",$user_info->user_login);
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
  global $wpdb, $twc_use_twitter_profile, $twc_user_login_suffix;

  $userinfo = explode('|',$pdvUserinfo);
  if(count($userinfo) < 4)
  {
      wp_die("An error occurred while trying to contact Twit Connect.");
  }
  
  //User login
  $user_login_n_suffix = $userinfo[1].$twc_user_login_suffix;

  //Use the url from the Twitter profile.
  $user_url = $userinfo[3];

  if($twc_use_twitter_profile == 'Y')
  {
      //Use the Twitter profile.
      $user_url = 'http://twitter.com/'.$userinfo[1];
  }

  $userdata = array(
    'user_pass' => wp_generate_password(),
    'user_login' => $user_login_n_suffix,
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
      if (!username_exists($user_login_n_suffix))
      {
        $wpuid = wp_insert_user($userdata);
        if($wpuid)
        {
            update_usermeta($wpuid, 'twcid', "$userinfo[0]");
        }
      }
      else
      {
        wp_die('User name '.$user_login_n_suffix.' cannot be added.  It already exists.');
      }
  }
  else
  {
    $user_obj = get_userdata($wpuid);
    
    if($user_obj->display_name != $userinfo[2] || $user_obj->user_url != $userinfo[3])
    {
        $userdata = array(
        'ID' => $wpuid,
        'display_name' => $userinfo[2],
        'user_url' => $userinfo[3],
        );
        wp_update_user( $userdata );
    }
    if($user_obj->user_login != $user_login_n_suffix)
    {
        if (!username_exists($user_login_n_suffix))
        {
            $q = sprintf( "UPDATE %s SET user_login='%s' WHERE ID=%d", 
                $wpdb->users, $user_login_n_suffix, (int) $wpuid );
		    if (false !== $wpdb->query($q)){
		        update_usermeta( $wpuid, 'nickname', $user_login_n_suffix );
		    }
		}
        else
        {
          wp_die('User name '.$user_login_n_suffix.' cannot be added.  It already exists.');
        }
    }
  }
  
  if($wpuid) {
      wp_set_auth_cookie($wpuid, true, false);
      wp_set_current_user($wpuid);
      wp_redirect($_SESSION['oauth_callback']);
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
        global $twc_btn_image1, $twc_btn_image2, $twc_btn_image3, $twc_template, $twc_login_text;

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
            update_option('twc_add_to_login_page', $_POST["twc_add_to_login_page"]);            
            update_option('twc_user_login_suffix', $_POST["twc_user_login_suffix"]);
            update_option('twc_redirect', $_POST["twc_redirect"]);
            $secret_file = dirname(__FILE__).'/secret.php';
            $fh = fopen($secret_file, 'w') or die("Can't open secret file.  Please change the permissions to the files in the Twit Connect plugin directory to allow write access.");
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
        $twc_user_login_suffix = get_option('twc_user_login_suffix');                                
        $twc_redirect = get_option('twc_redirect');                                        
        if(empty($twc_redirect))
        {
            $twc_redirect = 'wp-admin/index.php';
            update_option('twc_redirect',$twc_redirect);
        }
        $twc_template_temp = $twc_template;
        $twc_template = get_option('twc_template');
        if(strlen($twc_template) == 0)
        {
            $twc_template = $twc_template_temp;
        }
        $twc_use_twitter_profile = get_option('twc_use_twitter_profile');
        $twc_use_twitter_profile = $twc_use_twitter_profile == 'Y' ?
    	"checked='true'" : "";
    	
        $twc_add_to_login_page = get_option('twc_add_to_login_page');
        $twc_add_to_login_page = $twc_add_to_login_page == 'Y' ?
    	"checked='true'" : "";

        $twc_local = $twc_local == 'Y' ?
	    "checked='true'" : "";
		
        $btn1 = $twc_btn_choice == '1' ?
            "checked='true'" : "";
        $btn2 = $twc_btn_choice == '2' ?
            "checked='true'" : "";
        $btn3 = $twc_btn_choice == '3' ?
            "checked='true'" : "";			

?>
    <h3>Twit Connect Configuration</h3>
    <form action='' method='post' id='twc_conf'>
      <table cellspacing="20" width="60%">
        <tr>
        <td valign="top">Self-Hosted oAuth</td>
        <td>
          <input type='checkbox' name='twc_local' value='Y' 
            <?php echo $twc_local ?>/>  Self-Hosted oAuth requires <strong>PHP 5</strong>.
            <br/><small>Check this box to use your own Consumer Key and Consumer Secret.</small>
            <br/><small>For this option, you must register a new application at <a href="http://twitter.com/oauth/">Twitter.com</a></small>
            <br/><small>Help in filling out the registration can be found on the <a href="http://www.voiceoftech.com/swhitley/?page_id=706">Twit Connect</a> page.</small>
            
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
          <td width="20%" valign="top">Twitter Login Suffix</td>
          <td>
            <input type='text' name='twc_user_login_suffix' value='<?php echo $twc_user_login_suffix ?>' size="20" /> [Once set, do not change.]
                 <br/><small>
                  (Recommended) Add a suffix to all Twitter logins to keep them separate<br/>from other logins.
                  <br/><br/>Example: Enter <strong>@twitter</strong> into the box above.  The next Twitter account<br/>
                  created on your blog will be {user name}@twitter.
                </small>
          </td>
        </tr>
        <tr>
        <td valign="top">Select a Button</td>
        <td>
          <input type='radio' name='twc_btn_choice' value='1' 
            <?php echo $btn1 ?>/> <img src="<? echo $twc_btn_image1 ?>" /><br/><br/>
          <input type='radio' name='twc_btn_choice' value='2' 
            <?php echo $btn2 ?>/> <img src="<? echo $twc_btn_image2 ?>" /><br/><br/>
          <input type='radio' name='twc_btn_choice' value='3' 
            <?php echo $btn3 ?>/> <img src="<? echo $twc_btn_image3 ?>" />
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
   <tr>
          <td valign="top">Add to Login Page</td>
          <td>
          <input type='checkbox' name='twc_add_to_login_page' value='Y' 
            <?php echo $twc_add_to_login_page ?>/>
            <br/><small>Check this box if you would like the Twit Connect button to appear on the WordPress login page.</small>
          </td>
        </tr>
   <tr>
          <td valign="top">Login Text</td>
          <td>
            <textarea name='twc_login_text' rows="5" cols="50"><?php echo $twc_login_text; ?></textarea>
            <br/>
            <small>The text that appears above the Twit Connect button on the login page.</small>
          </td>
        </tr>
   <tr>
          <td valign="top">Redirect After Login</td>
          <td>
            <input type='text' name='twc_redirect' value='<?php echo $twc_redirect ?>' size="50" />
            <br/>
            <small>The user will be taken to this address after a successful login.  This is only applied to the Login Page.</small>
          </td>
        </tr>
        
      </table>
      <p class="submit">
        <input class="button-primary" type='submit' name='twc_save' value='Save Settings' />
      </p>
    </form>
<?php
			
}

?>