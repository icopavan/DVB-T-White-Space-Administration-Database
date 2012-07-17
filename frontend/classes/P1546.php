<?php

/**
* Usage example:
*	$lon = new LonDec(26.4402);	
*	$lat = new LatDec(55.0000);
*
*	$p = new Point($lat, $lon);	
*	$P1546 = new P1546($p, $frequency, $percent_time, P1546::Land, $RX_antenna_height);
*	$P1546->E($p, $azimuth, $TX_antenna_height);
**/

class P1546 {
	
	const Urban = 1;
	const DenseUrban = 2;
	const Suburban = 3;
	
	const Land = 1;
	const Warmsea = 2;
	const Coldsea = 3;
		
	/* Transmitter params */
	var $f; // frequency
	var $h1; // effective height
	var $ha; // height of transmitter mast
	var $latlon; // location of transmitter
	
	/* Propogation options */
	var $p; // propogation path
	var $t; // percent time
	
	// Access to HCM DB
	var $hcm;
		
	/**
	*  @var $p  : What type of field strength calculations/tables should be used. P1546::Land, P1546::Warmsea, P1546::Coldsea
	*  @var $f  : Operating frequency.    100-2000 MHz
	*  @var $h1 : Transmitter antenna height above smooth Earth. m
	*  @var $h2 : Receiver antenna height above smooth Earth. m
	*  @var $d  : Path lengh. 1-1000 km
	*  @var $t  : Percentage time 1-50%
	**/
	function __construct($latlon, $f, $t, $p, $ha) {
	
		if($f < 100 || $f > 2000) {
			throw new Exception("Invalid frequency given, should be 100-2000 MHz");
		}
		
		if($t < 1 || $t > 50) {
			throw new Exception("Invalid percent time give, should be 1-50%");
		}
		
		$this->hcm = new HCMParser();
		
		$this->ha = $ha;
		$this->f = $f;
		$this->p = $p;
		$this->t = $t;
		$this->latlon = $latlon;
	}
	
	function E($latLon, $azimuth, $h2, $d) {
	/*
		logger("Predicting radio propogation:");	
		log_param(array(
			'frequency' => "$f MHz",
			'percent time' => "$t %",
			'TX antenna height' => "$h1 m",
			'RX antenna height' => "$h2 m",
			'distance' => "$d km"
		));
	*/
		if($d < 1 || $d > 10000) {
			throw new Exception("Invalid distance give, should be 1-10000 km");
		}
		
		$distance = array(1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12, 13,
						14, 15, 16, 17, 18, 19, 20, 25, 30, 35, 40, 45, 50,
						55, 60, 65, 70, 75, 80, 85, 90, 95, 100, 110, 120, 130,
						140, 150, 160, 170, 180, 190, 200, 225, 250, 275, 300, 325, 350,
						375, 400, 425, 450, 475, 500, 525, 550, 575, 600, 625, 650, 675,
						700, 725, 750, 775, 800, 825, 850, 875, 900, 925, 950, 975, 1000);
		$height = array(10, 20, 37.5, 75, 150, 300, 600, 1200);
		$freq = array(100, 600, 2000);
		$time = array(1, 10, 50);
		
		//logger("Searching closest values:");
		
		$this->h1 = $this->effectiveHeight($azimuth, $d);
		
		list($t_sup, $t_inf) = closest($time, $this->t);
		list($f_sup, $f_inf) = closest($freq, $this->f);
		list($d_sup, $d_inf) = closest($distance, $d);
		list($h_sup, $h_inf) = closest($height, $this->h1);
		
		/*
		echo "<table border='1'>";
		echo "<tr><th>Name</th><th>Value</th><th>Sup</th><th>Inf</th>";
		echo "<tr><td>Frequency</td><td>{$f}</td><td>{$f_sup}</td><td>{$f_inf}</td></tr>";
		echo "<tr><td>Time</td><td>{$t}</td><td>{$t_sup}</td><td>{$t_inf}</td></tr>";
		echo "<tr><td>H1</td><td>{$h1}</td><td>{$h_sup}</td><td>{$h_inf}</td></tr>";
		echo "<tr><td>Distance</td><td>{$d}</td><td>{$d_sup}</td><td>{$d_inf}</td></tr>";
		echo "</table>";
		*/
		
		$p = P1546::Land;
		
		$E_sup = $this->tabValue($f_sup, $t_sup, $p, $h_sup, $d_sup);
		
		$E_inf = $this->tabValue($f_sup, $t_sup, $p, $h_sup, $d_inf);
		
		$E = $this->interpolate($d, $E_inf, $E_sup, $d_inf, $d_sup);
		
		return $E; //$this->TCACorrection($latLon, $azimuth, $h2, $d);
	}
		
