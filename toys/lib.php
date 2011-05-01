<?

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

function find_domelement_class_regex($accum,$node,$params) {
  if (get_class($node)=="DOMElement") {
    if (preg_match($params['regex'],$node->getAttribute("class"))) {
      $accum[]=$node;
      return $accum;
    }
  }
  return $accum;
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
  if (!isset($GLOBALS['insert_top_prepared'])) {
    $GLOBALS['insert_top_prepared']=true;
    pg_prepare('insert_r_top','INSERT INTO referenz (typ,parent,instanz_entnommen,position,url,original_description) VALUES (\'tagesordnungspunkt\',$1,$2,$3,$4,$5)');
    pg_prepare('insert_r_vo','INSERT INTO referenz (typ,parent,instanz_entnommen,position,original_key,url) VALUES (\'vorlage\',$1,$2,$3,$4,$5)');
  }
  pg_execute('insert_r_top',array($pid,$iid,$nr,$detailsurl,$betreff));  
  if ($vokey || $vourl) {
    pg_execute('insert_r_vo',array($pid,$iid,$nr,$vokey,$vourl));  
  }
}

function assert_referenz_id($typ,$original_key,$original_description,$pid,$position,$instanz_entnommen,$url,$post) {
  $result=pg_query_params('SELECT referenz_id FROM referenz WHERE original_key=$1',
			  array($original_key));
  if (pg_num_rows($result)) {
    $ref=pg_fetch_assoc($result);
    return $ref['referenz_id'];
  }

  pg_query_params('INSERT INTO referenz (typ,original_key,original_description,parent,position,instanz_entnommen,url,post) '.
		  'VALUES ($1,$2,$3,$4,$5,$6,$7,$8)',
		  array($typ,$original_key,$original_description,
			$pid,$position,$instanz_entnommen,
			$url,$post));
  $result=pg_query("SELECT currval('referenz_referenz_id_seq')");
  $id=pg_fetch_row($result);
  return $id[0];
}

function download_instance($rid) {
  $dbresult=pg_query_params('SELECT * FROM referenz WHERE referenz_id=$1',
		  array($rid));
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
  
  $hash=sha1($tidy);  

  $samehash=pg_query_params('SELECT instanz_id,retrieved FROM instanz WHERE '.
			    'hash=$1',
			    array($hash));
  if (pg_num_rows($samehash)) {
    $data=pg_fetch_assoc($samehash);
    throw new UserException("Keine inhaltliche Änderung gegenüber Instanz $data[instanz_id] (abgerufen $data[retrieved])");
  }

  pg_query_params(
	     'INSERT INTO instanz (referenz_id,retrieved,content,hash) '.
	     'VALUES ($1,NOW(),$2,$3)',
	     array($rid,$tidy,sha1($tidy)));

}


