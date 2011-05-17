<?
require('database.php');

require('header.php');

if (!isset($_REQUEST['suche'])) {
  die("Suche?");
}

$result=pg_query_params("select instanz_id,hash,retrieved from instanz where to_tsvector('german',convert_from(content,'utf8')) @@ to_tsquery('german',$1)",array($_REQUEST['suche']));
echo "<table>";
while($row=pg_fetch_assoc($result)) {
  echo "<tr><td>$row[instanz_id]</td><td>$row[retrieved]</td><td><a href='instanzbrowser.php?instance=$row[instanz_id]'>$row[hash]</a></td></tr>";
}
echo "</table>";

require('footer.php');