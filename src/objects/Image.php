<?php

require_once("objects/Element.php");

class Image extends Element {
	private $x;
	private $y;
	private $height;
	private $width;

	public function __construct() {
		parent::__construct();
		$this->x = 0;
		$this->y = 0;
		$this->height = 0;
		$this->width = 0;;
	}

	public function getX() { return $this->x; }
	public function setX($val) { $this->x = parent::normalizeUnit($val,'x'); }

	public function getY() { return $this->y; }
	public function setY($val) { $this->y = parent::normalizeUnit($val,'y'); }

	public function getHeight() { return $this->height; }
	public function setHeight($val) { $this->height = parent::normalizeUnit($val,'y'); }

	public function getWidth() { return $this->width; }
	public function setWidth($val) { $this->width = parent::normalizeUnit($val,'x'); }

	// bounding box
	public function getBBox() {
		return array('x'=>$this->x,'y'=>$this->y,'width'=>$this->width,'height'=>$this->height);
	}
}
?>
