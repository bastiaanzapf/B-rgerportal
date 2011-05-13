<?
echo "factsbroser_mehrere_instanzen.php";

if (isset($_REQUEST['children_of'])) {
  $p=array($_REQUEST['children_of']);
  $result=pg_query_params("SELECT DISTINCT instanz_entnommen ".
			  "FROM referenz WHERE parent=$1",$p);

  echo "<table>";
  while($row=pg_fetch_assoc($result)) {
    echo "<tr><td><a href='factsbrowser.php?children_of=$_REQUEST[children_of]&instanz_entnommen=$row[instanz_entnommen]'>Von Instanz ".pg_unescape_bytea($row['instanz_entnommen'])."</td></tr>";
  }
  echo "</table>";
}

