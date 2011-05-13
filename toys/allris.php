<?

/**
 * Diese Datei enthält Funktionen, die spezialisiert sind auf das 
 * Auslesen von Informationen aus "Allris"-Ratsinformationssystemen
 */

function top_link($accum,$node,$params) {
  $line=$accum['line'];
  if (get_class($node)=='DOMElement') {
    if ($node->hasAttribute('href')) {
      if (preg_match('|^to010.asp|',$node->getAttribute('href'))) {
	
	$accum['parsed-data'][$line]['detailsurl']=$params['baseurl'].$node->getAttribute('href');
	$accum['parsed-data'][$line]['topnr']=preg_replace('/^.*([0-9]+).*$/','$1',$node->nodeValue);
      } else
	throw new Exception("Unerwarteter Link in Spalte 1 (TOP-Link): ".$node->C14N());
    }
  }
  return $accum;
}

function to_link($accum,$node,$params) {
  $line=$accum['line'];
  if (get_class($node)=='DOMElement') {
    if ($node->tagName=='input') {
      if ($node->getAttribute('name')=='SILFDNR') {
	$accum['parsed-data'][$line]['tourl']=$params['baseurl'].'to010.asp';
	$accum['parsed-data'][$line]['post']='SILFDNR='.$node->getAttribute('value').'&options=8';
      } 
    }

  }
  return $accum;
}

function betreff($accum,$node,$params) {
  $line=$accum['line'];
  if (!isset($accum['parsed-data'][$line]['betreff']))
    $accum['parsed-data'][$line]['betreff']='';
  if (get_class($node)=='DOMText') {
    $accum['parsed-data'][$line]['betreff'].=trim($node->nodeValue);
  }
  if ($node->tagName=='a') {
    if ($link=preg_match('/^to020.asp/',$node->getAttribute('href'))) {
      $accum['parsed-data'][$line]['betreffurl']=$node->getAttribute('href');
    } else {
      throw new UserException("Unexpected Link found: ".$node->getAttribute('href'));
    }     
  }
  return $accum;
}

function vo_link($accum,$node,$params) {
  $line=$accum['line'];
  if (get_class($node)=='DOMElement') {
    if ($node->hasAttribute('href')) 
      if (preg_match('|^vo020.asp|',$node->getAttribute('href'))) {
	$accum['parsed-data'][$line]['vokey']=$node->nodeValue;
	$accum['parsed-data'][$line]['vourl']=$params['baseurl'].$node->getAttribute('href');
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

function datum($accum,$node,$params) {
  $line=$accum['line'];
  if (get_class($node)=='DOMText') {
    if (preg_match('/[0-9]+/',$node->nodeValue)) {
      $tag=preg_replace('/[^0-9]/','',$node->nodeValue);
      $accum['parsed-data'][$line]['datum']=$params['jahr'].'-'.$params['monat'].'-'.sprintf("%02s",$tag);
    }
  }
  return $accum;
}


function parse_sk($doc,$baseurl) {

  //  preg_match('//',$firstday
  //  ->getAttribute('value');

  $tables=$doc->getElementsByTagName('table');

  $a=array();
  map_to_children($a,'find_domelement_id',$doc,array('id'=>'kaldatvon'));
  if ($a[0]) {
    $firstday=$a[0]->getAttribute('value');
    preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/',$firstday,$matches);
    list($dummy,$tag,$monat,$jahr)=$matches;
  } else {
    throw new Exception("Kein Datum?");
  }
  foreach ($tables as $t) {    
    if ($t->getAttribute("class")=="tl1") {      
      $config=array('baseurl'=>$baseurl,
		    'jahr'=>$jahr,
		    'monat'=>$monat,
		    2 => 'datum',
		    4 => 'to_link',
		    5 => 'betreff');
      $a=array();
      map_to_children($a,'parse_table',$t,$config);
      return $a;
    }
  }
}


function retrieve_instance($iid) {
  $dbresult=pg_query_params(
			    'SELECT * FROM '.
			    'referenz JOIN instanz USING (referenz_id) '.
			    'WHERE instanz_id=$1',
			    array($iid) 
			    );  
  return pg_fetch_array($dbresult);
}

function parse_to_instance($iid) {
  $ref=retrieve_instance($iid);

  if ($ref['parsed']) {
    throw new UserException("Instanz $iid wurde bereits verarbeitet ($ref[parsed])");
  }

  $x=parse_url($ref['url']);
  $baseurl=$x['scheme'].'://'.$x['host'].preg_replace('|/[^/]*$|','/',$x['path']);

  $doc = new DOMDocument();

  $doc->loadHTML(tidy_repair_string(pg_unescape_bytea($ref['content']),array(),'utf8'));
  $to=parse_to($doc,$baseurl);

  $to=$to['parsed-data'];

  $i=1;
  foreach ($to as $top) {
    if (isset($top['betreffurl'])) {
      if (empty($top['vokey'])) {
	$top['vokey']=null;
      }
      if (empty($top['vourl'])) {
	$top['vourl']=null;
      }
      insert_top($ref['referenz_id'],$ref['instanz_id'],$top['topnr'],$top['betreff'],$top['betreffurl'],$top['vokey'],$top['vourl']);
    }
  }
  /*  pg_query_params('UPDATE instanz SET parsed=NOW() '.
		  'WHERE instanz_id=$1',
		  array($iid)
		  );*/
}


function parse_sk_instance($iid) {
  $ref=retrieve_instance($iid);

  if ($ref['parsed']) {
    throw new UserException("Instanz $iid wurde bereits verarbeitet ($ref[parsed])");
  }

  $x=parse_url($ref['url']);
  $baseurl=$x['scheme'].'://'.$x['host'].preg_replace('|/[^/]*$|','/',$x['path']);

  $doc = new DOMDocument();
  $doc->loadHTML(tidy_repair_string(pg_unescape_bytea($ref['content']),array(),'utf8'));
  $sk=parse_sk($doc,$baseurl);
  foreach ($sk['parsed-data'] as $ll=>$l) {
    if (isset($l['betreff']) ) {
      assert_referenz_id('tagesordnung','?-SI-'.$l['datum'],$l['betreff'],$ref['referenz_id'],$ll,$iid,$l['tourl'],$l['post']);
    }
	   //    assert_referenz_id('tagesordnung','');
  }
  pg_query_params('UPDATE instanz SET parsed=NOW() '.
		  'WHERE instanz_id=$1',
		  array($iid)
		  );
}
