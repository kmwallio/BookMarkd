#!/usr/bin/env php
<?php
$working_dir = str_replace('cron.php', '', __FILE__);
chdir($working_dir);

require_once('inc/functions.php');
require_once('inc/database.php');
require_once('inc/document.php');
require_once('vendor/autoload.php');
require_once('inc/files.php');

header('Content-type: text/plain');

$site = db_dequeue();
$time = time();

while (!empty($site) && time() - $time < 20) { // Want to stay under the 30 limit for some servers?
  $document = new Document(get_url_contents($site));
  $document->path  = $site;
  db_create_document($document);
  echo 'Added: ' . $site . "\n";
  $site = db_dequeue();
}

// In case we didn't have time to insert the URL
if (!empty($site) && time() - $time < 20) {
  db_reenqueue($site);
}
