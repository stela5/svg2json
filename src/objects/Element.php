<?php

class Element {
	private $id;
	private $xlink;
	private $styles;
	private $transforms;
	private $transform;
	private $viewport;
	private $viewbox;
	
	public function __construct() {
		$this->id = null;
		$this->xlink = null;
		$this->styles = array();
		$this->transforms = array();
		$this->transform = new Transform();
		$this->viewport = null;
		$this->viewbox = null;
	}
	
	public function setId($newId) { $this->id = $newId; }
	public function getId() { return $this->id; }
	
	public function setXLink($link) { $this->xlink = trim($link," \t\n\r#"); }
	public function getXLink() { return $this->xlink; }

	public function setStyles($newStyles) {
		$this->styles = array();
		$smallerStyles = split(";", trim($newStyles,' ;'));
		foreach($smallerStyles as $small) {
			$parts = split(":", $small);
			if (trim($parts[0]) == "stroke-width") $this->styles[trim($parts[0])] = $this->normalizeUnit(trim($parts[1]),'x');
			else $this->styles[trim($parts[0])] = trim(str_replace('"','',$parts[1]));
		}
		// group opacity (http://www.w3.org/TR/SVG/masking.html#ObjectAndGroupOpacityProperties)
		if ($this->getStyle('opacity')) {
			$this->setStyle("fill-opacity", $this->getStyle('opacity'));
			$this->setStyle("stroke-opacity", $this->getStyle('opacity'));
			$this->setStyle("stop-opacity", $this->getStyle('opacity'));
		}
	}
	public function getStyles() { return $this->styles; }

	public function setFontStyle($val) { $this->setStyle("font-style", $val); }
	public function getFontStyle() { return $this->getStyle("font-style"); }

	public function setFontVariant($val) { $this->setStyle("font-variant", $val); }
	public function getFontVariant() { return $this->getStyle("font-variant"); }

	public function setFontWeight($val) { $this->setStyle("font-weight", $val); }
	public function getFontWeight() { return $this->getStyle("font-weight"); }

	public function setFontSize($val) { $this->setStyle("font-size", $val); }
	public function getFontSize() { return $this->getStyle("font-size"); }

	public function setFontFamily($val) { $this->setStyle("font-family", $val); }
	public function getFontFamily() { return $this->getStyle("font-family"); }

	public function setViewport($viewport) {
		if (is_array($viewport) && (count($viewport) == 2)) {
			$this->viewport = array($this->normalizeUnit($viewport[0],'x'),$this->normalizeUnit($viewport[1],'y'));
		}
	}
	public function getViewport() { return $this->viewport; }

	public function setViewbox($viewbox) {
		if (is_array($viewbox) && (count($viewbox)==4)) {
			$this->viewbox = $viewbox;
		} elseif (is_string($viewbox)) {
			$v = split("[[:blank:],]+", trim($viewbox));
			if (count($v) == 4) $this->viewbox = array((0+$v[0]),(0+$v[1]),(0+$v[2]),(0+$v[3]));
		}
	}
	public function getViewbox() { return $this->viewbox; }

	public function setStyle($key, $style) {
		if (trim($key) == "stroke-width") $this->styles[trim($key)] = $this->normalizeUnit(trim($style),'x');
		else $this->styles[trim($key)] = trim(str_replace('"','',$style));
	}
	public function getStyle($key) {
		if ($this->styles && array_key_exists($key, $this->styles))
			return $this->styles[$key];
		else
			return null;
	}

	public function setTransforms($transform) {
		if (!empty($transform)) {
			$this->transforms = array();
			$this->addTransforms($transform);
			$this->combineTransforms();
		}
	}
	public function addTransforms($transform) {
		$parts = split("\\)", $transform);
		foreach ($parts as $part) {
			if (empty($part)) break;
			$transform = split("\\(", trim($part));
			switch (trim($transform[0])) {
				case "matrix":
					// get all the matrix elements, create a new transform and set them
					$elements = split("[[:blank:],]+", trim($transform[1]));
					$transformObject = new Transform();
					$transformObject->setFullMatrix($elements[0], $elements[1], $elements[2], $elements[3], $elements[4], $elements[5]);
					$this->transforms[] = $transformObject;
					break;
				case "translate":
					// get all the matrix elements, create a new translate transform and set them
					$elements = split("[[:blank:],]+", trim($transform[1]));
					$transformObject = new Transform();
					$transformObject->setTranslate($elements[0], $elements[1]);
					$this->transforms[] = $transformObject;
					break;
				case "scale":
					// get all the matrix elements, create a new scale transform and set them
					$elements = split("[[:blank:],]+", trim($transform[1]));
					$transformObject = new Transform();
					if (count($elements) > 1)
						$transformObject->setScale($elements[0], $elements[1]);
					else
						$transformObject->setScale($elements[0]);
					$this->transforms[] = $transformObject;
					break;
				case "rotate":
					// get all the matrix elements, create a new rotate transform and set them
					$elements = split("[[:blank:],]+", trim($transform[1]));
					// if there are 3 arguments, they are angle, and (x,y) coordinates of the point to rotate about
					// to handle this, we translate, rotate, and translate back
					if (count($elements) >= 3) {
						$transformObject1 = new Transform();
						$transformObject1->setTranslate(-$elements[1], -$elements[2]);
						$this->transforms[] = $transformObject1;
						
						$transformObject2 = new Transform();
						$transformObject2->setRotate($elements[0]);
						$this->transforms[] = $transformObject2;
						
						$transformObject3 = new Transform();
						$transformObject3->setTranslate($elements[1], $elements[2]);
						$this->transforms[] = $transformObject3;
					} else {
						$transformObject = new Transform();
						$transformObject->setRotate($elements[0]);
						$this->transforms[] = $transformObject;
					}
					break;
				case "skewX":
					// get all the matrix elements, create a new skew transform and set them
					$elements = split("[[:blank:],]+", trim($transform[1]));
					$transformObject = new Transform();
					$transformObject->setSkewX($elements[0]);
					$this->transforms[] = $transformObject;
					break;
				case "skewY":
					// get all the matrix elements, create a new skew transform and set them
					$elements = split("[[:blank:],]+", trim($transform[1]));
					$transformObject = new Transform();
					$transformObject->setSkewY($elements[0]);
					$this->transforms[] = $transformObject;
					break;
			} 
		}
		// combine all these into one transform
		$this->combineTransforms();
	}

