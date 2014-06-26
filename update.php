<?php
require_once('./inc/database.php');
require_once('vendor/autoload.php');

function alter_db() {
  $dbh = connect_db();

  try {
    $dbh->query('ALTER TABLE DocumentTerms ADD termfrequency REAL');
    $q = $dbh->prepare('SELECT term, occurrences, document FROM DocumentTerms ORDER BY document');
    $q->execute();
    $cur_doc = -1;
    $max_count = 0;
    while(($row = $q->fetch(PDO::FETCH_ASSOC)) !== false){
      if ($cur_doc != $row['document']) {
        $m = $dbh->prepare('SELECT occurrences as max_count FROM DocumentTerms WHERE document=:document ORDER BY occurrences DESC LIMIT 1');
        $m->execute(array('document' => $row['document']));
        $result = $m->fetch(PDO::FETCH_ASSOC);
        $max_count = $result['max_count'];
        $cur_doc = $row['document'];
      }
      $u = $dbh->prepare('UPDATE DocumentTerms SET termfrequency=:termfreq WHERE document=:document AND term=:term_id');
      $termFreq = ($max_count > 0) ? ($row['occurrences'] / $max_count) : 0;
      $u->execute(array('termfreq' => $termFreq, 'document' => $row['document'], 'term_id' => $row['term']));
      //echo $row['document'] . ' - ' . $row['term'] . ' - ' . $termFreq . "\n";
    }
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
}
$start = time();
alter_db();

echo 'Finished updating in ' . (time() - $start) . "seconds \n";
