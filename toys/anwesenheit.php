<?

$doc = new DOMDocument();
$doc->loadHTMLFile("../cache/si019.asp.html.tidy");

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

