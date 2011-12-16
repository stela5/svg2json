<?php

require_once("objects/Element.php");

class Polyline extends Element {
	private $points;
	
	public function __construct() {
		parent::__construct();
		$this->points = null;
	}
	
	public function getPoints() { return $this->points; }
	public function setPoints($val, $type="polyline") {
		$val = trim($val);
		$val = preg_replace("!\s+!", " ", $val);
		$val = str_replace(", ", ",", $val);
		$val = str_replace(" ", ",", $val);
		$a = explode(",", $val);
		if ($type === "polygon") {
			// connect last point with start point
			if (count($a) >= 4) $val.=','.$a[0].','.$a[1];
		}
		if (count($a) % 2 == 0) { 
			$val = "[".$val."]";
		} else { 
			$val = "[]";
		}
		$this->points = $val;
	}

	// bounding box
	public function getBBox() {
		$default = array('x'=>null,'y'=>null,'width'=>null,'height'=>null);
		if (empty($this->points)) return $default;   
		$anchorPoints = split(",",trim($this->points," []"));
		if (count($anchorPoints) <= 1) return $default;
		// loop through anchor points and find left-most, top-most, right-most, and bottom-most points
		$bb = null;
		for ($i = 0; $i < count($anchorPoints); $i += 2) {
			$x = $anchorPoints[$i];
			$y = $anchorPoints[($i+1)];
			if (!$bb) {
				$bb = array('left'=>$x,'top'=>$y,'right'=>$x,'bottom'=>$y);
			} else {
				if ($x < $bb['left']) $bb['left'] = $x;
				if ($y < $bb['top']) $bb['top'] = $y;
				if ($x > $bb['right']) $bb['right'] = $x;
				if ($y > $bb['bottom']) $bb['bottom'] = $y;
			}
		}
		if (empty($bb)) return $default;
		else return array('x'=>$bb['left'],'y'=>$bb['top'],'width'=>($bb['right'] - $bb['left']),'height'=>($bb['bottom'] - $bb['top']));
	}
}
?>
