<?

require('header.php');
require('database.php');

if (isset($_REQUEST['children_of'])) {
  $p=array($_REQUEST['children_of']);
  $result=pg_query_params("SELECT DISTINCT instanz_entnommen ".
			  "FROM referenz WHERE parent=$1",$p);

  if (pg_num_rows($result)>1) {
    include('factsbrowser_mehrere_instanzen.php');
    require('footer.php');
    return;
  }

  $result=pg_query_params("SELECT * FROM referenz ".
			  "WHERE parent=$1 ".
			  "ORDER BY original_key DESC",$p);
} else {
  $result=pg_query("SELECT * FROM referenz WHERE typ='tagesordnung' ORDER BY original_key DESC");
} 

echo "<table>";
while($row=pg_fetch_assoc($result)) {
  echo "<tr><td><a href='instanzbrowser.php?reference=$row[referenz_id]'>$row[original_key]</a></td><td><a href='?children_of=$row[referenz_id]'>$row[original_description]</a></td></tr>";
}
echo "</table>";

require('footer.php');