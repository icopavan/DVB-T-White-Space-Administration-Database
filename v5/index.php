<?

	mysql_connect("localhost", "root", "");
	mysql_select_db("baigiamasis_v4");
	mysql_query("SET NAMES utf8");
	mysql_query("SET CHARSERT utf8");
	if(!isset($_GET['channel'])) {
		$_GET['channel'] = 21;
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<link rel="stylesheet" type="text/css" href="bootstrap.css" />
<title>Lithuanian DVB-T white space administrative database based on ITU-R P1546</title>
<link rel="stylesheet" type="text/css" href="lib/tables.less" />
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=true"></script>
<script type="text/javascript" src="js/markerswithlabel.js"></script>
<script type="text/javascript" src="js/jquery.min.js"></script>
<link rel="shortcut icon" href="avicon.ico" type="image/x-icon">
<link rel="icon" href="favicon.ico" type="image/x-icon">
<style type="text/css">
	body {
		height: 100%;
		width: 100%;
		position: absolute;
	}

	#map_canvas { height: 100% }
	  
	div#legend { 
		float:right;
		background-color:#F5F5F5;
		padding: 4px
	}
	
	.labels {
		font-size:10px;
		color: black;
		font-weight: bold;
	}
	
	.options {
		margin-top:2px;
	}
