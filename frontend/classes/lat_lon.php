<?php

class LatLon {
	
	/**
	* Holds latitude value
	* @var object
	*/
	public $lat;
	
	/**
	* Holds longitude value
	* @var object
	*/
	public $lon;
		
	// Earth radius
	const R = 6353;
		
	function __construct($lat, $lon) {
		$this->lat = $lat;
		$this->lon = $lon;
	}
		
	/**
     * Calculates disttance offset from given point cordinates
     * @param object $lat
	 * @param object $lon
	 * @param int $distance
	 * @param int $azmimuth
     * @return object Point
     */
	function calculateOffset($distance, $azimuth) {
		$brng = deg2rad($azimuth);
		$lat = $this->lat->rad;
		$lon = $this->lon->rad;

		$lat2 = asin(sin($lat)*cos($distance/LatLon::R) + cos($lat)*sin($distance/LatLon::R)*cos($brng));	
		$lon2 = $lon + atan2(sin($brng)*sin($distance/LatLon::R)*cos($lat), cos($distance/LatLon::R)-sin($lat)*sin($lat2));
		
		return new LatLon(new LatRad($lat2), new LonRad($lon2));
	}
}
?>