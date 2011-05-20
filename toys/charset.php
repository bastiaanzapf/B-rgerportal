<?


function latin1_to_utf8($text) {
  return iconv('latin1','utf8',$text);
}

function identity($text) {
  return $text;
}