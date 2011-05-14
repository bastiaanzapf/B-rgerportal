<?

// Funktionen, um mit ratsinfomanagement.net zurechtzukommen

require('lib.php');

function acquire_jsessionid() {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, 'https://marburg-biedenkopf.ratsinfomanagement.net/index.do');
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

$GLOBALS['jsessionid']=acquire_jsessionid();

