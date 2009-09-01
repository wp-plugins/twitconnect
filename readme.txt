=== Plugin Name ===
Contributors: swhitley
Tags: Twitter, comments, login, single signon, avatar, claim
Requires at least: 2.7.0
Tested up to: 2.8.4
Stable tag: 1.50

Integrate Twitter and Wordpress.  Provides single-signon and avatars.

Changes in Version 1.50

- Bug fixes.
- Javascript redirect. 
- Fix for cross-domain issues.
- Can now specify a redirect page after login.
- Comment field check to prevent loss of comments.



== Installation ==

1. Upload `twitconnect.php` and all included files to the `/wp-content/plugins/` directory.
1. Place `<?php if(function_exists('twit_connect')){twit_connect();} ?>` in your comment template or rely on the default `<?php do_action('comment_form', $post->ID); ?>` code.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Modify plugin options through the `Settings` menu.


== Change Log ==

1.50

08/25/2009 Shannon Whitley

- Bug fixes.
- Javascript redirect. 
- Fix for cross-domain issues.
- Can now specify a redirect page after login.
- Comment field check to prevent loss of comments.


1.11

07/09/2009 Shannon Whitley

- Bug fix for missing '=' in login code.


1.1

07/06/2009 Shannon Whitley

- Twit Connect can optionally appear on the login page.


1.06

06/05/2009 Shannon Whitley   

- Separately identify Twitter accounts using a suffix.
- Check for existing user prior to add.
- Change the user name if changed on Twitter.
- New button image.


1.05

04/24/2009 Shannon Whitley   

- Workaround for removal of oauth_callback.
- Removed the closeme.php page.


1.02

04/21/2009 Shannon Whitley   

- PHP 5 required for Epi.


1.0

04/20/2009 Shannon Whitley   

- Config Page
- Local oAuth Processing
- Button image selection
