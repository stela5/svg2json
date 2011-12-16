<?php

require_once("objects/Gradient.php");

class RadialGradient extends Gradient {
	private $cx;
	private $cy;
	private $r;
	private $fx;
	private $fy;

	public function __construct() {
		parent::__construct();
		$this->cx = null;
		$this->cy = null;
		$this->r = null;
		$this->fx = null;
		$this->fy = null;
	}
	
	public function setCx($val) { $this->cx = $val; }
	public function getCx() { return $this->cx; }

	public function setCy($val) { $this->cy = $val; }
	public function getCy() { return $this->cy; }

	public function setR($val) { $this->r = $val; }
	public function getR() { return $this->r; }

	public function setFx($val) { $this->fx = $val; }
	public function getFx() { return $this->fx; }

	public function setFy($val) { $this->fy = $val; }
	public function getFy() { return $this->fy; }
	
}
?>
