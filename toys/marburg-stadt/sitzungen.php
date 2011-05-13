<?

require('database.php');
require('lib.php');
require('allris.php');

if (pg_client_encoding()!='UTF8') { 
  pg_set_client_encoding('UTF8');
  if (pg_client_encoding()!='UTF8') {
    throw new Exception('Kann Encoding nicht wechseln.');
  }
}

function latin1_to_utf8($x) {
  return iconv('latin1','utf8',$x);
}

$result=pg_query("SELECT NULL FROM referenz WHERE typ='sitzungskalender' LIMIT 1");

if (!pg_num_rows($result)) {
  foreach (array(2006,2007,2008,2009,2010,2011) as $jahr) {
    foreach (array(1,2,3,4,5,6,7,8,9,10,11,12) as $monat) {
      echo "*** Referenz $rid ***\n";
      try {
	$rid=assert_referenz_id('sitzungskalender',"?-SK-$jahr-$monat","Sitzungskalender von $monat $jahr",null,$jahr*12+$monat,null,"http://www.svmr.de/bi/si010_j.asp?MM=$monat&YY=$jahr",null);
	download_instance($rid,'latin1_to_utf8');
      } catch (UserException $e) {
	echo $e->getMessage();
      }
    }
  }
 }

$result=pg_query("SELECT instanz_id ".
		 "FROM referenz JOIN instanz USING (referenz_id) ".
		 "WHERE typ='sitzungskalender' AND parsed IS NULL");

while ($row=pg_fetch_assoc($result)) {
  parse_sk_instance($row['instanz_id']);
 }

