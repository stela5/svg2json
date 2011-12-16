<?php

require_once("objects/Element.php");

class Group extends Element {
	private $children;
	private $childrenCount;

	public function __construct() {
		parent::__construct();
		$this->children = array();
		$this->childrenCount = 0;
	}

	public function setChildren($newChildren) { $this->children = $newChildren; }	
	public function getChildren() { return $this->children; }
	public function getChildrenCount() { return $this->childrenCount; }

	public function addChild($child) {
		$this->children[] = $child;
		$this->childrenCount += 1;
	}
}
?>
