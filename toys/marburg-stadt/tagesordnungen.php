<?

require('../database.php');
require('../lib.php');
require('../allris.php');
require('../charset.php');
//download_instance(1);

$result=pg_query("SELECT referenz_id,original_description FROM referenz LEFT JOIN instanz USING (referenz_id) WHERE typ='tagesordnung' AND instanz_id IS NULL AND url!=''");

while ($r=pg_fetch_assoc($result)) {
  echo "Lade Referenz $r[referenz_id] ($r[original_description])\n";
  try {
    download_instance($r['referenz_id'],'latin1_to_utf8');
  } catch (UserException $e) {
    echo $e->getMessage()."\n";
  }
 }

$result=pg_query("SELECT instanz_id,original_description FROM referenz JOIN instanz USING (referenz_id) WHERE typ='tagesordnung'");
echo pg_last_error();
while ($i=pg_fetch_assoc($result)) {
  echo "Verarbeite Instanz $i[instanz_id] (".pg_unescape_bytea($i['original_description']).")\n";
  try {
    parse_to_instance($i['instanz_id']);
  } catch (UserException $e) {
    echo $e->getMessage()."\n";
  }
 }