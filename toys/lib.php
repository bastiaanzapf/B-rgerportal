<?

/**
 * Bloss eine Markierung: Anwendungsfehler - nichts, was 
 */

class UserException extends Exception {
  
}

function map_to_children(&$accum,$function,$node,$params) {
  $function($accum,$node,$params);
  if ($node->childNodes) {
    foreach ($node->childNodes as $c) {
      map_to_children($accum,$function,$c,$params);
    }
  }
}

function find_domelement_class_regex(&$accum,$node,$params) {
  if (get_class($node)=="DOMElement") {
    if (preg_match($params['regex'],$node->getAttribute("class"))) {
      $accum[]=$node;
    }
  }
}

function find_domelement_id(&$accum,$node,$params) {
  if (get_class($node)=="DOMElement") {    
    if ($node->hasAttribute('id')) {
      if ($params['id']==$node->getAttribute("id")) {
	$accum[]=$node;
      }
    }
  }
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

  $localconfig=array('line'=>$line,'column'=>$column);

  if (isset($params[$column]))  {
    $params[$column](&$accum,$node,array_merge($localconfig,$params));
  }
}

function getqueryasarray($url) {
  $x=parse_url($url);
  parse_str($x['query'],$r);
  return $r;
}

function insert_top($pid,$iid,$nr,$betreff,$detailsurl,$vokey,$vourl) {
  assert(!empty($GLOBALS['mandant']));
  pg_query_params('INSERT INTO referenz (mandant_id,typ,parent,instanz_entnommen,position,url,original_description) VALUES (\'tagesordnungspunkt\',$1,$2,$3,$4,$5)',array($GLOBALS['mandant'],$pid,$iid,$nr,$detailsurl,$betreff));  
  $result=pg_query("SELECT CURRVAL('referenz_referenz_id_seq')");
  $row=pg_fetch_row($result);
  $rid=$row[0];
  if ($vokey || $vourl) {
    $result2=pg_query_params('SELECT NULL FROM referenz WHERE mandant_id=$1 AND original_key=$2',array($GLOBALS['mandant'],$vokey));
    if (!pg_num_rows($result2)) {
      pg_query_params('INSERT INTO referenz (mandant_id,typ,parent,instanz_entnommen,position,original_key,url) VALUES (\'vorlage\',$2,$3,$4,$5,$6)',array($GLOBALS['mandant'],$pid,$rid,$nr,$vokey,$vourl));  
    }
  }
}

function assert_referenz_id($typ,
			    $original_key,
			    $original_description,
			    $pid,
			    $position,
			    $instanz_entnommen,
			    $url,
			    $post) {
  assert(!empty($GLOBALS['mandant']));
  $result=pg_query_params('SELECT referenz_id FROM referenz WHERE mandant_id=$1 AND original_key=$2',
			  array($GLOBALS['mandant'],$original_key));
  if (pg_num_rows($result)) {
    $ref=pg_fetch_assoc($result);
    return $ref['referenz_id'];
  }

  pg_query_params('INSERT INTO referenz (mandant_id,typ,original_key,original_description,parent,position,instanz_entnommen,url,post) '.
		  'VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)',
		  array($GLOBALS['mandant'], // 1 
			$typ, // 2
			$original_key, // 3
			$original_description, // 4
			$pid, // 5
			$position, // 6
			$instanz_entnommen, // 7
			$url, // 8
			$post // 9
			));
  $result=pg_query("SELECT currval('referenz_referenz_id_seq')");
  $id=pg_fetch_row($result);
  return $id[0];
}

function download_instance($rid,$todbcoding,$mapparams=null,$mapresult=null) {
  assert(!empty($GLOBALS['mandant']));

  if (!$todbcoding) {
    throw new Exception("Keine Zeichensatzübersetzung angegeben (identität?)");
  }
  $dbresult=pg_query_params('SELECT * FROM referenz WHERE mandant_id=$1 AND referenz_id=$2',
			    array($GLOBALS['mandant'],$rid));
  $ref=pg_fetch_assoc($dbresult);

  if (empty($ref))
    throw new Exception("Kann Referenz $id nicht in Datenbank finden");

  if (!empty($mapparams))
    list($ref['url'],$ref['post'])=$mapparams($ref['url'],$ref['post']);

  $curl = curl_init();

  curl_setopt($curl, CURLOPT_URL, $ref['url']);
  if (isset($ref['post'])) {
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS,$ref['post']);
  }
  curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
  $result= curl_exec ($curl);
  $contenttype= curl_getinfo($curl,CURLINFO_CONTENT_TYPE);
  if (curl_errno($curl))
    throw new Exception(curl_error($curl));
  curl_close ($curl);

  //echo $result;
  if (!empty($mapresult))
    $result=$mapresult($result);
  
  $hash=sha1($result);  


  $samehash=pg_query_params('SELECT instanz_id,retrieved FROM instanz WHERE '.
			    'hash=$2 AND mandant_id=$1',
			    array($GLOBALS['mandant'],$hash));

  if (pg_num_rows($samehash)) {
    $data=pg_fetch_assoc($samehash);
    throw new UserException("Keine inhaltliche Änderung gegenüber Instanz $data[instanz_id] (abgerufen $data[retrieved])");
  }

  if ($contenttype=='text/html') {
    $utf8_encoded_result=$todbcoding($result);
  }
  else
    $utf8_encoded_result=$result;

  pg_query_params(
	     'INSERT INTO instanz (mandant_id,referenz_id,content,hash,content_type_reported,retrieved) '.
	     'VALUES ($1,$2,$3::bytea,$4,$5,NOW())',
	     array($GLOBALS['mandant'],$rid,pg_escape_bytea($utf8_encoded_result),$hash,$contenttype));

  $result=pg_query("SELECT CURRVAL('instanz_instanz_id_seq')");
  echo pg_last_error();
  $row=pg_fetch_row($result);
  return $row[0];

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
