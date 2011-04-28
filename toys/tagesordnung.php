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

function parse_top($accum,$node) {
  switch (get_class($node)) {
  case "DOMText":
    if ($accum['column']==4)
      $accum['betreff'].=trim($node->nodeValue);
    break;

  case "DOMElement":
    if ($node->tagName=='tr') {

      // Neue Zeile hat angefangen, Akkumulator initialisieren

      $accum=array();
      $accum['column']=0;
      $accum['betreff']='';
    }

    if ($node->tagName=='td')

      // Neue Spalte

      $accum['column']++;

    if ($accum['column']==1) {

      // 1. Spalte: Top-Link

      if ($node->hasAttribute('href')) {
	if (preg_match('|^http://www.svmr.de/bi/to010.asp|',$node->getAttribute('href')))
	  $accum['top_link']=getqueryasarray($node->getAttribute('href'));
	else
	  throw new Exception("Unerwarteter Link in Spalte 1 (TOP-Link)");
      }
    }
    if ($accum['column']==6) {

      // 6. Spalte: Vo-Link

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
  $a=map_to_children('','parse_top',$node);
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

    // Da es nur eine Tabelle mit dieser Klasse gibt, wird 
    // dieser Block nur einmal aufgerufen

    $a=map_to_children(array(),'find_class_zl1_1_2',$t);
    array_map('outputtop',$a);
  }
}
