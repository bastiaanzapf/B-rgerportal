<?

// Funktionen, um mit ratsinfomanagement.net zurechtzukommen

require('database.php');
require('lib.php');
require('charset.php');

$GLOBALS['rim']['baseurl']='https://marburg-biedenkopf.ratsinfomanagement.net/';

function rim_acquire_jsessionid() {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $GLOBALS['rim']['baseurl'].'index.do');
  curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false);

  $result= curl_exec ($curl);
  $contenttype= curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
  echo curl_error($curl);
  // var_dump($result);die();
  
  curl_close ($curl);
  preg_match('/jsessionid=([0-9A-F]{32})/',$result,$matches);
  return $matches[1];
}

//$GLOBALS['jsessionid']=rim_acquire_jsessionid();

function rim_geturl($relativeurl) {
  $curl=curl_init();
  curl_setopt($curl, CURLOPT_URL, $GLOBALS['rim']['baseurl'] . $relativeurl);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false);

  $result= curl_exec ($curl);
  $contenttype= curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
  var_dump($result);

}

function test(&$accum,$node,$params) {
  echo ".";
}

function rim_parse_sk($doc,$baseurl) {

  //  preg_match('//',$firstday
  //  ->getAttribute('value');

  var_dump($doc->saveHTML());die();
  $a=array();
  map_to_children($a,'test',$doc,array('regex'=>'|.|'));
  var_dump($a);
  die();
  if ($a[0]) {
    $year=$a[0]->getAttribute('value');
    var_dump($year);die();
    preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/',$firstday,$matches);
    list($dummy,$tag,$monat,$jahr)=$matches;
  } else {
    throw new Exception("Kein Datum?");
  }
  die("X");
  $tables=$doc->getElementsByTagName('table');
  foreach ($tables as $t) {    
    if ($t->getAttribute("class")=="table_data") {      
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

function rim_parse_sk_instance($iid) {
  $ref=retrieve_instance($iid);

  if ($ref['parsed']) {
    echo "Wurde schon geparsed.\n";
  }

  $x=parse_url($ref['url']);
  $baseurl=$x['scheme'].'://'.$x['host'].preg_replace('|/[^/]*$|','/',$x['path']);

  // htmltidy XXX
  $doc = new DOMDocument();

  assert($ref['content']);

  // @ sollte man nicht verwenden
  @$doc->loadHTML(tidy_repair_string(pg_unescape_bytea($ref['content']),array(),'utf8'));

  $sk=rim_parse_sk($doc,$baseurl);
  foreach ($sk['parsed-data'] as $ll=>$l) {
    var_dump($l);
    if (isset($l['betreff']) ) {
      //      assert_referenz_id('tagesordnung','?-SI-'.$l['datum'],$l['betreff'],$ref['referenz_id'],$ll,$iid,$l['tourl'],$l['post']);
    }
	   //    assert_referenz_id('tagesordnung','');
  }
  pg_query_params('UPDATE instanz SET parsed=NOW() '.
		  'WHERE instanz_id=$1',
		  array($iid)
		  );

}

//function assert_referenz_id($typ,$original_key,$original_description,$pid,$position,$instanz_entnommen,$url,$post);

$GLOBALS['mandant']=2;

$rid=assert_referenz_id('sitzungskalender','?-S','Sitzungskalender test',null,1,null,$GLOBALS['rim']['baseurl'] . 'termine.do',null);

/**
 * Sessionids streichen, um gleiche Hashes bei bis auf Sessionids gleichem Inhalt zu erreichen
 */

function rim_delete_session_ids($document) {
  return preg_replace('|jessionid=[0-9A-F]{32}|i','********************************',$document);
}

$iid=download_instance($rid,'identity',null,'rim_delete_session_ids');

rim_parse_sk_instance($iid);
//$sitzungen=rim_geturl('termine.do');

// https://marburg-biedenkopf.ratsinfomanagement.net/termine.do