<?php
if (!file_exists('bookd.sqlite')) {
  header('Location: setup.php');
}

require_once('inc/files.php');
require_once('inc/functions.php');
require_once('vendor/autoload.php');
require_once('inc/document.php');

if(logged_in() && isset($_GET['del'])) {
  db_delete_document(intval($_GET['del']));
  header('Location: index.php');
}

?>
<!DOCTYPE HTML>
<html>
  <head>
    <title>BookMark'd</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <link href="css/bootstrap.css" rel="stylesheet" media="screen">
    <link href="css/search.css" rel="stylesheet" media="screen">
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
    
    <div class="container">
      <?php
      
      $how_many = 15;
      $page = isset($_GET['p']) ? intval($_GET['p']) : 0;
      
      $results = db_list_documents($how_many, $page);
      $pagination = db_paginate_documents($how_many, $page, 6);
      $num_res = count($results);
      if ($num_res == 0) {
        echo '<p>No bookmarks.</p>';
      } else {
        echo '<ol start="' . ($page * $how_many + 1) . '">';
        
        for($r = 0; $r < $num_res; $r++) {
          $res = $results[$r];
          $doc_content = db_get_content($res['id']);
          $parts = parse_query(ascii_only($res['title']));
          $terms = $parts['terms'];
          ?>
          
          <li>
            <a href="view.php?doc=<?=$res['id']?>" rel="tooltip" title="<?=$res['path']?>"><img src="https://plus.google.com/_/favicon?domain=<?php
            $parsed_url = parse_url($res['path']);
            if (isset($parsed_url['host'])) {
              echo $parsed_url['host'];
            } else {
              echo urlencode($res['path']);
            }
            ?>" style="margin-top: -4px">&nbsp;<?=strip_tags_and_javascript($res['title'])?></a><?php
            if(logged_in()) {
              ?>
                &nbsp;<a href="?del=<?=$res['id']?>" rel="tooltip" title="Delete" class="delete">&times;</a>
              <?php
            }
            ?>
            <p><?=($r<15)?get_syop($doc_content, $terms) : ''?></p>
          </li>
          
          <?php
        }
        
        echo '</ol>';
      }
      
      ?>
      
      <div class="pagination text-center">
        <ul>
          <?php
          if ($page == 0) {
            echo '<li class="disabled"><a href="#">&laquo;</a></li>';
          } else {
            echo '<li><a href="?p=' . ($page - 1) . '">&laquo;</a></li>';
          }
          
          foreach($pagination as $paginator) {
            if ($paginator == -1) {
              echo '<li class="disabled"><a href="#">&hellip;</a></li>';
            } else {
              echo '<li' . (($page == $paginator) ? ' class="active"':'') . '>';
              echo '<a href="?p=' . $paginator . '">' . ($paginator + 1) . '</a></li>';
            }
          }
          
          if ($page < $pagination[count($pagination) - 1]) {
            echo '<li><a href="?p=' . ($page + 1) . '">&raquo;</a></li>';
          } else {
            echo '<li class="disabled"><a href="#">&raquo;</a></li>';
          }
          
          ?>
        </ul>
      </div>
    </div>
    
    <footer id="footer">
      <div class="container">
        <p class="text-center"><a href="index.php">Home</a> &nbsp; <a href="search.php?q=search">Search</a> &nbsp; <a href="admin.php">Admin</a><br>Powered by <a href="http://kmwallio.github.io/BookMarkd">BookMark'd</a>.</p>
      </div>
    </footer>
       
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>  
      $(document).ready(function () {  
        $("[rel=tooltip]").tooltip({'placement':'right'});
        $('.navbar').scrollspy();
      });  
    </script>
  </body>
</html>
