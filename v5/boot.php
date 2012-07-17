<?php
	
	define('DIR', 'C:/wamp/www/baigiamasis/v4/');
	define('DS','/');
	define('DOT', '.');
	define('TOPO_DB', DIR.'data/TOPO');
	
	
	mysql_connect("localhost", "root", "");
	mysql_select_db("baigiamasis_v4");
	mysql_query("SET NAMES utf8");
	mysql_query("SET charset utf8");
	
	set_time_limit(0);
	function __autoload($class_name) {
		$path = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class_name));
		require_once 'classes/'.$path.".php";
	}

	/*
	 * Parameters: 
     *   $a - The sort array.
     *   $first - First index of the array to be searched (inclusive).
     *   $last - Last index of the array to be searched (exclusive).
     *   $key - The key to be searched for.
     *   $compare - A user defined function for comparison. Same definition as the one in usort
     *
     * Return:
     *   index of the search key if found, otherwise return (-insert_index - 1). 
     *   insert_index is the index of smallest element that is greater than $key or sizeof($a) if $key
     *   is larger than all elements in the array.
     */
    function binary_search(array $a, $first, $last, $key, $compare) {
        $lo = $first; 
        $hi = $last - 1;

        while ($lo <= $hi) {
            $mid = (int)(($hi - $lo) / 2) + $lo;
            $cmp = call_user_func($compare, $a[$mid], $key);
			
            if ($cmp < 0) {
                $lo = $mid + 1;
            } elseif ($cmp > 0) {
                $hi = $mid - 1;
            } else {
                return $mid;
            }
        }
        return $lo-1;
    }
	
    function cmp($a, $b) {
        return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
    }
	
	function closest($a, $key) {
		$idx = binary_search($a, 0, sizeof($a), $key, 'cmp');
		return array(isset($a[$idx]) ? $a[$idx] : 0, isset($a[$idx+1]) ? $a[$idx+1] : 0);
	}

	function logger($str) {
		echo $str;
	}
	
	function log_param($array) {
		$a = "<table border='1'>";
		foreach($array as $key => $value) {
			$a .= "<tr>";
			$a .= "<td>{$key}</td>";
			$a .= "<td>{$value}</td>";
			$a .= "</tr>";
		}
		$a .= "</table>";
		echo $a;
	}
	
	// array('param1
	function log_param2($array) {
	
	}
	
?>