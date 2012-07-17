<?php

class HCMParser {
	
	const RECORDS = 144;
	
	var $db_path;
	
	function __construct($db_path = TOPO_DB) {
		$this->db_path = $db_path;
	}
		 
	public function getElevation($lat, $lon) {
		
		if(!($lat instanceof Cordinate)) {
			throw new Exception('Wrong $lat param given, should be object of Latitude class');
		}
		
		if(!($lon instanceof Cordinate)) {
			throw new Exception('Wrong $lon param given, should be object of Longitude class');
		}
		
		if($lat->deg >= 0) 
			$lat_prefix = 'N';
		else
			$lat_prefix = 'S';
			
		if($lon->deg >= 0)
			$lon_prefix = 'E';
		else
			$lon_prefix = 'W';
		
		// The HCM terrain height data has a resolution of 3 seconds in the North - South direction 
		$lat_resolution = 3;
		
		//If the latitude is less than 50 degrees (North or South), the resolution is 3 seconds

		if(abs($lat->deg) < 50 ) 
			$lon_resolution = 3;
		else
			$lon_resolution = 6;
		
		// INTERPOLATION GOES HERE		
		if($lat->sec % $lat_resolution != 0 && $lon->sec % $lon_resolution != 0) {
		
			/*
				[ P1 ] [ P2 ]
					  *
				[ P3 ] [ P4 ] 
			*/
				
			$inf_lat = $this->roundnum($lat->sec, $lat_resolution);
			$sup_lat = $inf_lat - $lat_resolution;
			
			$inf_lon = $this->roundnum($lon->sec, $lon_resolution);
			$sup_lon = $inf_lon - $lon_resolution;
					
			/* Cordinates */
			$c1 = new Lat($lat->deg, $lat->min, $inf_lat);
			$c2 = new Lat($lat->deg, $lat->min, $sup_lat);
			$c3 = new Lon($lon->deg, $lon->min, $inf_lon);
			$c4 = new Lon($lon->deg, $lon->min, $sup_lon);
						
			$e1 = $this->getElevation($c1, $c3);
			$e2 = $this->getElevation($c1, $c4);
			$e3 = $this->getElevation($c2, $c3);
			$e4 = $this->getElevation($c2, $c4);
			
			$points = array(
				array($c1->dec, $c3->dec, $e1),
				array($c1->dec, $c4->dec, $e2),
				array($c2->dec, $c3->dec, $e3),
				array($c2->dec, $c4->dec, $e4)
			);
			
			return $this->bilinear_interpolation($lat->dec, $lon->dec, $points);
			
		}
		elseif($lat->sec % $lat_resolution != 0) {
			/**
			[ P1 ] 
			  *
			[ P2 ]
			**/
			
			$inf_lat = $this->roundnum($lat->sec, $lat_resolution);
			$sup_lat = $inf_lat - $lat_resolution;
			
			$c1 = new Lat($lat->deg, $lat->min, $sup_lat);
			$c2 = new Lat($lat->deg, $lat->min, $inf_lat);
			
			$e1 = $this->getElevation($c1, $lon);
			$e2 = $this->getElevation($c2, $lon);
			
			return $this->linear_interpolation($c1->sec, $lat->sec, $c2->sec, $e1, $e2);
		}
		elseif($lon->sec % $lon_resolution != 0) {
			/**
			* [P1] * [P2]
			**/
			$inf_lon = $this->roundnum($lon->sec, $lon_resolution);
			$sup_lon = $inf_lon - $lon_resolution;
					
			$c1 = new Lon($lon->deg, $lon->min, $sup_lon);
			$c2 = new Lon($lon->deg, $lon->min, $inf_lon);
					
			$e1 = $this->getElevation($lat, $c1);
			$e2 = $this->getElevation($lat, $c2);
	
			return $this->linear_interpolation($c1->sec, $lon->sec, $c2->sec, $e1, $e2);
		}
		// No interpolation needed
		else {
				
			// Zero fill longitude 04 -> 004
			$x_0 = str_pad(abs($lon->deg), 3, '0', STR_PAD_LEFT);
			$y_0 = str_pad(abs($lat->deg), 2, '0', STR_PAD_LEFT);
			
			$file_path = $this->db_path . DS . $lon_prefix . $x_0 . DS . $lon_prefix . $x_0 . $lat_prefix . $y_0 . DOT . $lon_resolution. $lat_resolution . 'E'; 
					
			if($lon_resolution == 3 ) 
				$record_size = 20402;
			else
				$record_size = 10302;
				
			$record_id = $this->findRecordID($lat->min, $lon->min);
			
			$record_offset = $this->offset($record_id, $record_size);
			
		
			
			$point_id = $this->findPointID($lat, $lon, $lon_resolution);
			
			$point_offset = $this->offset($point_id, 2);

			$OFFSET = $record_offset + $point_offset;
			
			$hex_value = $this->readBinary($file_path, $OFFSET);
			
			$a = unpack('s', $hex_value);
			
			return $a[1];
		}
	}