	/**
	* @Tested http://www.itu.int/SRTM3/
	* In telecommunication, the term effective height can refer to the height of the center of 
	* radiation of an antenna above the effective ground level P.1546, 3 annex. 
	* Returns effective height of transmitter.
	* @var int $azimuth
	* @var double $d distance between transmitter and receiver antennas
	* @return double effective height h1
	**/
	public function effectiveHeight($azimuth, $d) {
	
		#1 Get elevation level of ground where antenna is located
		$h = $this->hcm->getElevation($this->latlon->lat, $this->latlon->lon);
	
		#2 Calculate avarage ground level around antenna
		$tmp = 0;
		
		// For shorter distance than 15km
		if($d < 15) {
			$iterations_count = 0;
			for($j = 0.2 * $d; $j < $d ; $j += 0.25 ) {
				$iterations_count++;
				$p = $this->latlon->calculateOffset($j, $azimuth);
				$tmp += $this->hcm->getElevation($p->lat, $p->lon);
				$avg_h =  $tmp/$iterations_count;
			}
		}
		// For longer distance than 15km
		else {
			for($j = 3; $j <= 15; $j += 0.25 ) {
				$p = $this->latlon->calculateOffset($j, $azimuth);
				$tmp += $this->hcm->getElevation($p->lat, $p->lon);
				$avg_h =  $tmp/49;
			}
		}
		return round((($h-$avg_h) + $this->ha), 1);
	}
	
	/**
	* Calculates terrain clearance angle TCA from receiver site up to 16km, but not going beyong transmitter.
	* Calculating only positives values
	* @param $p object Point. Defines cordinates of receiver 
	* @param $azimuth int degrees
	* @param $h2 double height of receiver
	* @param $d int distance between transmitter and receiver
	* @return int clerance angle in degrees
	*/
	public function clearanceAngle($p, $azimuth, $h2, $d) {
	
		$hcm = new HCMParser();
		
		// Getting elevation level of ground where antenna is located
		$h = $h2 + $hcm->getElevation($p->lat, $p->lon);

		$highest = 0;
		
		if($d > 16) {
			$d = 16;
		}
		
		// Step 0.25km
		for($j = 0.25; $j <= $d; $j += 0.25 ) {
			$point = $p->calculateOffset($j, $azimuth);
			
			$ground_h = $hcm->getElevation($point->lat, $point->lon);
			if($ground_h > $h) {
				$highest = $ground_h;
			}
		}
		
		// In case visibility is clear
		if($highest == 0 ) 
			$tca = 0;
		else 
			$tca = round(rad2deg(atan(($highest-$h)/16)), 1);
		
		return $tca;
	}
	
	/**
	* @TESTED P.1546 28 graph
	* Calculates correction based on clearance angle on receiver site 
	* Calculates terrain clearance angle from receiver site up to 16km, but not going beyong transmitter.
	* @param $p object Point. Defines cordinates of receiver
	* @param $azimuth int degrees
	* @param $h2 double height of receiver
	* @param $d double distance between transmitter and receiver
	* @param $f double frequency MHz 
	* @return double correction value in dB
	*/
	public function TCACorrection($p, $azimuth, $h2, $d) {
	
		$tca = $this->clearanceAngle($p, $azimuth, $h2, $d);
		
		// tca should be limited such that it is not less than +0.55° or more than +40.0°.
		if($tca < 0.55 ) {
			$tca = 0.55;
		}
		if($tca > 40) {
			$tca = 40;
		}
		
		return round($this->J(0.036*sqrt($this->f*10^6)) - $this->J(0.065*$tca*sqrt($this->f*10^6)),1);
	}
	
