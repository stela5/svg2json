<?php

require_once("objects/Element.php");

class Circle extends Element {
	private $cx;
	private $cy;
	private $r;
	
	public function __construct() {
		parent::__construct();
		$this->cx = 0;
		$this->cy = 0;
		$this->r = 0;
	}

	public function getCx() { return $this->cx; }
	public function getCy() { return $this->cy; }
	public function getR() { return $this->r; }
	public function setCx($val) { $this->cx = parent::normalizeUnit($val,'x'); }
	public function setCy($val) { $this->cy = parent::normalizeUnit($val,'y'); }
	public function setR($val) { $this->r = parent::normalizeUnit($val); }

	// bounding box
	public function getBBox() {
		return array('x'=>($this->cx - $this->r),'y'=>($this->cy - $this->r),'width'=>($this->r * 2),'height'=>($this->r * 2));
	}
}
?>
