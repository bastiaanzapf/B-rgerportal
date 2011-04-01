<?

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL,"http://www.svmr.de/bi/si019.asp");
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS,"SILFDNR=1403&options=16");
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
$result= curl_exec ($curl);
curl_close ($curl);
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

$forms=$doc->getElementsByTagName('form');

function find_name($name,$node) {
  foreach ($node->childNodes as $c) {
    if (get_class($c)=="DOMElement")
      if ($c->hasAttribute("name"))
	if ($c->getAttribute("name")==$name)
	  return $c->getAttribute("value");
    if ($c->hasChildNodes()) {
      find_name($name,$c);
    }
  }
  return false;
}

foreach ($forms as $f) {
  echo find_name("KPLFDNR",$f);
  echo "\n";
}

