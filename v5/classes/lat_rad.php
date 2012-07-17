<?php

class LatRad extends Cordinate {

	function __construct($rad) {
		$this->rad = $rad;
		$this->dec = rad2deg($this->rad);
		
		list($deg, $min, $sec) = $this->DECtoDMS($this->dec);
		parent::__construct($deg, $min, $sec);
	}
}

?>