<?
require('database.php');


if (isset($_REQUEST['instance'])) {
  $p=array($_REQUEST['instance']);
  $result=pg_query_params("SELECT * ".
			  "FROM instanz WHERE instanz_id=$1",$p);
  
  $row=pg_fetch_assoc($result);
  header('Content-Type: '.$row['content_type_reported']);
  echo pg_unescape_bytea(pg_unescape_bytea($row['content'])); // ???
  die();
}

require('header.php');

if (isset($_REQUEST['reference'])) {
  $p=array($_REQUEST['reference']);
  $result=pg_query_params("SELECT DISTINCT instanz_id ".
			  "FROM instanz WHERE referenz_id=$1",$p);

  if (pg_num_rows($result)>1) {
    include('instanzbrowser_mehrere_instanzen.php');
    require('footer.php');
    return;
  }

  $result=pg_query_params("SELECT instanz_id,retrieved,hash FROM instanz ".
			  "WHERE referenz_id=$1 ".
			  "ORDER BY retrieved DESC",$p);
} else {
  $result=pg_query("SELECT 1 WHERE 1=0");
} 

echo "<table>";
while($row=pg_fetch_assoc($result)) {
  echo "<tr><td>$row[instanz_id]</td><td>$row[retrieved]</td><td><a href='?instance=$row[instanz_id]'>$row[hash]</a></td></tr>";
}
echo "</table>";

require('footer.php');