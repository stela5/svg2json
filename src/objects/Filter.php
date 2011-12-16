<?php

class Filter {
	private $id;
	private $type;
	private $stdDeviation;
	private $dx;
	private $dy;

	public function __construct() {
		$this->id = "";
		$this->type = "";
		$this->stdDeviation = 2.5;
		$this->dx = 4;
		$this->dy = 4;
	}
	
	public function setId($newId) { $this->id = $newId; }
	public function getId() { return $this->id; }

	public function setType($newType) { $this->type = $newType; }	
	public function getType() { return $this->type; }

	public function setStdDeviation($newStdDeviation) { if (is_numeric($newStdDeviation)) $this->stdDeviation = $newStdDeviation; }	
	public function getStdDeviation() { return $this->stdDeviation; }

	public function setDx($newDx) { if (is_numeric($newDx)) $this->dx = $newDx; }	
	public function getDx() { return $this->dx; }

	public function setDy($newDy) { if (is_numeric($newDy)) $this->dy = $newDy; }	
	public function getDy() { return $this->dy; }
}
?>
