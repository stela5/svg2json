<?php

class FilterGroup {
	private $id;
	private $filters;

	public function __construct() {
		$this->id = null;
		$this->filters = array();
	}
	
	public function setId($newId) { $this->id = $newId; }
	public function getId() { return $this->id; }

	public function addFilter($filter) { $this->filters[] = $filter; }
	public function getFilters() { return $this->filters; }
}
?>
