=== Plugin Name ===
Contributors: swhitley
Tags: Twitter, comments, login, single signon, avatar, claim
Requires at least: 2.7.0
Tested up to: 2.7.1
Stable tag: .9

Integrate Twitter and Wordpress.  Provides single-signon and avatars.


== Installation ==

1. Upload `twitconnect.php` to the `/wp-content/plugins/` directory.
1. Place `<?php if(function_exists('twit_connect')){twit_connect();} ?>` in your comment template or rely on the default `<?php do_action('comment_form', $post->ID); ?>` code.
1. To change the text or layout of the Button Template, copy the file `config-sample.php` to `config.php` and modify the text in that file.
1. Activate the plugin through the 'Plugins' menu in WordPress.