	/**
	*  @var $f : frequency (MHz)
	*  @var $h1 : first antenna height above smooth Earth (m)
	*  @var $h2 : second antenna height above smooth Earth (m)
	**/
	public function fresnelZone($f, $h1, $h2) {
		if($h1 < 0) {
			$h1 = 0;
		}
		$D06 = (0.0000389*$f*$h1*$h2)/(4.1*(sqrt($h1)+sqrt($h2)));
		
		if($D06 > 0.001) {
			return $D06;
		}
		else {
			return 0.001;
		}
	}
	
	public function receiverAntennaCorrection($h1, $h2, $f, $d, $environment) {
		
		switch ($location) {
			case P1546::Urban:
				$R = 20;
			case P1546::DenseUrban:
				$R = 30;
			case P1546::Suburban:
				$R = 10;
		}
		
		$R_ = ((1000*$d*$R)- 15 * $h1)/( (1000*$d) -15);
			
		// The value of R' must be limited if necessary such that it is not less than 1 m.
		if($R_ < 1) {
			$R_ = 1;
		}
		
		if($environment == P1546::URBAN) {
		
			$K_h2 = 3.2 + 6.2*log($f);
		
			if($h2 < $R_) {
				
				$h_dif = $R_ - $h2;
				$tera_clut = arctan($h_dif/27);
				$K_nu = 0.0108 * sqrt($f);
				
				$v = $K_nu * sqrt($h_dif * $tera_clut);
				
				$c = 6.03 - $this->J($v);
			}
			else {
				$c = $K_h2 * log($h2/$R_);
			}
			
			//In cases in an urban environment where R' is less than 10 m, the correction given by equation (27)
			//should be reduced by Kh2 log(10/R?).
			if($R_ < 10) {
				$c -= $K_h2 * log(10/$R_);
			}
		}
	}
	
	
	/**
	* Returns field strength value from tab_values database table
	* @var int $f frequency MHz 
	* @var int $t percent time 
	* @var string $p path type
	* @var int $d distance km
	* @var double $h antenna height m
	* @return double field strength
	**/
	function tabValue($f, $t, $p, $h, $d) {
		
		switch($p) {
			case P1546::Land:
				$p = 'land';
			break;
			case P1546::Warmsea:
				$p = 'warmsea';
			break;
			case P1546::Coldsea:
				$p = 'coldsea';
			break;
		}
		
		$sql = mysql_query("
			SELECT field_strength FROM tab_values
			WHERE path = '$p' AND
				  time = $t AND 
				  frequency = $f AND
				  distance = $d AND
				  height = $h
		");
		
		$row = mysql_fetch_array($sql);
		return $row[0];
	}
	
	public function interpolate($val, $E_inf, $E_sup, $inf, $sup) {
		return $E_inf+($E_sup-$E_inf)*log10($val/$inf)/log10($sup/$inf);
	}
	
	public function findStrength($t_sup, $f_sup, $h_sup, $d_sup) {
	
		list($t_sup, $t_inf) = $this->closest($time, $t);
		list($f_sup, $f_inf) = $this->closest($freq, $f);
		list($d_sup, $d_inf) = $this->closest($distance, $d);
		list($h_sup, $h_inf) = $this->closest($height, $h1);
		
		/* Distance */
		$E_sup = $this->findTabValue($t_sup, $f_sup, $h_sup, $d_sup);
	}
	
	public function strength2loss($str, $f) {
		return 139.3 - $str + 20*log10($f*10^6); 
	}
	
	/*
	*   @Tested P.1546 Table 3
	*	An approximation to the inverse complementary cumulative normal distribution function
	*/
	public function Qi($x) {
		if($x <= 0.5) {
			return $this->T($x) - $this->G($x);
		}
		else {
			return -($this->T(1-$x)-$this->G(1-$x));
		}
	}
	
	public function T($x) {
		return sqrt(-2*log($x));
	}
	
	public function G($x) {
		$c0 = 2.515517;
		$c1 = 0.802853;
		$c2 = 0.010328;
		$d1 = 1.432788;
		$d2 = 0.189269;
		$d3 = 0.001308;
		return ((($c2*$this->T($x)+$c1)*$this->T($x))+$c0)/( (($d3*$this->T($x)+$d2)*$this->T($x)+$d1)*$this->T($x)+1 );
	}
	
	public function J($v) {
		return 6.9+20*log10(sqrt(($v-0.1)^2+1)+$v-0.1);
	}	
}
?>