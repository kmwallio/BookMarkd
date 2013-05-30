<?php
require_once('inc/document.php');
require_once('inc/database.php');

if(!isset($_GET['doc'])) {
  header('Location: index.php');
  die();
}

$doc = intval($_GET['doc']);

if(isset($_GET['searched'])) {
  // Increase count
  db_searched($doc);
}

$document = db_view_document($doc);

header('Location: ' . $document->path);
