<?php
require_once('inc/functions.php');
require_once('inc/database.php');
require_once('vendor/autoload.php');

if (!isset($_GET['url'])) {
  header('Location: index.php');
}

if (!isset($_GET['key'])) {
  header('Location: ' . $_GET['url']);
}

try {
  db_enqueue($_GET['key'], $_GET['url']);
} catch (Exception $e) {
}

header('Location: ' . $_GET['url']);
