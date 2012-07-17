<?php

mysql_connect("localhost", "root", "");
mysql_select_db("baigiamasis_v4");


include 'boot.php';

$lat = new LatDEC($_GET['lat']);
$lon = new LonDEC($_GET['lon']);
$channel = $_GET['channel'];

$obj = new HCMParser("H:/TOPO");
$elevation =  $obj->getElevation($lat, $lon);

        
$TOP_LAT = 57.407823;
$BOTTOM_LAT = 52.800651;
$LEFT_LON = 21.008818;
$RIGHT_LON = 26.663818;
       
/*
$x = 0;
for($LON = $LEFT_LON; $LON <= $RIGHT_LON; $LON+=0.001) {
	for($LAT = $BOTTOM_LAT; $LAT <= $TOP_LAT; $LAT+=0.001) {
		$x++;
		if($LON > $_GET['lon'] && $LAT > $_GET['lat']) {
			break;
		}
		
	}
	if($LON > $_GET['lon'] ){
		break;
	}
	
}
*/

/*
echo "<br>";
echo "1:".$x;
echo "<br>";
*/

$TOTAL_LAT_STRIPS = CEIL(($TOP_LAT - $BOTTOM_LAT)/0.001);
$TOTAL_LON_STRIPS = CEIL(($RIGHT_LON - $LEFT_LON)/0.001);

$NEEDED_LAT = CEIL(($_GET['lat'] - $BOTTOM_LAT)/0.001);
$NEEDED_LON = CEIL(($_GET['lon']-$LEFT_LON)/0.001);

$x = ($NEEDED_LON * $TOTAL_LAT_STRIPS)+ $NEEDED_LAT+1;

/*
echo "<br>";
echo "2:".$x;
echo "<br>";
*/




function ToDouble($data) {
	$a = unpack("f*", strrev($data));
	return (float)$a[1]; 
}

$tx = array();
$sql = mysql_query("SELECT * FROM transmitters WHERE channel = {$channel}");
while($row = mysql_fetch_array($sql)) { 

	$fp = fopen("H:\EML\\{$row['id']}.dat", "rb+");

	fseek($fp, ($x-1)*4 );
	$a = fread($fp, 4);
	$E = round(toDouble($a),3);
	if($E == 0) $E = "< 0";
	
	$tx[] = array('name' => $row['name'], 'e' => $E );
}

echo json_encode(array(
	'lat' => round($_GET['lat'], 6),
	'lon' => round($_GET['lon'], 6),
	'elevation' => round($elevation,2),
	'tx' => $tx
));

?>