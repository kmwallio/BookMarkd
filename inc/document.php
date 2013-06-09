<?php
require_once('vendor/autoload.php');
require_once('stemmer.php');
require_once('functions.php');

class Document {
  public $document = '';
  public $content = '';
  public $terms = array();
  public $front_matter = '';
  public $front_matter_extract = array();
  public $length = 0;
  public $path = null;
  
  public function __construct($nDocument, $extract_terms = true, $extract_front_matter = true) {
    if (strlen($nDocument) < 255 && file_exists($nDocument)) {
      $this->path = $nDocument;
      $this->document = file_get_contents($nDocument);
    } else {
      $this->document = $nDocument;
    }
    // Create a text only version.
    $this->content = strtolower(html_entity_decode(strip_tags_and_javascript($this->document)));
    // Remove punctuation
    // @TODO: Add possible fix for contractions?
    // @TODO: Add fix for foreign languages?
    $this->content = preg_replace('/[^a-zA-Z0-9\s]/i', '', $this->content);
    if($extract_terms){
      $this->extract_terms();
    }
    if($extract_front_matter){
      $this->extract_front_matter();
    }
  }
  
  public function extract_terms(){
    // Extract terms and counts
    $words = preg_split('/\s+/', $this->content, -1, PREG_SPLIT_NO_EMPTY);
    $nTerms = array();
    foreach($words as $word) {
      if (!isset($nTerms[$word])) {
        $nTerms[$word] = 0;
      }
      $nTerms[$word]++;
    }
    
    $sTermCount = array(); // Now we find the stems of the terms.
    $oTerms = array_keys($nTerms);
    $limit = count($oTerms);
    $stemmer = new Stemmer();
    $this->length = 0;
    for($i = 0; $i < $limit; $i++) {
      $stem = $stemmer->stem($oTerms[$i]);
      if(!empty($stem)){
        if(!isset($sTermCount[$stem])) {
          $sTermCount[$stem] = 0;
        }
        $sTermCount[$stem] += $nTerms[$oTerms[$i]];
        $this->length += $nTerms[$oTerms[$i]];
      }
    }
    $this->terms = $sTermCount;
  }
  
  public function extract_front_matter() {
    preg_match("/<title([^<]*)?>([^<]*)<\/title>/im", $this->document, $match_title);
    $title = '';
    if (count($match_title) >= 1) {
      $title = $match_title[count($match_title) - 1];
    }
    $this->front_matter_extract['title'] = $title;
  }
  
  public function get_tags() {
    $tags = array();
    $possible = array('category', 'categories', 'tags');
    foreach($possible as $examine){
      if(isset($this->front_matter_extract[$examine])) {
        if(is_array($this->front_matter_extract[$examine])) {
          foreach($this->front_matter_extract[$examine] as $tag) {
            $tags[] = $tag;
          }
        } else {
          $tags[] = $this->front_matter_extract[$examine];
        }
      }
    }
    
    return array_unique($tags);
  }
  
  function get_document() {
    return $this->document;
  }
  
  public function make_title() {
    $title = '';
    if (count($this->terms) > 0) {
      $uTerm = array_keys($this->terms);
      $l = count($uTerm);
      for($i = 1; $i < 5; $i++) {
        $title .= $uTerm[rand(0, $l - 1)] . ' ';
      }
    } else {
      $title = rand(1000, 9999);
    }
    return trim($title);
  }
  
  public function get_title($no_blank = false) {
    if(isset($this->front_matter_extract['title'])) {
      return $this->front_matter_extract['title'];
    }
    return ($no_blank) ? $this->make_title() : '';
  }
}

class DBDocument {
  public $doc = null;
  public $tags = null;
  public $path = '';
  public $title = '';
  public $length = 0;
  
  public function __construct($title, $length, $path, $tags) {
    $this->doc = new Document($path);
    $this->length = $length;
    $this->path = $path;
    $this->tags = $tags;
    $this->title = $title;
  }
  
  public function get_content() {
    return $this->doc->document;
  }
  
  // This will store the new content, but not parse it.
  public function store_content($nText) {
    $this->doc = new Document($nText, false, false);
  }
  
  public function parse_content() {
    $this->doc->extract_terms();
    $this->doc->extract_front_matter();
    $this->length = $this->doc->length;
  }
  
  public function update_content($nText) {
    $this->doc = new Document($nText);
  }
  
  public function get_document() {
    return $this->doc->document;
  }
  
  public function get_title($no_blank = false) {
    $this->doc->extract_front_matter();
    if (trim($this->doc->get_title($no_blank)) != '') {
      return $this->doc->get_title($no_blank);
    }
    return $this->title;
  }
  
  public function get_tags() {
    return array_unique(array_merge($this->tags, $this->doc->get_tags()));
  }
}
