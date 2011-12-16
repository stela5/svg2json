<?php

require_once("objects/Gradient.php");

class LinearGradient extends Gradient {
	private $x1;
	private $y1;
	private $x2;
	private $y2;

	public function __construct() {
		parent::__construct();
		$this->x1 = null;
		$this->y1 = null;
		$this->x2 = null;
		$this->y2 = null;
	}
	
	public function setX1($val) { $this->x1 = $val; }
	public function getX1() { return $this->x1; }

	public function setY1($val) { $this->y1 = $val; }
	public function getY1() { return $this->y1; }

	public function setX2($val) { $this->x2 = $val; }
	public function getX2() { return $this->x2; }

	public function setY2($val) { $this->y2 = $val; }
	public function getY2() { return $this->y2; }

}
?>
