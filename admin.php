<?php
if (!file_exists('bookd.sqlite')) {
  header('Location: setup.php');
}
require_once('inc/functions.php');
require_once('inc/database.php');
require_once('vendor/autoload.php');
date_default_timezone_set('UTC');

if (isset($_POST['login'])) {
  db_login($_POST['username'], $_POST['password']);
  header('Location: admin.php');
}

if (isset($_GET['new_code'])) {
  db_update_marklet($_COOKIE['user']);
  header('Location: admin.php');
}

?><!DOCTYPE HTML>
<html>
  <head>
    <title>BookMark'd - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.css" rel="stylesheet" media="screen">
    <style>
      body {
        padding-top: 60px;
      }
    </style>
    <link href="css/responsive.css" rel="stylesheet" media="screen">
    <link href="css/login.css" rel="stylesheet" media="screen">
  </head>
  <body>
    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="index.php">BookMark'd</a>
          <div class="nav-collapse collapse">
            <form method="get" action="search.php" class="navbar-search pull-right">
              <input type="text" class="search-query" name="q" placeholder="Search">
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <?php
    if (!logged_in()) {
      ?>
      <div class="container">
        <form class="form-signin" method="post">
          <h2 class="form-signin-heading">Please sign in</h2>
          <input type="text" class="input-block-level" placeholder="Username" name="username">
          <input type="password" class="input-block-level" placeholder="Password" name="password">
          <input type="hidden" name="login" value="yes">
          <button class="btn btn-large btn-primary" type="submit">Sign in</button>
        </form>
      </div>
      <?php
      die();
    }
    ?>
    
    <div class="container">
    
      <div class="row">
        <div class="span9">
          <h1>Admin</h1>
          <div class="row">
            
            <div class="span4">
              <h3>Bookmarklet Code</h3>
              <?php
                $rep = array('{{key}}', '{{domain}}');
                $val = array(db_get_marklet($_COOKIE['user']),$_SERVER['HTTP_HOST']);
                $bookmartlet = str_replace($rep, $val, preg_split('/[\r\n]+/', file_get_contents('js/marklet.js'))[1]);
              ?>
              <!-- <pre>javascript:<?=$bookmartlet?></pre> -->
              Drag This to your Bookmark Bar: <a href="javascript:<?=$bookmartlet?>">Mark It!</a>.<br>
              <a href="?new_code=y">Generate new bookmarklet?</a>
            </div>
            
            <div class="span5">
              <h3>Queued</h3>
              <ul>
                <?php
                  $show_cron = false;
                  $queued = db_list_queue();
                  foreach($queued as $site) {
                    echo '<li><a href="'.$site['url'].'">'.$site['url'].'</a> <!-- ('.date('Y-m-d', $site['time']).') --> (' . time_ago($site['time']) . ' ago)</li>';
                    $show_cron = ($show_cron || (time() - $site['time']) > 3600); // Warn the user if the queue has items older than an hour.
                  }
                ?>
              </ul>
            </div>
          </div>
          
          <div class="row">
            <?php
            if ($show_cron){
            ?>
            <div class="span4">
              <h3>Cron</h3>
              <p>It doesn't look like cron has been ran.  Please setup a cronjob to run <tt><?=$_SERVER['DOCUMENT_ROOT']?>/cron.php</tt> or a web cron to run <tt>http://<?=$_SERVER['HTTP_HOST']?>/cron.php</tt></p>
            </div>
            <?php
            }
            ?>
          </div>
          
        </div>
      </div>
    
    </div>
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>  
      $(document).ready(function () {  
        $("[rel=tooltip]").tooltip();
        $('.navbar').scrollspy();
      });  
    </script>
  </body>
</html>
