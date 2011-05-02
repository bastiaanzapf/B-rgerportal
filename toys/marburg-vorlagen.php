<?

require('database.php');
require('lib.php');
require('allris.php');
require('charset.php');
//download_instance(1);

$result=pg_query("SELECT referenz_id,original_description FROM referenz LEFT JOIN instanz USING (referenz_id) WHERE typ='vorlage' AND instanz_id IS NULL");

while ($r=pg_fetch_assoc($result)) {
  echo "Lade Referenz $r[referenz_id] ($r[original_key])\n";
  try {
    download_instance($r['referenz_id'],'latin1_to_utf8');
  } catch (UserException $e) {
    echo $e->getMessage()."\n";
  }
 }
