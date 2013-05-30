<?php
require_once('document.php');

define('NO_TERM_PENALTY', -0.005);

$connection = false;

function connect_db() {
  global $connection;
  if ($connection) {
    return $connection;
  }
  try {
    $dbh = new PDO('sqlite:bookd.sqlite');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection = $dbh;
    return $dbh;
  }catch(PDOException $e) {
    die('Database Error: '. $e->getMessage());
    die();
  }
}

function db_create_document($document) {
  $db = connect_db();
  try{
    // Create and store the document.
    $q = $db->prepare("INSERT INTO Documents (title, doclength, path, made, edited, views, searches) VALUES (:title, :length, :path, strftime('%s','now'), strftime('%s','now'), 0, 0)");
    
    $path = $document->path;
    $data = array("title" => $document->get_title(true), "length" => $document->length, "path" => $path);
    
    if($q->execute($data)) {
      $doc_id = $db->lastInsertId();
      
      $tags = $document->get_tags();
      
      foreach($tags as $tag) {
        db_add_tag(db_get_tag_id($tag), $doc_id);
      }
      
      $terms = $document->terms;
      
      foreach($terms as $term=>$count) {
        db_add_term(db_get_term_id($term), $doc_id, $count);
      }
      
      $q = $db->prepare('INSERT INTO DocumentCache (content, id) VALUES (:content, :id)');
      $q->execute(array('content' => $document->content, 'id' => $doc_id));
      
    } else {
      die('Error: Could not add document to database');
    }
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
}

function db_get_term_id($term) {
  $db = connect_db();
  try{
    // Check if it's in the database
    $q = $db->prepare("SELECT COUNT(*) FROM Terms WHERE term=:term");
    $q->execute(array("term" => $term));
    
    if($q->fetchColumn() == 1) {
      $q = $db->prepare("SELECT id FROM Terms WHERE term=:term");
      $q->execute(array("term" => $term));
      $result = $q->fetch(PDO::FETCH_ASSOC);
      return $result['id'];
    }
    
    // Create a new Tag.
    $c = $db->prepare("INSERT INTO Terms (term, occurrences) VALUES (:term, 0)");
    if ($c->execute(array("term" => $term))) {
      return db_get_term_id($term);
    } else {
      die('Error: Could not create new term');
    }
  } catch (PDOException $e) {
    die($e->getMessage());
  }
}

function db_add_term($term_id, $doc_id, $count) {
  $db = connect_db();
  try {
    // Get the old Document Term Count
    $doc_term = array("document" => $doc_id, "term" => $term_id);
    $doc_term_count = array("document" => $doc_id, "term" => $term_id, "count" => $count);
    $q = $db->prepare("SELECT COUNT(*) FROM DocumentTerms WHERE document=:document AND term=:term");
    $q->execute($doc_term);
    if($q->fetchColumn() == 0) {
      $prevCount = 0;
      $n = $db->prepare("INSERT INTO DocumentTerms (document, term, occurrences) VALUES (:document, :term, :count)");
      $n->execute($doc_term_count);
    } else {
      $q = $db->prepare("SELECT occurrences FROM DocumentTerms WHERE document=:document AND term=:term");
      $q->execute($doc_term);
      $res = $q->fetch(PDO::FETCH_ASSOC);
      $prevCount = $res['occurrences'];
      // Update the document count
      $u = $db->prepare("UPDATE DocumentTerms SET occurrences=:count WHERE document=:document AND term=:term");
      $u->execute($doc_term_count);
    }
    
    // Grab the current total term count, update the numbers, and yeah...
    $o = $db->prepare("SELECT occurrences FROM Terms WHERE id=:term");
    $o->execute(array("term" => $term_id));
    
    $res = $o->fetch(PDO::FETCH_ASSOC);
    $new_value = intval($res['occurrences']) - $prevCount + $count;
    
    // Update the count
    $uc = $db->prepare("UPDATE Terms SET occurrences=:count WHERE id=:term");
    $uc->execute(array("term" => $term_id, "count" => $new_value));
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
}

function db_get_tag_id($tag) {
  $db = connect_db();
  try {
    // Check if it's in the database
    $q = $db->prepare("SELECT COUNT(*) FROM Tags WHERE tag=:tag");
    $q->execute(array("tag" => $tag));
    
    if($q->fetchColumn() == 1) {
      $q = $db->prepare("SELECT id FROM Tags WHERE tag=:tag");
      $q->execute(array("tag" => $tag));
      $result = $q->fetch(PDO::FETCH_ASSOC);
      return $result['id'];
    }
    
    // Create a new Tag.
    $c = $db->prepare("INSERT INTO Tags (tag) VALUES (:tag)");
    if ($c->execute(array("tag" => $tag))) {
      return db_get_tag_id($tag);
    } else {
      die('Error: Could not create new tag');
    }
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
}

function db_add_tag($tag_id, $doc_id) {
  $db = connect_db();
  try {
    // Check if the tag exists
    $q = $db->prepare("SELECT COUNT(*) FROM TaggedDocuments WHERE document=:document AND tag=:tag");
    $q->execute(array("document" => $doc_id, "tag" => $tag_id));
    
    // If not tagged, tag the document
    if($q->fetchColumn() == 0) {
      $t = $db->prepare("INSERT INTO TaggedDocuments (document, tag) VALUES (:document, :tag)");
      $t->execute(array("document" => $doc_id, "tag" => $tag_id));
    }
  } catch (PDOException $e) {
    echo $e->getMessage();
  }
}

function db_search($words, $tags = array()) {
  $terms = array_unique(Stemmer::stemm(strip_tags_and_javascript($words)));
  
  $db = connect_db();
  
  // Find all the documents that contain the terms.
  $term_counts = array();
  $term_docs = array();
  $docs = array();
  $searches = 1;
  $views = 1;
  foreach($terms as $term) {
    $term_counts[$term] = 0;
    $term_docs[$term] = 0;
    
    $q = $db->prepare('SELECT Documents.id as doc_id, Documents.icon as icon, Documents.doclength as doc_size, Documents.views as views, Documents.title as title, Documents.path as path, Documents.searches as searches, DocumentTerms.occurrences as doc_count FROM Documents, DocumentTerms, Terms WHERE Terms.id=DocumentTerms.term AND DocumentTerms.document=Documents.id AND Terms.term=:term');
    $q->execute(array('term' => $term));
    
    // Get the information
    while(($row = $q->fetch(PDO::FETCH_ASSOC)) !== false){
      $docs[$row['doc_id']]['terms'][$term] = $row['doc_count'];
      $docs[$row['doc_id']]['title'] = $row['title'];
      $docs[$row['doc_id']]['icon'] = $row['icon'];
      $docs[$row['doc_id']]['size'] = $row['doc_size'];
      $docs[$row['doc_id']]['path'] = $row['path'];
      $docs[$row['doc_id']]['id'] = $row['doc_id']; // For when we return the data from this method
      $docs[$row['doc_id']]['searches'] = $row['searches'];
      $searches += $row['searches'];
      $docs[$row['doc_id']]['views'] = $row['views'];
      $views += $row['views'];
      $docs[$row['doc_id']]['max_count'] = $row['doc_size']; // We'll overwrite this soon
      $term_counts[$term] += $row['doc_count'];
      $term_docs[$term]++;
    }
  }
  
  // Get the max count for each document and calculate our localized TF-IDF vector
  $tfidf = array();
  $keys = array();
  $weights = array();
  $doc_count = count($docs);
  foreach($docs as $doc=>$info) {
    $q = $db->prepare('SELECT occurrences as max_count FROM DocumentTerms WHERE document=:document ORDER BY occurrences DESC LIMIT 1');
    $q->execute(array('document' => $doc));
    $result = $q->fetch(PDO::FETCH_ASSOC);
    $docs[$doc]['max_count'] = $result['max_count'];
    foreach($terms as $term) {
      if(isset($info['terms'][$term]) && $term_docs[$term] != 0) {
        $tf = 0.5 + ((0.5 * $info['terms'][$term]) / $docs[$doc]['max_count']);
        $idf = 1 + log($doc_count / ($term_docs[$term]));
        $tfidf[$doc][$term] = $tf * $idf;
      } else {
        $tfidf[$doc][$term] = NO_TERM_PENALTY;
      }
      
    }
    $docs[$doc]['tf-idf'] = $tfidf[$doc];
    $keys[] = $doc;
    // Ideally weighted 60% on content, 40% on popularity
    $weights[] = (.6 * array_sum($tfidf[$doc])) + (.2 * $info['searches'] / $searches) + (.2 * $info['views'] / $views);
  }
  
  array_multisort($weights, SORT_DESC, $keys);
  
  $res = array();
  for($i = 0; $i < $doc_count; $i++) {
    $res[$i]['weight'] = $weights[$i];
    $res[$i]['doc'] = $docs[$keys[$i]];
  }
  return $res;
}

function db_searched($doc_id) {
  $db = connect_db();
  try {
    $q = $db->prepare('UPDATE Documents SET searches=searches+1 WHERE id=:doc');
    $q->execute(array('doc' => $doc_id));
  } catch (PDOException $e) {
    die($e->getMessage());
  }
}

function db_view_document($doc_id) {
  $db = connect_db();
  try {
    $q = $db->prepare('UPDATE Documents SET views=views+1 WHERE id=:doc');
    $q->execute(array('doc' => $doc_id));
    
    $q = $db->prepare('SELECT * FROM Documents WHERE id=:doc');
    $q->execute(array('doc' => $doc_id));
    
    $res = $q->fetch(PDO::FETCH_ASSOC);
    
    // @TODO: Add tags extraction
    $doc = new DBDocument($res['title'], $res['doclength'], $res['path'], array());
    return $doc;
  } catch (PDOException $e) {
    die($e->getMessage());
  }
}

function db_add_user($user, $pass) {
  $username = $user;
  $password = password_hash($pass, PASSWORD_BCRYPT);
  
  $db = connect_db();
  $q = $db->prepare('INSERT INTO Users (username, password) VALUES (:username, :password)');
  try {
    $q->execute(array('username' => $username, 'password' => $password));
    if($q->rowCount() == 1) {
      $code =  '';
      $cp = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
      for($i = 0; $i < 16; $i++) {
        $code .= $cp[rand(0, strlen($cp) - 1)];
      }
      $q = $db->prepare("INSERT INTO Marklets (code, made, user) VALUES (:code, strftime('%s','now'), :username)");
      $q->execute(array('username' => $username, 'code' => $code));
      return true;
    } else {
      return false;
    }
  } catch (PDOException $e) {
    die($e->getMessage());
  }
}

function db_update_marklet($user) {
  $db = connect_db();
  $code =  '';
  $cp = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  for($i = 0; $i < 16; $i++) {
    $code .= $cp[rand(0, strlen($cp) - 1)];
  }
  
  $q = $db->prepare('UPDATE Marklets SET code=:code WHERE user=:user');
  $q->execute(array('user' => $user, 'code' => $code));
}

function db_get_marklet($user) {
  $db = connect_db();
  $q = $db->prepare('SELECT code FROM Marklets WHERE user=:user');
  $q->execute(array('user' => $user));
  
  $res = $q->fetch(PDO::FETCH_ASSOC);
  return $res['code'];
}

function db_login($user, $pass) {
  $db = connect_db();
  $q = $db->prepare('SELECT password FROM Users WHERE username=:user');
  $q->execute(array('user' => $user));
  
  // Check for proper password
  if(password_verify($pass, $q->fetchColumn())) {
    setcookie('user', $user, time() + (3600 * 24 * 30), '/');
    $code =  '';
    $cp = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    for($i = 0; $i < 32; $i++) {
      $code .= $cp[rand(0, strlen($cp) - 1)];
    }
    setcookie('cookie_id', $code, time() + (3600 * 24 * 30), '/');
    $q = $db->prepare('UPDATE Users SET cookie_id=:code WHERE username=:user');
    $q->execute(array('user' => $user, 'code' => $code));
    
    return true;
  } else {
    return false;
  }
}

function db_logged_in($user, $cookie_id) {
  $db = connect_db();
  $q = $db->prepare('SELECT COUNT(*) FROM Users WHERE username=:user AND cookie_id=:cookie_id');
  $q->execute(array('user' => $user, 'cookie_id' => $cookie_id));
  return ($q->fetchColumn() == 1);
}

function db_enqueue($key, $path) {
  $db = connect_db();
  
  // Check for valid key
  $q = $db->prepare('SELECT COUNT(*) FROM Marklets WHERE code=:key');
  $q->execute(array('key' => $key));
  if($q->fetchColumn() == 1) {
    $q = $db->prepare("INSERT INTO DocumentQueue (path, made) VALUES (:path, strftime('%s','now'))");
    $q->execute(array('path' => $path));
  } else {
    die('Invalid Markley, did you <a href="/admin.php" target="_blank">generate a new one</a> recently?');
  }
}

function db_reenqueue($path) {
  $db = connect_db();
  
  $q = $db->prepare("INSERT INTO DocumentQueue (path, made) VALUES (:path, strftime('%s','now'))");
  $q->execute(array('path' => $path));
}

function db_dequeue() {
  $db = connect_db();
  
  $q = $db->prepare('SELECT COUNT(*) FROM DocumentQueue ORDER BY made ASC LIMIT 1');
  $q->execute();
  if ($q->fetchColumn() > 0) {
    $q = $db->prepare('SELECT * FROM DocumentQueue ORDER BY made ASC LIMIT 1');
    $q->execute();
    $r = $q->fetch(PDO::FETCH_ASSOC);
    $q = $db->prepare('DELETE FROM DocumentQueue WHERE path=:path');
    $q->execute(array($r['path']));
    return $r['path'];
  }
  return '';
}

function db_list_queue() {
  $db = connect_db();
  
  $q = $db->prepare('SELECT * FROM DocumentQueue ORDER BY made DESC');
  $q->execute();
  $list = array();
  while (($r = $q->fetch(PDO::FETCH_ASSOC)) !== false) {
    $list[] = array('url' => $r['path'], 'time' => $r['made']);
  }
  return $list;
}

function db_get_content($doc_id) {
  $db = connect_db();
  
  $q = $db->prepare('SELECT content FROM DocumentCache WHERE id=:doc_id');
  if($q->execute(array('doc_id' => $doc_id))){
    return $q->fetchColumn();
  }
  return '';
}

function db_list_documents() {
  $db = connect_db();
  
  $q = $db->prepare('SELECT Documents.id as id, Documents.icon as icon, Documents.doclength as doc_size, Documents.views as views, Documents.title as title, Documents.path as path, Documents.searches as searches FROM Documents ORDER BY id DESC');
  $q->execute();
  
  $res = array();
  while(($r = $q->fetch(PDO::FETCH_ASSOC)) !== false) {
    $res[] = $r;
  }
  
  return $res;
}

function db_delete_document($doc_id) {
  $db = connect_db();
  $rem_array = array('doc_id' => $doc_id);
  
  // Get the terms
  $q = $db->prepare('SELECT DocumentTerms.term as term_id, DocumentTerms.occurrences as dec_term, Terms.occurrences as cur_count FROM DocumentTerms, Terms WHERE document=:doc_id AND Terms.id=DocumentTerms.term');
  $q->execute($rem_array);
  
  // Update counts
  while (($r = $q->fetch(PDO::FETCH_ASSOC))) {
    $new_value = $r['cur_count'] - $r['dec_term'];
    $u = $db->prepare('UPDATE Terms SET occurrences=:new_value WHERE id=:term');
    $u->execute(array('new_value' => $new_value, 'term' => $r['term_id']));
  }
  
  // Remove the document
  $rm = $db->prepare('DELETE FROM Documents WHERE id=:doc_id');
  $rm->execute($rem_array);
  $rm = $db->prepare('DELETE FROM DocumentTerms WHERE document=:doc_id');
  $rm->execute($rem_array);
  $rm = $db->prepare('DELETE FROM TaggedDocuments WHERE document=:doc_id');
  $rm->execute($rem_array);
  $rm = $db->prepare('DELETE FROM DocumentCache WHERE id=:doc_id');
  $rm->execute($rem_array);
}
