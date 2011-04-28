<?

$result= file_get_contents("../cache/marburg/neu/to010.asp.htm");
//echo $result;

$config = array(
           'indent'         => true,
           'output-xhtml'   => true,
           'wrap'           => 200);

$tidy = new tidy;
$tidy->parseString($result, $config, 'utf8'); // XXX charset?
$tidy->cleanRepair();

$doc = new DOMDocument();
$doc->loadHTML($tidy);

function map_to_children($accum,$function,$node) {
  $accum=$function($accum,$node);
  if ($node->childNodes) {
    foreach ($node->childNodes as $c) {
      $accum=map_to_children($accum,$function,$c);
    }
  }
  return $accum;
}

function find_class_zl1_1_2($accum,$node) {
  if (get_class($node)=="DOMElement") {
    if (preg_match('/^zl1[12]$/',$node->getAttribute("class"))) {
      $accum[]=$node;
      return $accum;
    }
  }
  return $accum;
}

function getqueryasarray($url) {
  $x=parse_url($url);
  parse_str($x['query'],$r);
  return $r;
}

function accumtext($accum,$node) {
  switch (get_class($node)) {
  case "DOMText":
    //    echo 'T '.$accum['column'].':'.trim($node->C14N())."\n";
    if ($accum['column']==4)
      $accum['betreff'].=trim($node->nodeValue);
    break;
  case "DOMElement":
    if ($node->tagName=='tr') {
      $accum=array();
      $accum['column']=0;
      $accum['betreff']='';
    }
    if ($node->tagName=='td')
      $accum['column']++;
    if ($accum['column']==1) {
      if ($node->hasAttribute('href')) {
	if (preg_match('|^http://www.svmr.de/bi/to010.asp|',$node->getAttribute('href')))
	  $accum['top_link']=getqueryasarray($node->getAttribute('href'));
	else
	  throw new Exception("Unerwarteter Link in Spalte 4 (TOP-Link)");
      }
    }
    if ($accum['column']==6) {
      if ($node->hasAttribute('href')) 
	if (preg_match('|^http://www.svmr.de/bi/vo020.asp|',$node->getAttribute('href')))
	  $accum['vorlage_link']=getqueryasarray($node->getAttribute('href'));
	else
	  throw new Exception("Unerwarteter Link in Spalte 6 (VO-Link)");

    }
    break;
  }
  return $accum;
}

function outputtop($node) {
  //  var_dump(get_class_methods($node->childNodes->item(0)));
  //  var_dump($node->childNodes->item(0)->C14N());
  $a=map_to_children('','accumtext',$node);
  echo $a['betreff']."\n";
  echo "TOP: ".$a['top_link']['SILFDNR']." - ";
  echo $a['top_link']['TOLFDNR']."\n";
  echo "VO: ".$a['vorlage_link']['VOLFDNR']."\n";
  echo "\n***\n";
}


//$forms=$doc->getElementsByTagName('form');
$tables=$doc->getElementsByTagName('table');

foreach ($tables as $t) {
  if ($t->getAttribute("class")=="tl1") {
    $a=map_to_children(array(),'find_class_zl1_1_2',$t);
    //    var_dump($a);
    array_map('outputtop',$a);
  }
}
