<?

require('header.php');
require('database.php');

if (isset($_REQUEST['children_of'])) {
  $p=$_REQUEST['children_of'];
  if (isset($_REQUEST['instanz_entnommen']) && $_REQUEST['instanz_entnommen']) {
    $pa=array($p,$_REQUEST['instanz_entnommen']);
  } else {
    $pa=array($p,null);
  }
  $result=pg_query_params("SELECT DISTINCT instanz_entnommen ".
			  "FROM referenz WHERE parent=$1 AND ".
			  "(($2::Integer IS NULL) OR instanz_entnommen=$2) ",$pa);
  if (pg_num_rows($result)>1) {
    include('factsbrowser_mehrere_instanzen.php');
    require('footer.php');
    return;
  }

  $result=pg_query_params("SELECT * FROM referenz ".
			  "WHERE parent=$1 AND ".
			  "(($2::Integer IS NULL) OR instanz_entnommen=$2)".
			  "ORDER BY original_key DESC",$pa);
} else {
  $result=pg_query("SELECT * FROM referenz WHERE typ='tagesordnung' ORDER BY original_key DESC");
} 



echo "<table>";
while($row=pg_fetch_assoc($result)) {
  if (empty($row['original_key'])) {
    $row['original_key']="Kein Schlüssel";
  }
  echo "<tr><td>$row[typ]</td><td><a href='instanzbrowser.php?reference=$row[referenz_id]'>$row[original_key]</a></td><td><a href='?children_of=$row[referenz_id]'>".pg_unescape_bytea($row['original_description'])."</a></td></tr>";
}
echo "</table>";

require('footer.php');