<?php

require_once("objects/Element.php");

class Line extends Element {
	private $x1;
	private $y1;
	private $x2;
	private $y2;

	public function __construct() {
		parent::__construct();
		$this->x1 = 0;
		$this->y1 = 0;
		$this->x2 = 0;
		$this->y2 = 0;
	}

	public function getX1() { return $this->x1; }
	public function setX1($val) { $this->x1 = parent::normalizeUnit($val,'x'); }

	public function getY1() { return $this->y1; }
	public function setY1($val) { $this->y1 = parent::normalizeUnit($val,'y'); }

	public function getX2() { return $this->x2; }
	public function setX2($val) { $this->x2 = parent::normalizeUnit($val,'x'); }

	public function getY2() { return $this->y2; }
	public function setY2($val) { $this->y2 = parent::normalizeUnit($val,'y'); }
}
?>
