<?php

require_once("objects/Element.php");

class Rectangle extends Element {
	private $x;
	private $y;
	private $height;
	private $width;
	private $rx;
	private $ry;

	public function __construct() {
		parent::__construct();
		$this->x = 0;
		$this->y = 0;
		$this->height = 0;
		$this->width = 0;
		$this->rx = null;
		$this->ry = null;
	}

	public function getX() { return $this->x; }
	public function setX($val) { $this->x = parent::normalizeUnit($val,'x'); }

	public function getY() { return $this->y; }
	public function setY($val) { $this->y = parent::normalizeUnit($val,'y'); }

	public function getHeight() { return $this->height; }
	public function setHeight($val) { $this->height = parent::normalizeUnit($val,'y'); }

	public function getWidth() { return $this->width; }
	public function setWidth($val) { $this->width = parent::normalizeUnit($val,'x'); }

	public function getRx() { return ($this->rx != null)?$this->rx:0; }
	public function setRx($val) { $this->rx = parent::normalizeUnit($val,'x'); }

	public function getRy() { return ($this->ry != null)?$this->ry:0; }
	public function setRy($val) { $this->ry = parent::normalizeUnit($val,'y'); }

	public function getR() {
		if ($this->rx != null && $this->ry != null) return ($this->rx + $this->ry) / 2;
		else if ($this->rx != null) return $this->rx;
		else if ($this->ry != null) return $this->ry;
		else return 0;
	}
	public function setR($val) {
		$this->setRx($val);
		$this->setRy($val);
	}

	// bounding box
	public function getBBox() {
		return array('x'=>$this->x,'y'=>$this->y,'width'=>$this->width,'height'=>$this->height);
	}
}
?>
