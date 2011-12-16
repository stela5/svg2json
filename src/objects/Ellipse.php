<?php

require_once("objects/Element.php");

class Ellipse extends Element {
	private $cx;
	private $cy;
	private $rx;
	private $ry;
	
	public function __construct() {
		parent::__construct();
		$this->cx = 0;
		$this->cy = 0;
		$this->rx = 0;
		$this->ry = 0;
	}

	public function getCx() { return $this->cx; }
	public function setCx($val) { $this->cx = parent::normalizeUnit($val,'x'); }

	public function getCy() { return $this->cy; }
	public function setCy($val) { $this->cy = parent::normalizeUnit($val,'y'); }

	public function getRx() { return $this->rx; }
	public function setRx($val) { $this->rx = parent::normalizeUnit($val,'x'); }

	public function getRy() { return $this->ry; }
	public function setRy($val) { $this->ry = parent::normalizeUnit($val,'y'); }

	// bounding box
	public function getBBox() {
		return array('x'=>($this->cx - $this->rx),'y'=>($this->cy - $this->ry),'width'=>($this->rx * 2),'height'=>($this->ry * 2));
	}
}
?>
