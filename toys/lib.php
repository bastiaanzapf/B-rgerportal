<?

function map_to_children(&$accum,$function,$node,$params) {
  $function($accum,$node,$params);
  if ($node->childNodes) {
    foreach ($node->childNodes as $c) {
      map_to_children($accum,$function,$c,$params);
    }
  }
}

function find_domelement_class_regex($accum,$node,$params) {
  if (get_class($node)=="DOMElement") {
    if (preg_match($params['regex'],$node->getAttribute("class"))) {
      $accum[]=$node;
      return $accum;
    }
  }
  return $accum;
}

function parse_table(&$accum,$node,$params) {
  $column=&$accum['column'];
  $line=&$accum['line'];
  if (!isset($line))
    $line=0;
  if (!isset($column))
    $column=0;
  if (get_class($node)=='DOMElement') {
    if ($node->tagName=='tr') {
      $column=0;
      $line++;
    }
    if ($node->tagName=='td') {
      $column++;
    }
  }

  if (isset($params[$column])) 
    $params[$column](&$accum,$node,array('line'=>$line,'column'=>$column,
					 'baseurl'=>$params['baseurl']));
}

function getqueryasarray($url) {
  $x=parse_url($url);
  parse_str($x['query'],$r);
  return $r;
}

function top_link($accum,$node,$params) {
  $line=$accum['line'];
  if (get_class($node)=='DOMElement') {
    if ($node->hasAttribute('href')) {
      // http://www.svmr.de/bi/
      if (preg_match('|^to010.asp|',$node->getAttribute('href'))) {
	
	$accum['to'][$line]['detailsurl']=$params['baseurl'].$node->getAttribute('href');
	$accum['to'][$line]['topnr']=preg_replace('/^.*([0-9]+).*$/','$1',$node->nodeValue);
      } else
	throw new Exception("Unerwarteter Link in Spalte 1 (TOP-Link): ".$node->C14N());
    }
  }
  return $accum;
}

function betreff($accum,$node,$params) {
  $line=$accum['line'];
  if (!isset($accum['to'][$line]['betreff']))
    $accum['to'][$line]['betreff']='';
  if (get_class($node)=='DOMText') {
    $accum['to'][$line]['betreff'].=trim($node->nodeValue);
  }
  return $accum;
}

function vo_link($accum,$node,$params) {
  $line=$accum['line'];
  if (get_class($node)=='DOMElement') {
    if ($node->hasAttribute('href')) 
      // http://www.svmr.de/bi/
      if (preg_match('|^vo020.asp|',$node->getAttribute('href'))) {
	$accum['to'][$line]['vokey']=$node->nodeValue;
	$accum['to'][$line]['vourl']=$params['baseurl'].$node->getAttribute('href');
      } else
	throw new Exception("Unerwarteter Link in Spalte 6 (VO-Link)");
  }
  return $accum;
}


function parse_to($doc,$baseurl) {
  $tables=$doc->getElementsByTagName('table');
  foreach ($tables as $t) {    
    if ($t->getAttribute("class")=="tl1") {
      
      $config=array('baseurl'=>$baseurl,
		    1 => 'top_link',
		    4 => 'betreff',
		    6 => 'vo_link');
      $a=array();
      map_to_children($a,'parse_table',$t,$config);
      return $a;
    }
  }
}

function insert_top($pid,$nr,$betreff,$detailsurl,$vokey,$vourl) {
  if (!isset($GLOBALS['insert_top_prepared'])) {
    $GLOBALS['insert_top_prepared']=true;
    pg_prepare('insert_r_top','INSERT INTO referenz (typ,parent,position,url,original_description) VALUES (\'tagesordnungspunkt\',$1,$2,$3,$4)');
    pg_prepare('insert_r_vo','INSERT INTO referenz (typ,parent,position,original_key,url) VALUES (\'vorlage\',$1,$2,$3,$4)');
  }
  pg_execute('insert_r_top',array($pid,$nr,$detailsurl,$betreff));  
  if ($vokey || $vourl) {
    pg_execute('insert_r_vo',array($pid,$nr,$vokey,$vourl));  
  }
}

function retrieve_instance($rid) {
  pg_prepare('retrieve_reference',
	     'SELECT * FROM referenz WHERE referenz_id=$1');
  $dbresult=pg_execute('retrieve_reference',array($rid));
  $ref=pg_fetch_assoc($dbresult);

  if (empty($ref))
    throw new Exception("Kann Referenz $id nicht in Datenbank finden");

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $ref['url']);
  if (isset($ref['post'])) {
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS,$ref['post']);
  }
  curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
  $result= curl_exec ($curl);
  //  var_dump($result);die();
  curl_close ($curl);

  //echo $result;

  $config = array(
		  'indent'         => true,
		  'output-xhtml'   => true,
		  'wrap'           => 200);

  $tidy = new tidy;
  $tidy->parseString($result, $config, 'utf8'); // XXX charset?
  $tidy->cleanRepair();

  /*  $doc = new DOMDocument();
   $doc->loadHTML($tidy);*/

  pg_query('BEGIN');
  $oid=pg_lo_create();
  $handle=pg_lo_open($oid,'w');
  pg_lo_write($handle,$tidy);
  pg_lo_close($handle);

  pg_prepare('insert_instance',
	     'INSERT INTO instanz (referenz_id,retrieved,content) '.
	     'VALUES ($1,NOW(),$2)');
  pg_execute('insert_instance',array($rid,$oid));
  pg_query('COMMIT');
}

function parse_to_instance($iid) {
  pg_query('BEGIN');
  pg_prepare('retrieve_instance',
	     'SELECT * FROM referenz JOIN instanz USING (referenz_id) WHERE instanz_id=$1');
  $dbresult=pg_execute('retrieve_instance',array($iid));  
  $ref=pg_fetch_array($dbresult);

  $x=parse_url($ref['url']);
  $baseurl=$x['scheme'].'://'.$x['host'].preg_replace('|/[^/]*$|','/',$x['path']);

  $handle=pg_lo_open($ref['content'],'r');

  $htmldoc=pg_lo_read($handle,1e7); // XXX

  pg_lo_close($handle);
  pg_query('COMMIT'); // ??

  $doc = new DOMDocument();
  $doc->loadHTML($htmldoc);

  $to=parse_to($doc,$baseurl);

  $to=$to['to'];

  $i=1;
  foreach ($to as $top) {
    if (isset($top['detailsurl'])) {
      if (empty($top['vokey'])) {
	$top['vokey']=null;
      }
      if (empty($top['vourl'])) {
	$top['vourl']=null;
      }
      insert_top($ref['referenz_id'],$top['topnr'],$top['betreff'],$top['detailsurl'],$top['vokey'],$top['vourl']);
    }
  }
}

require('database.php');

//retrieve_instance(1);

parse_to_instance(8);