<?php

define('NAME', 0);
define('LON', 1);
define('LAT', 2);
define('FREQUENCY', 3);
define('CHANNEL', 4);
define('HEIGHT', 5);

set_time_limit(0);

function random_hex_color(){
    return sprintf("%02X%02X%02X", mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
}


mysql_connect("localhost", "root", "") or die("Could not connect to mysql server");
mysql_select_db("baigiamasis_v4") or die("Could not connect to database");
mysql_query("SET NAMES utf8");
mysql_query("SET charset utf8");

$i=0;
if ($f = fopen('transmitter_table.csv', 'r')) do {

    $line = fgets($f);
	$line = str_replace(',', '.', $line);
	
	$a = explode(";", $line);

	if(!empty($a) &&  isset($a[LON]) && (double) $a[LON] > 0) {

	
		mysql_query("
			INSERT INTO `transmitters`
			(name, lon, lat, height, channel, frequency)
			VALUES
			('{$a[NAME]}', '{$a[LON]}', '{$a[LAT]}', '{$a[HEIGHT]}', '{$a[CHANNEL]}', '{$a[FREQUENCY]}')
		");
		
		$transmitter_id = mysql_insert_id();
		$offset = 7;
				
		for($i = 0; $i < 36; $i++) {
			$erp = $a[$offset + $i];
			$azimuth = $i * 10;
			mysql_query("
			INSERT INTO erps 
				(transmitter_id, erp, azimuth)
			VALUES
				($transmitter_id, {$erp}, {$azimuth})
			");
		}		
	}
	
} while (!feof($f));

fclose($f);

echo "Importing done";

?>
