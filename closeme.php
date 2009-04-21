<html>
<head>
<title>Twit Connect</title>
    <script type="text/javascript">
    window.onload = function(){closeMe();};

function closeMe()
{
          if(window.opener)
          {
              window.opener.location.href = "<?php echo str_replace('oauth_token_replacement',$_GET['oauth_token'],$_GET['a']) ?>"; 
              window.close();                    
          }

}
    </script>
<style type="text/css">
body {font-family:Arial}
</style>
</head>
<body>
<p>This window should close on its own and refresh the blog page.</p>
<p>You can also try clicking <a href="javascript:void(0)" onclick="closeMe()">this link</a>.</p>
</body>
</html>