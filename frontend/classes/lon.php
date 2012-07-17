<?php

class Lon extends Cordinate {

	function __construct($deg, $min, $sec) {
		
		if(!(-180 <= $deg && $deg <= 180)) {
			throw new Exception("Invalid longitude degree value $deg given, should be -180 <= deg <= 180");
		}
		
		if(!(0 <= $min && $min < 60)) {
			throw new Exception("Invalid longitude minute value $min given, should be 0 <= min <= 59");
		}
		
		if(!(0 <= $sec && $sec <= 60)) {
			throw new Exception("Invalid longitude second value $sec given, should be 0 <= sec <= 60");
		}
		
		$this->dec = $this->DMStoDEC($deg, $min, $sec);
		$this->rad = deg2rad($this->dec);
		
		parent::__construct($deg, $min, $sec);
	}
}

?>