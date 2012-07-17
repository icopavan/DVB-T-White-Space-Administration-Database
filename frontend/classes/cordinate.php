<?php
/**
 *		Degree:
 *		-------------
 *		Longitude:Y
 *		E [ 000-179 ] +
 *		W [ 001-180 ] -
 *		
 *		Latitude:X
 *		N [00 - 89] + 
 *		S [01 - 90] -
 *		
 *		Minute, Second:
 *		------------
 *		[00-59]
 *
 */

class Cordinate {
		
	public $deg;
	public $min;
	public $sec;
		
	public $rad;
	public $dec;
		
	function __construct($deg, $min, $sec) {
		$this->deg = $deg;
		$this->min = $min;
		$this->sec = $sec;			
	}
		
	/**
	*	Converts DMS ( Degrees / minutes / seconds ) 
	*	to decimal format longitude / latitude
	**/
	public function DMStoDEC($deg, $min, $sec) {
		return $deg + ((($min*60)+($sec))/3600);
	}

	/**
	* Converts decimal longitude / latitude to DMS
	* ( Degrees / minutes / seconds ) 
	*
	**/
	public function DECtoDMS($dec) {
		// Converting float to integer
		$deg = (int) $dec;
		$rem = $dec-$deg;
		$rem *= 3600;
		$min = floor($rem / 60);
		$sec = $rem - ($min*60);
		return array($deg, round($min), round($sec));
	}				
}
?>