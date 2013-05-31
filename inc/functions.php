<?php

define('SENTENCE_SIZE', 18);
// Average sentence length is ~17 words http://strainindex.wordpress.com/2008/07/28/the-average-sentence-length/

function parse_query($q) {
	$parts = preg_split('/(tag|tagged|tags):/', $q);
	$terms = (isset($parts[0])) ? preg_split('/[\s,]+/', $parts[0], -1, PREG_SPLIT_NO_EMPTY) : array();
	$tags = (isset($parts[1])) ? preg_split('/[\s,]+/', $parts[1], -1, PREG_SPLIT_NO_EMPTY) : array();
	return array('terms' => $terms, 'tags' => $tags);
}

function synop($contents, $terms) {
  $content = strip_tags_and_javascript(strip_markdown($contents));
  $contents = preg_split('/\s+/', strtolower($content));
  $contentsUsed = preg_split('/\s+/', $content);
  
  $keys = array();
  foreach($terms as $term) {
    $keys = array_merge($keys, array_keys($contents, strtolower($term)));
  }
  sort($keys);
  
  $klim = count($keys);
  if ($klim == 0) {
    return implode(' ', array_slice($contentsUsed, 0, 50));
  } else {
    $result = '';
    $lim = $klim > 5 ? 5 : $klim;
    $dlen = count($contents);
    $u = 0;
    $s = 0;
    $e = 0;
    $divider = intval(SENTENCE_SIZE / 2);
    for($i = 0; $i < $lim && $u < $klim && $e < $dlen; $i++) {
      $mid = $keys[$u];
      $ne = $mid + $divider;
      if ($mid - $divider < $s) {
        $ne = $mid + $divider - ($mid - $divider - $s);
      } else {
        $s = $mid - $divider;
      }
      
      if ($ne > $dlen) {
        $ne = $dlen - 1;
      }
      
      if ($s != $e) {
        $result .= ' ...';
      }

      $result .= implode(' ', array_slice($contentsUsed, $s, ($ne - $s)));
      
      $e = $ne + 1;
      $s = $e;
      while($u < $klim && $keys[$u] < $e) {
        $u++;
      }
    }
    if ($e < $dlen) {
      $result .= '...';
    }
    return $result;
  }
  
}

function highlight($text, $terms) {
  $terms = array_unique($terms);
  $lengths = $terms;
  array_walk($lengths, "strlen2");
  array_multisort($lengths, SORT_DESC, $terms);
  foreach($terms as $term) {
    if (strtolower($term) != "b"){
      $text = preg_replace("/($term)/i", '<b>\\1</b>', $text);
    }
  }
  return $text;
}

function get_syop($content, $terms) {
  return highlight(htmlentities(synop($content, $terms)),$terms);
}

function strip_tags_and_javascript($text) {
  $result = '';
  if(!is_array($text)){
    $markdown_chars = array('*','[',']','(',')');
    $text = str_replace($markdown_chars, ' ', $text);
    $remove = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript 
                    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
                    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags 
                    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA 
    );
    
    $result = preg_replace($remove, ' ', $text);
    
    $result = preg_replace('/\s+/', ' ', $result);
  } else {
    $result = array();
    foreach($text as $word) {
      $result[] = strip_tags_and_javascript($word);
    }
  }
  return $result;
}

// Doesn't strip all markdown.
function strip_markdown($text) {
  // Images and urls
  $text = preg_replace('/!\[(.*?)\]\((.*?)\)/', '\\1', $text);
  $text = preg_replace('/!?\[(.*?)\]\((.*?)\)/', '\\1', $text);
  
  // Footnotes
  $text = preg_replace('/\[\^(.*?)\]:?/', '', $text);
  
  // Bold and Italics
  $text = preg_replace('/\*{1,3}([^\*]*?)\*{1,3}/', '\\1', $text);
  $text = preg_replace('/_{1,3}([^_]*?)_{1,3}/', '\\1', $text);
  
  // Lists and block quotes
  $text = preg_replace('/^\s*[\*\s]+(.*?)/', '\\1', $text);
  $text = preg_replace('/^\s*[\>\s]*(.*?)/', '\\1', $text);
  $text = preg_replace('/^\s*[\*\s]+(.*?)/', '\\1', $text);
  $text = preg_replace('/^\s*[\>\s]*(.*?)/', '\\1', $text);
  
  // Headings
  $text = preg_replace('/#/', '', $text);
  
  // Comments
  $text = preg_replace('/<!--(.*?)-->/', '\\1', $text);
  
  // Extra white space
  $text = preg_replace('/\s+/', ' ', $text);
  
  return $text;
}

function logged_in() {
  if (isset($_COOKIE['user']) && isset($_COOKIE['cookie_id'])) {
    return db_logged_in($_COOKIE['user'], $_COOKIE['cookie_id']);
  } else {
    return false;
  }
}

function time_ago($time) {
  $passed = time() - $time;
  
  // Days
  $days = intval($passed / (24 * 3600));
  $passed = $passed % (24 * 3600);
  
  // Hours
  $hours = intval($passed / 3600);
  $passed = $passed % 3600;
  
  // Minutes
  $minutes = intval($passed / 60);
  $passed = $passed % 60;
  
  // Seconds
  $seconds = $passed;
  
  $result = '';
  if ($days != 0) {
    $result .= $days . ' days, ';
  }
  
  if ($hours != 0) {
    $result .= $hours .' hrs, ';
  }
  
  if ($minutes != 0) {
    $result .= $minutes .' min, ';
  }
  
  if ($seconds != 0) {
    $result .= $seconds .' sec';
  }
  
  return $result;
}

function get_url_contents($url) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_USERAGENT, 'MarkdBot');
  $contents = curl_exec($curl);
  curl_close($curl);
  return $contents;
}

function ascii_only($text) {
  $result = str_replace("'", '', $text);
  $result = preg_replace('/[^\w]/', ' ', $result);
  $result = preg_replace('/\s+/', ' ', $result);
  return $result;
}

function strlen2($str, $option) {
  return strlen($str);
}