	private function readBinary($file, $offset) {
		$handler = fopen($file, "rb+");
		if(!$handler) {
			throw new Exception("Could not open $file");
		}
		fseek($handler, $offset);
		return fread($handler, 2);	
	}
		
	private function findRecordID($lat, $lon) {
			
		$lat_to = $this->roundnum($lat,5);
		$lat_from =  $lat_to-5;

		$lon_to = $this->roundnum($lon, 5);
		$lon_from = $lon_to - 5;
		 
		$record_number = ($lat_from/5) * 12  + ($lon_to/5);
		if($lon !=0 && $lon % 5 == 0) {
			$record_number++;
		}
			
		if($lat != 0 && $lat % 5 == 0) {
			$record_number += 12;
		}
		
		return $record_number;
	}
		
	private function roundNum($num, $nearest = 5 ) {
		if($num == 0)
			return $nearest;
		else
			return ceil($num / $nearest) * $nearest; 
	}

	private function findPointID($lat, $lon, $resolution) {

		$LON_SEC_INDEX = ($lon->sec/$resolution)+1;
			
		$LON_MIN_INDEX = $lon->min % 5;
	
		if($resolution == 3 )
			$LON_INDEX = $LON_MIN_INDEX * 20 + $LON_SEC_INDEX;
		else
			$LON_INDEX = $LON_MIN_INDEX * 10 + $LON_SEC_INDEX;
	
		/*---------------*/
		
		$LAT_MIN_INDEX = $lat->min % 5;
	
		$LAT_SEC_INDEX = ($lat->sec)/3;

		if($resolution == 3) 
			$LAT_INDEX = (($LAT_MIN_INDEX * 20) + $LAT_SEC_INDEX) * 101;
		else
			$LAT_INDEX = (($LAT_MIN_INDEX * 20) + ($LAT_SEC_INDEX)) * 51;
	
		$INDEX = $LON_INDEX +  $LAT_INDEX;
	
		return $INDEX;
	}
	
	private function offset($record_number, $size) {
		return  ($record_number-1)*$size;
	}
	
	private function linear_interpolation($x1, $x2, $x3, $y1, $y3) {
		return ((($x2-$x1)*($y3-$y1))/($x3-$x1))+$y1;
	}
	
	/**
	* http://stackoverflow.com/questions/8661537/how-to-perform-bilinear-interpolation-in-python
	* http://en.wikipedia.org/wiki/Bilinear_interpolation
	*
	**/
	private function bilinear_interpolation($x, $y, $points ) {
		
		array_multisort($points);
		
		list($x1, $y1, $q11) = $points[0];
		list($_x1, $y2, $q12) = $points[1];
		list($x2, $_y1, $q21) = $points[2];
		list($_x2, $_y2, $q22) = $points[3];

		if($x1 != $_x1 or $x2 != $_x2 or $y1 != $_y1 or $y2 != $_y2) {
			throw new Exception('Points do not form a rectangle');
		}
		
		if(!($x1 <= $x && $x <= $x2) or !($y1 <= $y && $y <= $y2)) {
			throw new Exception('(x, y) not within the rectangle');
		}
			
		return ($q11 * ($x2 - $x) * ($y2 - $y) +
            $q21 * ($x - $x1) * ($y2 - $y) +
            $q12 * ($x2 - $x) * ($y - $y1) +
            $q22 * ($x - $x1) * ($y - $y1)
           ) / (($x2 - $x1) * ($y2 - $y1));
	}
} 
?>