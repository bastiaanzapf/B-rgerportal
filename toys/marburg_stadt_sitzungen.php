<?

require('database.php');
require('lib.php');
require('allris.php');

if (false) {
  foreach (array(2008,2009,2010,2011) as $jahr) {
    foreach (array(1,2,3,4,5,6,7,8,9,10,11,12) as $monat) {
      echo "*** Referenz $rid ***\n";
      try {
	$rid=assert_referenz_id('sitzungskalender',"?-SK-$jahr-$monat","Sitzungskalender von $monat $jahr",null,$jahr*12+$monat,null,"http://www.svmr.de/bi/si010_j.asp?MM=$monat&YY=$jahr",null);
	download_instance($rid);
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

