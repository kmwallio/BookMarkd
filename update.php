<?php
require_once('./inc/database.php');
require_once('vendor/autoload.php');

function alter_db() {
  $dbh = connect_db();

  try {
    $dbh->query('CREATE INDEX Terms_term ON Terms (term);');
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
}
$start = time();
alter_db();

echo 'Finished updating in ' . (time() - $start) . "seconds \n";
