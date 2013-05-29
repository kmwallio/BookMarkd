<?php
require_once('database.php');
require_once('vendor/autoload.php');
use GitElephant\Repository;

function n_file_exists($file_name) {
  $db = connect_db();
  $q = $db->prepare('SELECT title FROM Documents WHERE path=:path');
  $q->execute(array($file_name));
  if (count($q->fetchAll()) >= 1) {
    return true;
  } else {
    return false;
  }
}

function create_file($document) {
  db_create_document($document);
}

function n_valid_file($file_name) {
  $valid_extensions = array('txt', 'md', 'markdown', 'fountain', 'textile', 'text', 'spmd');
  $extension = explode('.', $file_name);
  $extension = $extension[count($extension) - 1];
  return in_array($extension, $valid_extensions);
}

function make_file_name($title) {
  $file_name = './notes/' . date("Y-m-d") . '-';
  $nTitle = preg_replace('/\s+/', '-', $title);
  $text = array('+', '=');
  $rep = array('plus', 'equals');
  $nTitle = str_replace($text, $rep, $nTitle);
  $nTitle = preg_replace('/[^\w-]/', '', $nTitle);
  $file_name .= $nTitle . '-' . date("H-i-s") . '.markdown';
  return $file_name;
}