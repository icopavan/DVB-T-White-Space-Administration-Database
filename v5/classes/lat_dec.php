<?php

class LatDec extends Cordinate {
	function __construct($dec) {
	
		if(!(-90 <= $dec && $dec <= 90)) {
			throw new Exception("Invalid latitude given, should be -90 <= deg <= 90");
		}
	
		$this->dec = $dec;
		$this->rad = deg2rad($this->dec);
		
		list($deg, $min, $sec) = $this->DECtoDMS($dec);
		parent::__construct($deg, $min, $sec);
	}
}

?>