	// combine all transforms into one transform and store
	private function combineTransforms() {
		$combinedTransform = new Transform();
		foreach ($this->transforms as $transform)
			$combinedTransform->multiplyBy($transform);
		$this->transform = $combinedTransform;
	}

	public function setTransform($transform) {
		$this->transform = $transform;
		$this->transforms = array();
		$this->transforms[] = $transform;
	}
	public function getTransforms() {
		return $this->transforms;  // array
	}
	public function getTransform() {
		return $this->transform;  // object
	}

	// bounding box
	public function getBBox() {
		return array('x'=>null,'y'=>null,'width'=>null,'height'=>null);
	}

	// normalize units (http://www.w3.org/TR/SVG/coords.html)
	public function normalizeUnit($unit, $type=null) {
		$u = strtolower(trim($unit));
		if (is_numeric($u)) return (0 + $u);
		if (substr($u, -2) == "px") return (0 + $u);
		if (substr($u, -2) == "pt") return (0 + $u) * 1.25;
		if (substr($u, -2) == "pc") return (0 + $u) * 15;
		if (substr($u, -2) == "mm") return (0 + $u) * 3.543307;
		if (substr($u, -2) == "cm") return (0 + $u) * 35.43307;
		if (substr($u, -2) == "in") return (0 + $u) * 90;
		if (substr($u, -2) == "em") {
			if ($this->getStyle("font-size") && (substr(strtolower(trim($this->getStyle("font-size"))),-2) !== "em")) {
				return (0 + $u) * $this->normalizeUnit($this->getStyle("font-size"));
		}	else
				return (0 + $u) * 10 * 1.25;	// Dojox.gfx default font-size is 10pt
		}
		if (substr($u, -2) == "ex") return (0 + $u) * $this->normalizeUnit("0.5em");	// CSS: 1ex can be approximated as 0.5em
		if (substr($u, -1) == "%") {
			$actualWidth = 500;	// default
			$actualHeight = 500;	// default
			if (is_array($this->getViewbox()) && count($this->getViewbox()) == 4) {
				$viewbox = $this->getViewbox();
				$actualWidth = $viewbox[2];
				$actualHeight = $viewbox[3];
			}
			elseif (is_array($this->getViewport()) && count($this->getViewport()) == 2) {
				$viewport = $this->getViewport();
				$actualWidth = $viewport[0];
				$actualHeight = $viewport[1];
			}
			if ($type == "x") return (substr($u,0,-1) / 100) * $actualWidth;
			elseif ($type == "y") return (substr($u,0,-1) / 100) * $actualHeight;
			else return (substr($u,0,-1) / 100) * (sqrt(($actualWidth * $actualWidth) + ($actualHeight * $actualHeight)) / sqrt(2));
		}
		return 0 + $u;	// catch-all
	}

	public function viewboxTransform($origin = array(0,0)) {
		$viewport = $this->viewport;
		$viewbox = $this->viewbox;
		if (!empty($viewport) && !empty($viewbox)) {
			$minX = $viewbox[0];
			$minY = $viewbox[1];
			$viewboxWidth = $viewbox[2];
			$viewboxHeight = $viewbox[3];					
			$viewportWidth = $this->normalizeUnit($viewport[0]);
			$viewportHeight = $this->normalizeUnit($viewport[1]);
			$scaleX = ($viewportWidth / $viewboxWidth);
			$scaleY = ($viewportHeight / $viewboxHeight);
			$originX = $origin[0];
			$originY = $origin[1];
			$translateX = $originX - ($viewportWidth / $viewboxWidth) * $minX;
			$translateY = $originY - ($viewportHeight / $viewboxHeight) * $minY;
			$transform = "scale(".$scaleX.",".$scaleY.") translate(".$translateX.",".$translateY.")";
			return $transform;
		} else {
			return null;
		}
	}

}
?>
