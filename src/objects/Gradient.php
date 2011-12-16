<?php

require_once("objects/Element.php");

class Gradient extends Element{
	private $stops;
	
	public function __construct() {
		parent::__construct();
		$this->stops = array();
	}
	
	public function addStop($stop) { $this->stops[] = $stop; }
	public function setStops($stops) { $this->stops = $stops; }
	public function getStops() { return $this->stops; }
}
?>
