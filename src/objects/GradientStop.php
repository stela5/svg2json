<?php

require_once("objects/Element.php");

class GradientStop extends Element{
	private $offset;
	
	public function __construct() {
		parent::__construct();
		$this->offset = 0;
	}
	
	public function setOffset($offset) {
		if (substr($offset,-1) == "%") $offset = (substr($offset,0,-1) / 100);
		$this->offset = parent::normalizeUnit($offset,'x');
	}
	public function getOffset() { return $this->offset; }

	public function setColor($color) { $this->setStyle("stop-color", $color); }
	public function getColor() {
		if ($this->getStyle("stop-color")) return $this->getStyle("stop-color");
		else return "black";
	}
	
	public function setOpacity($opacity) { $this->setStyle("stop-opacity", $opacity); }
	public function getOpacity() {
		if (($this->getStyle("stop-opacity")>=0) && ($this->getStyle("stop-opacity")<=1)) return $this->getStyle("stop-opacity");
		else return 1;
	}
}
?>
