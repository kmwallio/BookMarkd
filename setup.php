<?php
$version = trim(file_get_contents('VERSION'));

if (file_exists('bookd.sqlite')) {
  header('Location: index.php');
  die();
}

require_once('inc/database.php');
require_once('vendor/autoload.php');

function create_db($file) {
  $dp = fopen($file, 'w+');
  fputs($dp, '');
  fclose($dp);
}

function populate_db() {
  $dbh = connect_db();
  
  try {
    $dbh->query('CREATE TABLE "Documents" (title VARCHAR, doclength INTEGER, path TEXT UNIQUE, icon TEXT, made TIMESTAMP, edited TIMESTAMP, views INTEGER, searches INTEGER, id INTEGER PRIMARY KEY AUTOINCREMENT)');
    $dbh->query('CREATE TABLE "DocumentCache" (content TEXT, id INTEGER REFERENCES "Documents" (id))');
    $dbh->query('CREATE TABLE "DocumentQueue" (path TEXT PRIMARY KEY, made TIMESTAMP)');
    $dbh->query('CREATE TABLE "Marklets" (code TEXT PRIMARY KEY, made TIMESTAMP, user VARCHAR REFERENCES "Users" (username))');
    $dbh->query('CREATE TABLE "Tags" (tag VARCHAR UNIQUE, id INTEGER PRIMARY KEY AUTOINCREMENT)');
    $dbh->query('CREATE TABLE "Terms" (term VARCHAR UNIQUE, occurrences INTEGER, id INTEGER PRIMARY KEY AUTOINCREMENT)');
    $dbh->query('CREATE TABLE "TaggedDocuments" (document INTEGER REFERENCES "Documents" (id), tag INTEGER REFERENCES "Tags" (id))');
    $dbh->query('CREATE TABLE "DocumentTerms" (occurrences INTEGER, document INTEGER REFERENCES "Documents" (id), term INTEGER REFERENCES "Terms" (id))');
    $dbh->query('CREATE TABLE "Users" (username VARCHAR PRIMARY KEY, password VARCHAR, cookie_id VARCHAR)');
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
}

function setup_header() {
?>
<!DOCTYPE HTML>
<html>
  <head>
    <title>BookMark'd - Setup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.css" rel="stylesheet" media="screen">
    <style>
      body {
        padding-top: 60px;
      }
    </style>
    <link href="css/responsive.css" rel="stylesheet" media="screen">
  </head>
  <body>
    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="brand" href="#">BookMark'd</a>
        </div>
      </div>
    </div>
<?php
}

function setup_footer() {
?>
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
<?php
}

if (isset($_POST['setup'])) {

  if(empty($_POST['username']) || empty($_POST['password'])) {
    setup_header();
    echo '<div class="container"><h1>Error</h1><p>Please fill in all fields</p></div>';
    setup_footer();
    die();
  }
  
  create_db('bookd.sqlite');
  populate_db();
  db_add_user($_POST['username'], $_POST['password']);
  header('Location: admin.php');

} else {
  setup_header();
  ?>
  
  
  <div class="container">
    <h1>Intro</h1>
    
    <p>BookMark'd is your own personal search engine for all the things <strong>you</strong> find interesting on the web.</p>
    
    <p>After you click install, we're going to setup the database and take you to the admin page.  At the admin page, you'll be able to get your bookmarklet.</p>
    
    <h1>Setup</h1>
    
    <form class="form-horizontal" method="post">
      <div class="control-group">
        <label class="control-label" for="inputUser">Username</label>
        <div class="controls">
          <input type="text" id="inputUser" placeholder="Username" name="username">
        </div>
      </div>
      <div class="control-group">
        <label class="control-label" for="inputPassword">Password</label>
        <div class="controls">
          <input type="password" id="inputPassword" placeholder="Password" name="password">
        </div>
      </div>
      
      <input type="hidden" name="setup" value="go">
      
      <p class=""><input type="submit" class="btn btn-large btn-block btn-success" value="Install"></p>
      
    </form>
    
  </div>
  
  
  <?php
  setup_footer();
}
