<?php

class LonDec extends Cordinate {
	function __construct($dec) {
	
		if(!(-180 <= $dec && $dec <= 180)) {
			throw new Exception("Invalid longitude given");
		}
		
		$this->dec = $dec;
		$this->rad = deg2rad($this->dec);
		
		list($deg, $min, $sec) = $this->DECtoDMS($dec);
		parent::__construct($deg, $min, $sec);
	}
}
	
?>