</style>
<script type="text/javascript">

  var map;
  var txs = [];
  
  function display_tx() {
	if($('#display_tx').attr('checked') == 'checked'){
		for(i = 0; i < txs.length; i++) {
			txs[i].setMap(map);
		}	
	}
	else {
		for(i = 0; i < txs.length; i++) {
			txs[i].setMap(null);
		}	
	}
  }

  function initialize() {
   
   var latlng = new google.maps.LatLng(55.264999, 23.985832);
   
   var markerimage = new google.maps.MarkerImage('tower.png', new google.maps.Size(50, 50));
	
    var myOptions = {
      zoom: 7,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.TERRAIN
    };
	
	
    var image = new google.maps.MarkerImage(
	  'marker-images/image.png',
		new google.maps.Size(40,35),
		new google.maps.Point(0,0),
		new google.maps.Point(0,35)
	);

	var shadow = new google.maps.MarkerImage(
	  'marker-images/shadow.png',
		new google.maps.Size(62,35),
		new google.maps.Point(0, 0),
		new google.maps.Point(0,35)
	);

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
	var boundaries1 = new google.maps.LatLngBounds(
		new google.maps.LatLng( 52.800651, 21.008818), 
		new google.maps.LatLng( 57.407823, 26.663818)
	);
	
	
	rmap1 =new google.maps.GroundOverlay("titles/<?= $_GET['channel'] ?>.png", boundaries1);
	rmap1.setMap(map);
	
	var marker;

	function placeMarker(location) {
	  if ( marker ) {
		marker.setPosition(location);
	  } else {
		marker = new google.maps.Marker({
		  position: location,
		  map: map
		});
	  }
  
		  $.ajax({
			  url: 'area_data.php',
			  data: {lat : location.lat(), lon: location.lng(), 'channel' : <?= $_GET['channel'] ?>},
			  success: function(data) {

			    data = JSON.parse(data);
				
				b = "<table>";
				b += "<thread>";
				b += "<th>TX Name</th>";
				b += "<th>E<sub>med</sub></th>";
				b += "</thread>";
				b += "<tbody>";
				for(i = 0; i < data.tx.length; i++) {
					b += "<tr>";
					b += "<td>"+data.tx[i].name+"</td>";
					b += "<td>"+data.tx[i].e+"</td>";
					b += "</tr>";
				}
				b += "</tbody>";
				b += "</table>";
				
				
				$("#info").html(
				'<h4>Location analysis</h4>'+
				'<table>'+
				'<tr>'+
				'<td><b>Latitude</b></td>'+
				'<td>'+data.lat+'</td>'+
				'</tr>'+
				'<tr>'+
				'<td><b>Longtitude</b></td>'+
				'<td>'+data.lon+'</td>'+
				'</tr>'+
				'<tr>'+
				'<td><b>Elevation:</b></td>'+
				'<td>'+ data.elevation + ' m</td>'+
				'</tr>'+
				'<tr>'+
				'</table>'+
				'<h4>Electric field, dB(&micro;V/m)</h4>'+
				b
				);
				
				
				
			  }
		}); 
	}

	google.maps.event.addListener(map, 'click', function(event) {
	  placeMarker(event.latLng);
	});
	
	google.maps.event.addListener(rmap1, 'click', function(event) {
	  placeMarker(event.latLng);
	});


	<?php
		$q = mysql_query("SELECT * FROM `transmitters` where channel = {$_GET['channel']}");	 
		while($row = mysql_fetch_array($q)):
	?>
		var myLatlng = new google.maps.LatLng(<?= $row['lat']?>,<?= $row['lon']?> );

	
		
		marker<?= $row['id']?> = new MarkerWithLabel({
			position: myLatlng, 
			map: map,
			labelContent: "<?= $row['name'] ?>",
			icon: image,
			shadow: shadow,
			title:"<?= $row['name'] ?>",
			 labelClass: "labels", // the CSS class for the label
			//labelStyle: {opacity: 0.75}
		});
		
		<? $sql2 =  mysql_query("SELECT * FROM `erps` WHERE transmitter_id = {$row['id']}"); 
			$ln = "<table>";
			$ln .= "<thead>";
			$ln .= "<tr>";
			$ln .= "<th>Azimuth, &deg;</th>";
			$ln .= "<th>ERP, dBW</th>";
			$ln .= "</thead>";
			$ln .= "</tr>";
			$ln .= "<tbody>";
			while($row2 = mysql_fetch_array($sql2)) {
				$ln .= "<tr>";
				$ln .= "<td>{$row2['azimuth']}</td>";
				$ln .= "<td>{$row2['erp']}</td>";
				$ln .= "</tr>";
			}
			$ln .= "</tbody>";
			$ln .= "</table>";
		?>

		google.maps.event.addListener(marker<?= $row['id']?>, 'click', function() {
		
			document.getElementById("info").innerHTML = 
			'<h4>Transmitter details</h4>' 
			+ '<table>'
			+ '<tr>'
			+ '<td><b>Name</b></td>'
			+ '<td><?= $row['name'] ?></td>'
			+ '</tr>'
			+ '<td><b>Lat</b></td>'
			+ '<td><?= $row['lat'] ?></td>'
			+ '</tr>'
			+ '<td><b>Lon</b></td>'
			+ '<td><?= $row['lon'] ?></td>'
			+ '</tr>'
			+ '<tr>'
			+ '<td><b>Height:</b></td>'
			+ '<td><?= $row['height'] ?> m</td>'
			+ '</tr>'
			+ '<tr>'
			+ '<td><b>Frequency:</b></td>'
			+ '<td><?= $row['frequency'] ?> MHz</td>'
			+ '</tr>'
			+ '<tr>'
			+ '<td><b>Channel:</b></td>'
			+ '<td><?= $row['channel'] ?></td>'
			+ '</tr>'
			+ '</ul>'
			+ '<?= $ln ?>';	
			document.getElementById("info").scrollTop = 0;	
		});
		
		txs.push(marker<?= $row['id']?>);
		
	<?php endwhile ?>
}	
</script>
</head>
	<body onload="initialize()">
		<div class="topbar">
	      <div class="topbar-inner">
	        <div class="container-fluid">
	          <a class="brand" href="#">DVB-T</a>
			
	          <ul class="nav">
		  
			  <? $sql = mysql_query("SELECT channel FROM transmitters GROUP BY channel"); ?>
			  <? while($row = mysql_fetch_array($sql)): ?>
	            <li <?= @$_GET['channel'] == $row['channel'] ? 'class="active"' : '' ?>><a href="index.php?channel=<?= $row['channel']?>">
					<?= $row['channel']?></a>
				</li>
			  <? endwhile ?>
	          </ul>
			  
	        </div>
	      </div>
	    </div>
	
	  <div class="container-fluid" style='height: 93%; margin-top:5%'>
	    <div class="sidebar" style="height:inherit">
	      	<div class="well" style="max-height:100%; overflow:auto; padding-right:4px" id="info" >	
				Click on transmitter or area
	        </div>
	    </div>
	    <div class="content" style='height: inherit'>
	    	<div id="map_canvas"></div>
			<div id="legend">
				<span style="height: 15px; width: 15px; background-color:#7FFF00;display: inline-block; vertical-align: middle"></span> 99% location
				<span style="height: 15px; width: 15px; background-color:#FFFF00;display: inline-block; vertical-align: middle"></span> 75% location
			</div>
			<div class="options">
				<span>Display transmitters <input onclick="display_tx()" type="checkbox" checked="checked" id="display_tx" /></span>
				<span style="font-weight:bold; margin-left:10px; margin-right:10px">|</span>
				<span >RX antenna height <select style="width:50px"><option>10</option></select> metres</span>
			</div>
	    </div>
	  </div>
	</body>
</html>