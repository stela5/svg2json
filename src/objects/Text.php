<?php

require_once("objects/Element.php");

class Text extends Element {
	private $x;
	private $y;
	private $text;
	private $textpath;

	public function __construct() {
		parent::__construct();
		$this->x = 0;
		$this->y = 0;
		$this->text = null;
		$this->textpath = null;
	}

	public function getX() { return $this->x; }
	public function setX($val) { $this->x = parent::normalizeUnit($val,'x'); }

	public function getY() { return $this->y; }
	public function setY($val) { $this->y = parent::normalizeUnit($val,'y'); }

	public function getText() { return $this->text; }
	public function setText($val) {
		$val = trim(str_replace(array("\r\n", "\n", "\r"),"",$val));
		$val = str_replace('"','\'',$val);
		$this->text = $val; 
	}

	public function setTextAnchor($anchor) { $this->setStyle("text-anchor", $anchor); }
	public function getTextAnchor() {
		$anchor = $this->getStyle("text-anchor");
		if ($anchor) return $anchor;
		else return "start";
	}

	public function setTextDecoration($decoration) { $this->setStyle("text-decoration", $decoration); }
	public function getTextDecoration() {
		$decoration = $this->getStyle("text-decoration");
		if ($decoration) return $decoration;
		else return "none";
	}

	public function getTextpath() { return $this->textpath; }
	public function setTextpath($val) { $this->textpath = $val; }
}
?>
