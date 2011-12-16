<?php

require_once("objects/SVG.php");
require_once("objects/Path.php");
require_once("objects/Rectangle.php");
require_once("objects/Circle.php");
require_once("objects/Ellipse.php");
require_once("objects/Line.php");
require_once("objects/Polyline.php");
require_once("objects/Line.php");
require_once("objects/Text.php");
require_once("objects/Gradient.php");
require_once("objects/RadialGradient.php");
require_once("objects/LinearGradient.php");
require_once("objects/GradientStop.php");
require_once("objects/FilterGroup.php");
require_once("objects/Filter.php");
require_once("objects/Group.php");
require_once("objects/Transform.php");
require_once("objects/Image.php");

class Parser {
	private $elementStack;
	private $svg;
	private $characterDataStack;
	private $currentFilterGroup;
	private $currentGroup;
	private $groupStack;
	private $inDefs;
	private $inPattern;
	private $inSymbol;
	private $isOutermost;
	
	public function parse($svgString) {
		// reset the stack of elements
		unset($this->elementStack);
		$this->elementStack = array();

		// reset the stack of groups
		unset($this->groupStack);
		$this->groupStack = array();

		// reset the stack of character data (this is used for text nodes)
		unset($this->characterDataStack);
		$this->characterDataStack = array();

		// reset the current filter group
		$this->currentFilterGroup = null;

		// reset the current group
		$this->currentGroup = null;

		// reset the booleans
		$this->inDefs = false;
		$this->inPattern = false;
		$this->inSymbol = false;
		$this->isOutermost = true;

		// reset svg object
		$this->svg = new SVG($svgString);

		// verify provided xml is valid
		$validate = $this->validateXml($svgString);

		if ($validate === false) {		
			// return the svg object			
			return $this->svg;
		} else {
			// parse the validated XML
			$parser = xml_parser_create();
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);	// skip extra white-space
		
			// set the handlers
			xml_set_element_handler($parser, array($this, "startElementHandler"), array($this,"endElementHandler"));

			xml_set_character_data_handler($parser, array($this, "characterDataHandler"));

			// parse the xml
			xml_parse($parser, $validate);

			// release the parser
			xml_parser_free($parser);

			// add the svg group data
			$this->svg->setGroupData($this->currentGroup);

			// return the svg object			
			return $this->svg;
		}
	}

	// parser helper function for node begin (i.e. <circle>)
	private function startElementHandler($parser, $name, $attribs) {
		// process node begin
		switch ($name) {
			case "SVG":
				// create and track group objects
				$parent = null;
				$viewport = null;
				$viewbox = null;
				if (!empty($this->currentGroup)) {
					$this->groupStack[] = $this->currentGroup;
					$viewport = $this->currentGroup->getViewport();
					$viewbox = $this->currentGroup->getViewbox();
				}
				if (!empty($this->currentGroup)) $parent = clone $this->currentGroup;
				$this->currentGroup = new Group();
				if ($parent) $this->applyInheritance($this->currentGroup, $parent);
				if ($viewport) $this->currentGroup->setViewport($viewport);
				if ($viewbox) $this->currentGroup->setViewbox($viewbox);
				if (array_key_exists("ID", $attribs))
					$this->currentGroup->setId($attribs["ID"]);
				else
					$this->currentGroup->setId("g_".mt_rand());
				if (array_key_exists("STYLE", $attribs))
					$this->currentGroup->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($this->currentGroup,$attribs);
				if (array_key_exists("TRANSFORM", $attribs))
					$this->currentGroup->setTransforms($attribs["TRANSFORM"]);
				if (array_key_exists("VIEWBOX", $attribs)) {
					$this->currentGroup->setViewbox($attribs["VIEWBOX"]);
					$this->currentGroup->setTransforms($this->currentGroup->viewboxTransform());
				}
				if (array_key_exists("WIDTH", $attribs) && array_key_exists("HEIGHT", $attribs))
					$this->currentGroup->setViewport(array($attribs["WIDTH"],$attribs["HEIGHT"]));
				$this->isOutermost = false;
				break;
			case "G":
				// create and track group objects
				$parent = null;
				$viewport = null;
				$viewbox = null;
				if (!empty($this->currentGroup)) {
					$this->groupStack[] = $this->currentGroup;
					$viewport = $this->currentGroup->getViewport();
					$viewbox = $this->currentGroup->getViewbox();
				}
				if (!empty($this->currentGroup)) $parent = clone $this->currentGroup;
				$this->currentGroup = new Group();
				if ($parent) $this->applyInheritance($this->currentGroup, $parent);
				if ($viewport) $this->currentGroup->setViewport($viewport);
				if ($viewbox) $this->currentGroup->setViewbox($viewbox);
				if (array_key_exists("ID", $attribs))
					$this->currentGroup->setId($attribs["ID"]);
				else
					$this->currentGroup->setId("g_".mt_rand());
				if (array_key_exists("STYLE", $attribs))
					$this->currentGroup->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($this->currentGroup,$attribs);
				if (array_key_exists("TRANSFORM", $attribs))
					$this->currentGroup->setTransforms($attribs["TRANSFORM"]);
				break;
			case "USE":
				// http://www.w3.org/TR/SVG/struct.html#UseElement
				// TODO: determine how to implement USE width and height optional values
				$this->groupStack[] = $this->currentGroup;
				$parent = clone $this->currentGroup;
				$viewport = $this->currentGroup->getViewport();
				$viewbox = $this->currentGroup->getViewbox();
				$this->currentGroup = new Group();
				$this->applyInheritance($this->currentGroup, $parent);
				$this->currentGroup->setViewport($viewport);
				$this->currentGroup->setViewbox($viewbox);
				if (array_key_exists("ID", $attribs))
					$this->currentGroup->setId($attribs["ID"]);
				else
					$this->currentGroup->setId("g_".mt_rand());
				if (array_key_exists("X", $attribs) && array_key_exists("Y", $attribs))
					$this->currentGroup->setTransforms("translate(".$attribs["X"].",".$attribs["Y"].")");
				if (array_key_exists("XLINK:HREF", $attribs)) {
					$defs = $this->svg->getDefs();
					foreach($defs as $def) {
						if ($def["id"] == trim($attribs["XLINK:HREF"]," \t\n\r#")) {
							if ($def["value"] && is_object($def["value"])) {
								$obj = clone $def["value"];
								if (array_key_exists("STYLE", $attribs))
									$obj->setStyles($attribs["STYLE"]);
								$this->applyStyleAttributes($obj,$attribs);
								$this->currentGroup->addChild($obj);
							}
							break;
						}
					}
				}
				break;
			case "DEFS":
				$this->inDefs = true;
				break;
			case "SYMBOL":
				$this->inSymbol = true;
				$element = new Element();
				if (array_key_exists("WIDTH", $attribs) && array_key_exists("HEIGHT", $attribs))
					$element->setViewport(array($attribs["WIDTH"],$attribs["HEIGHT"]));
				else
					$element->setViewport($this->currentGroup->getViewport());
				if (array_key_exists("ID", $attribs))
					$element->setId($attribs["ID"]);
				if (array_key_exists("VIEWBOX", $attribs)) {
					$element->setViewbox($attribs["VIEWBOX"]);
					$element->setTransforms($element->viewboxTransform());
				}
				$this->elementStack[] = $element;
				break;
			case "PATH":
				$path = new Path();
				$path->setViewport($this->currentGroup->getViewport());
				$path->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$path->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$path->setId($attribs["ID"]);
				if (array_key_exists("D", $attribs))
					$path->setPath($attribs["D"]);
				if (array_key_exists("STYLE", $attribs))
					$path->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($path,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$path->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$path->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $path;
				break;
			case "RECT":
				$rect = new Rectangle();
				$rect->setViewport($this->currentGroup->getViewport());
				$rect->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$rect->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$rect->setId($attribs["ID"]);
				if (array_key_exists("X", $attribs))
					$rect->setX($attribs["X"]);
				if (array_key_exists("Y", $attribs))
					$rect->setY($attribs["Y"]);
				if (array_key_exists("HEIGHT", $attribs))
					$rect->setHeight($attribs["HEIGHT"]);
				if (array_key_exists("WIDTH", $attribs))
					$rect->setWidth($attribs["WIDTH"]);
				if (array_key_exists("RX", $attribs))
					$rect->setRx($attribs["RX"]);
				if (array_key_exists("RY", $attribs))
					$rect->setRy($attribs["RY"]);
				if (array_key_exists("STYLE", $attribs))
					$rect->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($rect,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$rect->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$rect->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $rect;
				break;
			case "CIRCLE":
				$circle = new Circle();
				$circle->setViewport($this->currentGroup->getViewport());
				$circle->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$circle->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$circle->setId($attribs["ID"]);
				if (array_key_exists("CX", $attribs))
					$circle->setCx($attribs["CX"]);
				if (array_key_exists("CY", $attribs))
					$circle->setCy($attribs["CY"]);
				if (array_key_exists("R", $attribs))
					$circle->setR($attribs["R"]);
				if (array_key_exists("STYLE", $attribs))
					$circle->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($circle,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$circle->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$circle->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $circle;
				break;
			case "ELLIPSE":
				$ellipse = new Ellipse();
				$ellipse->setViewport($this->currentGroup->getViewport());
				$ellipse->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$ellipse->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$ellipse->setId($attribs["ID"]);
				if (array_key_exists("CX", $attribs))
					$ellipse->setCx($attribs["CX"]);
				if (array_key_exists("CY", $attribs))
					$ellipse->setCy($attribs["CY"]);
				if (array_key_exists("RX", $attribs))
					$ellipse->setRx($attribs["RX"]);
				if (array_key_exists("RY", $attribs))
					$ellipse->setRy($attribs["RY"]);
				if (array_key_exists("STYLE", $attribs))
					$ellipse->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($ellipse,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$ellipse->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$ellipse->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $ellipse;
				break;
			case "LINE":
				$line = new Line();
				$line->setViewport($this->currentGroup->getViewport());
				$line->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$line->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$line->setId($attribs["ID"]);
				if (array_key_exists("X1", $attribs))
					$line->setX1($attribs["X1"]);
				if (array_key_exists("Y1", $attribs))
					$line->setY1($attribs["Y1"]);
				if (array_key_exists("X2", $attribs))
					$line->setX2($attribs["X2"]);
				if (array_key_exists("Y2", $attribs))
					$line->setY2($attribs["Y2"]);
				if (array_key_exists("STYLE", $attribs))
					$line->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($line,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$line->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$line->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $line;
				break;
			case "POLYLINE":
				$polyline = new Polyline();
				$polyline->setViewport($this->currentGroup->getViewport());
				$polyline->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$polyline->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$polyline->setId($attribs["ID"]);
				if (array_key_exists("POINTS", $attribs))
					$polyline->setPoints($attribs["POINTS"], "polyline");
				if (array_key_exists("STYLE", $attribs))
					$polyline->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($polyline,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$polyline->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$polyline->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $polyline;
				break;
			case "POLYGON":
				$polygon = new Polyline();
				$polygon->setViewport($this->currentGroup->getViewport());
				$polygon->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$polygon->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$polygon->setId($attribs["ID"]);
				if (array_key_exists("POINTS", $attribs))
					$polygon->setPoints($attribs["POINTS"], "polygon");
				if (array_key_exists("STYLE", $attribs))
					$polygon->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($polygon,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$polygon->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$polygon->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $polygon;
				break;
			case "TEXT":
				$text = new Text();
				$text->setViewport($this->currentGroup->getViewport());
				$text->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$text->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$text->setId($attribs["ID"]);
				if (array_key_exists("X", $attribs))
					$text->setX($attribs["X"]);
				if (array_key_exists("Y", $attribs))
					$text->setY($attribs["Y"]);
				if (array_key_exists("STYLE", $attribs))
					$text->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($text,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$text->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TEXT-ANCHOR", $attribs))
					$text->setTextAnchor($attribs["TEXT-ANCHOR"]);
				if (array_key_exists("TEXT-DECORATION", $attribs))
					$text->setTextDecoration($attribs["TEXT-DECORATION"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$text->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $text;
				break;
			case "TSPAN":
				// Dojox.gfx doesn't support tspan so simply append to parent text
				$lastText = array_pop($this->characterDataStack);
				if ((trim($lastText) != '') && ($this->elementStack[count($this->elementStack)-1] instanceof Text)) { 
					$t = array_pop($this->elementStack);
					$text = $t->getText().' '.trim($lastText);
					$t->setText($text);
					$this->elementStack[] = $t;
				}
				break;
			case "TREF":
				$element = new Element();
				$element->setViewport($this->currentGroup->getViewport());
				$element->setViewbox($this->currentGroup->getViewbox());
				if (array_key_exists("XLINK:HREF", $attribs))
					$element->setXLink($attribs["XLINK:HREF"]);
				$this->elementStack[] = $element;
				break;
			case "TEXTPATH":
				if (array_key_exists("XLINK:HREF", $attribs) && ($this->elementStack[count($this->elementStack)-1] instanceof Text)) {
					$t = array_pop($this->elementStack);
					$t->setTextpath($attribs["XLINK:HREF"]);
					$this->elementStack[] = $t;
				}
				break;
			case "PATTERN":
				$this->inPattern = true;
				$image = new Image();	// note: Dojox.gfx only supports image patterns
				$image->setViewport($this->currentGroup->getViewport());
				$image->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$image->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$image->setId($attribs["ID"]);
				if (array_key_exists("X", $attribs))
					$image->setX($attribs["X"]);
				if (array_key_exists("Y", $attribs))
					$image->setY($attribs["Y"]);
				if (array_key_exists("HEIGHT", $attribs))
					$image->setHeight($attribs["HEIGHT"]);
				if (array_key_exists("WIDTH", $attribs))
					$image->setWidth($attribs["WIDTH"]);
				if (array_key_exists("VIEWBOX", $attribs)) {
					$image->setViewbox($attribs["VIEWBOX"]);
					$image->setTransforms($image->viewboxTransform());
				}
				$this->elementStack[] = $image;
				break;
			case "IMAGE":
				$image = new Image();
				$image->setViewport($this->currentGroup->getViewport());
				$image->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$image->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$image->setId($attribs["ID"]);
				if (array_key_exists("X", $attribs))
					$image->setX($attribs["X"]);
				if (array_key_exists("Y", $attribs))
					$image->setY($attribs["Y"]);
				if (array_key_exists("HEIGHT", $attribs))
					$image->setHeight($attribs["HEIGHT"]);
				if (array_key_exists("WIDTH", $attribs))
					$image->setWidth($attribs["WIDTH"]);
				if (array_key_exists("VIEWBOX", $attribs)) {
					if (array_key_exists("WIDTH", $attribs) && array_key_exists("HEIGHT", $attribs))
						$image->setViewport(array($attribs["WIDTH"],$attribs["HEIGHT"]));
					else
						$image->setViewport($this->currentGroup->getViewport());
					$image->setViewbox($attribs["VIEWBOX"]);
					$image->setTransforms($image->viewboxTransform());
				}
				if (array_key_exists("XLINK:HREF", $attribs))
					if ($this->validateUrl($attribs["XLINK:HREF"])) {
						$image->setXLink($attribs["XLINK:HREF"]);
					} else {
						$image->setXLink("http://www.google.com/images/cleardot.gif");
						$image->setHeight("50px");
						$image->setWidth("50px");
					}
				if (array_key_exists("STYLE", $attribs))
					$image->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($image,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$image->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("TRANSFORM", $attribs))
					$image->setTransforms($attribs["TRANSFORM"]);
				$this->elementStack[] = $image;
				break;
			case "LINEARGRADIENT":
				$linearGradient = new LinearGradient();
				$linearGradient->setViewport($this->currentGroup->getViewport());
				$linearGradient->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$linearGradient->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$linearGradient->setId($attribs["ID"]);
				if (array_key_exists("STYLE", $attribs))
					$linearGradient->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($linearGradient,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$linearGradient->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("GRADIENTTRANSFORM", $attribs))
					$linearGradient->setTransforms($attribs["GRADIENTTRANSFORM"]);
				if (array_key_exists("XLINK:HREF", $attribs))
					$linearGradient->setXLink($attribs["XLINK:HREF"]);
				if (array_key_exists("X1", $attribs))
					$linearGradient->setX1($attribs["X1"]);
				if (array_key_exists("Y1", $attribs))
					$linearGradient->setY1($attribs["Y1"]);
				if (array_key_exists("X2", $attribs))
					$linearGradient->setX2($attribs["X2"]);
				if (array_key_exists("Y2", $attribs))
					$linearGradient->setY2($attribs["Y2"]);
				$this->elementStack[] = $linearGradient;
				break;
			case "FILTER":
				$filterGroup = new FilterGroup();
				if (array_key_exists("ID", $attribs))
					$filterGroup->setId($attribs["ID"]);
				$this->currentFilterGroup = $filterGroup;
				break;
			case "FEGAUSSIANBLUR":
				$filter = new Filter();
				$filter->setType("feGaussianBlur");
				if (array_key_exists("ID", $attribs))
					$filter->setId($attribs["ID"]);
				if (array_key_exists("STDDEVIATION", $attribs))
					$filter->setStdDeviation($attribs["STDDEVIATION"]);
				$this->elementStack[] = $filter;
				break;
			case "FEOFFSET":
				$filter = new Filter();
				$filter->setType("feOffset");
				if (array_key_exists("DX", $attribs))
					$filter->setDx($attribs["DX"]);
				if (array_key_exists("DY", $attribs))
					$filter->setDy($attribs["DY"]);
				$this->elementStack[] = $filter;
				break;
			case "FEMERGE":
				$filter = new Filter();
				$filter->setType("feMerge");
				$this->elementStack[] = $filter;
				break;
			case "FEBLEND":
				$filter = new Filter();
				$filter->setType("feBlend");
				$this->elementStack[] = $filter;
				break;
			case "STOP":
				$stop = new GradientStop();
				$stop->setViewport($this->currentGroup->getViewport());
				$stop->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$stop->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$stop->setId($attribs["ID"]);
				if (array_key_exists("STYLE", $attribs))
					$stop->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($stop,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$stop->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("OFFSET", $attribs))
					$stop->setOffset($attribs["OFFSET"]);
				$this->elementStack[] = $stop;
				break;
			case "RADIALGRADIENT":
				$radialGradient = new RadialGradient();
				$radialGradient->setViewport($this->currentGroup->getViewport());
				$radialGradient->setViewbox($this->currentGroup->getViewbox());
				if ($this->currentGroup->getStyle('font-size'))
					$radialgradient->setStyle('font-size',$this->currentGroup->getStyle('font-size'));  // for em conversion
				if (array_key_exists("ID", $attribs))
					$radialGradient->setId($attribs["ID"]);
				if (array_key_exists("STYLE", $attribs))
					$radialGradient->setStyles($attribs["STYLE"]);
				$this->applyStyleAttributes($radialGradient,$attribs);
				if (array_key_exists("FILTER", $attribs))
					$radialGradient->setStyle("filter", $attribs["FILTER"]);
				if (array_key_exists("GRADIENTTRANSFORM", $attribs))
					$radialGradient->setTransforms($attribs["GRADIENTTRANSFORM"]);
				if (array_key_exists("XLINK:HREF", $attribs))
					$radialGradient->setXLink($attribs["XLINK:HREF"]);
				if (array_key_exists("CX", $attribs))
					$radialGradient->setCx($attribs["CX"]);
				if (array_key_exists("CY", $attribs))
					$radialGradient->setCy($attribs["CY"]);
				if (array_key_exists("R", $attribs))
					$radialGradient->setR($attribs["R"]);
				if (array_key_exists("FX", $attribs))
					$radialGradient->setFx($attribs["FX"]);
				if (array_key_exists("FY", $attribs))
					$radialGradient->setFy($attribs["FY"]);
				$this->elementStack[] = $radialGradient;
				break;
		}
	}

	// save inner-node text
	private function characterDataHandler($parser, $data) {
		$this->characterDataStack[] = $data;
	}
	
	// parser helper function for node end (i.e. </circle>)
	private function endElementHandler($parser, $name) {
		// get inner-node text for current node (i.e. <text>this is inner-node</text>)
		$lastText = array_pop($this->characterDataStack);
		// process node end
		switch ($name) {
			case "PATH":
				$element = array_pop($this->elementStack);
				if ($this->inPattern) {
					// ignore
				} elseif ($this->inDefs) {
					if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
						$s = array_pop($this->elementStack);
						if ($s->getId()) $element->setId($s->getId());
						if (count($s->getTransforms())>0) $element->setTransforms($s->getTransforms());
						if (count($s->getViewport())>0) $element->setViewport($s->getViewport());
					}
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				} else {
					$this->currentGroup->addChild($element);
				}
				break;
			case "RECT":
				$element = array_pop($this->elementStack);
				if ($this->inPattern) {
					// ignore
				} elseif ($this->inDefs) {
					if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
						$s = array_pop($this->elementStack);
						if ($s->getId()) $element->setId($s->getId());
						if (count($s->getTransforms())>0) $element->setTransforms($s->getTransforms());
						if (count($s->getViewport())>0) $element->setViewport($s->getViewport());
					}
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				} else {
					$this->currentGroup->addChild($element);
				}
				break;
			case "CIRCLE":
				$element = array_pop($this->elementStack);
				if ($this->inPattern) {
					// ignore
				} elseif ($this->inDefs) {
					if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
						$s = array_pop($this->elementStack);
						if ($s->getId()) $element->setId($s->getId());
						if (count($s->getTransforms())>0) $element->setTransforms($s->getTransforms());
						if (count($s->getViewport())>0) $element->setViewport($s->getViewport());
					}
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				} else {
					$this->currentGroup->addChild($element);
				}
				break;
			case "ELLIPSE":
				$element = array_pop($this->elementStack);
				if ($this->inPattern) {
					// ignore
				} elseif ($this->inDefs) {
					if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
						$s = array_pop($this->elementStack);
						if ($s->getId()) $element->setId($s->getId());
						if (count($s->getTransforms())>0) $element->setTransforms($s->getTransforms());
						if (count($s->getViewport())>0) $element->setViewport($s->getViewport());
					}
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				} else {
					$this->currentGroup->addChild($element);
				}
				break;
			case "LINE":
				$element = array_pop($this->elementStack);
				if ($this->inPattern) {
					// ignore
				} elseif ($this->inDefs) {
					if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
						$s = array_pop($this->elementStack);
						if ($s->getId()) $element->setId($s->getId());
						if (count($s->getTransforms())>0) $element->setTransforms($s->getTransforms());
						if (count($s->getViewport())>0) $element->setViewport($s->getViewport());
					}
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				} else {
					$this->currentGroup->addChild($element);
				}
				break;
			case "POLYLINE":
			case "POLYGON":
				$element = array_pop($this->elementStack);
				if ($this->inPattern) {
					// ignore
				} elseif ($this->inDefs) {
					if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
						$s = array_pop($this->elementStack);
						if ($s->getId()) $element->setId($s->getId());
						if (count($s->getTransforms())>0) $element->setTransforms($s->getTransforms());
						if (count($s->getViewport())>0) $element->setViewport($s->getViewport());
					}
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				} else {
					$this->currentGroup->addChild($element);
				}
				break;
			case "TEXT":
				$element = array_pop($this->elementStack);
				if (($element->getText() == null) && (trim($lastText) != '')) $element->setText(trim($lastText));
				elseif (($element->getText() != null) && (trim($lastText) != '')) $element->setText($element->getText().' '.trim($lastText));
				if ($element->getText() != null) {
					if ($this->inPattern) {
						// ignore
					} elseif ($this->inDefs) {
						if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
							$s = array_pop($this->elementStack);
							if ($s->getId()) $element->setId($s->getId());
							if (count($s->getTransforms())>0) $element->setTransforms($s->getTransforms());
							if (count($s->getViewport())>0) $element->setViewport($s->getViewport());
						}
						if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
					} else {
						$this->currentGroup->addChild($element);
					}
				}
				break;
			case "TSPAN":
				if ((trim($lastText) != '') && ($this->elementStack[count($this->elementStack)-1] instanceof Text)) { 
					$t = array_pop($this->elementStack);
					$text = $t->getText().' '.trim($lastText);
					$t->setText($text);
					$this->elementStack[] = $t;
				}
				break;
			case "TREF":
				$element = array_pop($this->elementStack);
				if (($element->getXLink()) && ($this->elementStack[count($this->elementStack)-1] instanceof Text)) {
					$link = $element->getXLink();
					$text = '';
					$defs = $this->svg->getDefs();
					foreach($defs as $def) {
						if ($def["id"] == $link) {
							if ($def["value"] && is_object($def["value"]) && ($def["value"] instanceof Text)) $text = $def["value"]->getText();
							break;
						}
					}
					$this->elementStack[count($this->elementStack)-1]->setText($text);
				}
				break;
			case "TEXTPATH":
				if ((trim($lastText) != '') && ($this->elementStack[count($this->elementStack)-1] instanceof Text)) { 
					$t = array_pop($this->elementStack);
					$text = $t->getText().' '.trim($lastText);
					$t->setText($text);
					$this->elementStack[] = $t;
				}
				break;
			case "IMAGE":
				$element = array_pop($this->elementStack);
				if ($this->inPattern) {
					if ($this->elementStack[count($this->elementStack)-1] instanceof Image) { 
						$p = array_pop($this->elementStack);
						$element->setId($p->getId());
						if($element->getX() == 0) $element->setX($p->getX());
						if($element->getY() == 0) $element->setY($p->getY());
						if($element->getHeight() == 0) $element->setHeight($p->getHeight());
						if($element->getWidth() == 0) $element->setWidth($p->getWidth());
						$this->elementStack[] = $element;
					}
				} elseif ($this->inDefs) {
					if (($this->inSymbol) && ($this->elementStack[count($this->elementStack)-1] instanceof Element)) {
						$s = array_pop($this->elementStack);
						if ($s->getId()) $element->setId($s->getId());
					}
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				} else {
					$this->currentGroup->addChild($element);
				}
				break;
			case "PATTERN":
				$element = array_pop($this->elementStack);
				if ($this->inDefs) {
					if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				}
				$this->inPattern = false;
				break;
			case "FILTER":
				if ($this->currentFilterGroup->getId()) $this->svg->addDef(array("id"=>$this->currentFilterGroup->getId(),"value"=>$this->currentFilterGroup));
				// reset current filter group
				$this->currentFilterGroup = null;
				break;
			case "FEGAUSSIANBLUR":
				$this->currentFilterGroup->addFilter(array_pop($this->elementStack));
				break;
			case "FEOFFSET":
				$this->currentFilterGroup->addFilter(array_pop($this->elementStack));
				break;
			case "FEMERGE":
				$this->currentFilterGroup->addFilter(array_pop($this->elementStack));
				break;
			case "FEBLEND":
				$this->currentFilterGroup->addFilter(array_pop($this->elementStack));
				break;
			case "LINEARGRADIENT":
				$element = array_pop($this->elementStack);
				if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				break;
			case "STOP":
				$stop = array_pop($this->elementStack);
				if ($this->elementStack[count($this->elementStack)-1] instanceof Gradient)
					$this->elementStack[count($this->elementStack)-1]->addStop($stop);
				break;
			case "RADIALGRADIENT":
				$element = array_pop($this->elementStack);
				if ($element->getId()) $this->svg->addDef(array("id"=>$element->getId(),"value"=>$element));
				break;
			case "DEFS":
				$this->inDefs = false;
				break;
			case "SYMBOL":
				$this->inSymbol = false;
				break;
			case "SVG":
			case "G":
			case "USE":
				$group = array_pop($this->groupStack);
				if ($group) {
					$group->addChild($this->currentGroup);
					$this->currentGroup = $group;
				}
				break;
		}
	}

	// SVG best practice: put styles in their own xml attributes instead of a single "style" attribute
	// http://jwatt.org/svg/authoring/#the-style-attribute
	private function applyStyleAttributes(&$obj, &$attributes) {
		// fill attributes
		if (array_key_exists("FILL", $attributes))
			$obj->setStyle("fill", $attributes["FILL"]);
		if (array_key_exists("FILL-OPACITY", $attributes))
			$obj->setStyle("fill-opacity", $attributes["FILL-OPACITY"]);
		// stroke attributes
		if (array_key_exists("STROKE", $attributes))
			$obj->setStyle("stroke", $attributes["STROKE"]);
		if (array_key_exists("STROKE-WIDTH", $attributes))
			$obj->setStyle("stroke-width", $attributes["STROKE-WIDTH"]);
		if (array_key_exists("STROKE-OPACITY", $attributes))
			$obj->setStyle("stroke-opacity", $attributes["STROKE-OPACITY"]);
		if (array_key_exists("STROKE-LINECAP", $attributes))
			$obj->setStyle("stroke-linecap", $attributes["STROKE-LINECAP"]);
		if (array_key_exists("STROKE-LINEJOIN", $attributes))
			$obj->setStyle("stroke-linejoin", $attributes["STROKE-LINEJOIN"]);
		if (array_key_exists("STROKE-DASHARRAY", $attributes))
			$obj->setStyle("stroke-dasharray", $attributes["STROKE-DASHARRAY"]);
		// font attributes
		if (array_key_exists("FONT-STYLE", $attributes))
			$obj->setFontStyle($attributes["FONT-STYLE"]);
		if (array_key_exists("FONT-VARIANT", $attributes))
			$obj->setFontVariant($attributes["FONT-VARIANT"]);
		if (array_key_exists("FONT-WEIGHT", $attributes))
			$obj->setFontWeight($attributes["FONT-WEIGHT"]);
		if (array_key_exists("FONT-SIZE", $attributes))
			$obj->setFontSize($attributes["FONT-SIZE"]);
		if (array_key_exists("FONT-FAMILY", $attributes))
			$obj->setFontFamily($attributes["FONT-FAMILY"]);
		// stop attributes
		if (array_key_exists("STOP-COLOR", $attributes))
			$obj->setStyle("stop-color", $attributes["STOP-COLOR"]);
		if (array_key_exists("STOP-OPACITY", $attributes))
			$obj->setStyle("stop-opacity", $attributes["STOP-OPACITY"]);
		if (array_key_exists("OFFSET", $attributes))
			$obj->setOffset($attributes["OFFSET"]);
		// group opacity (http://www.w3.org/TR/SVG/masking.html#ObjectAndGroupOpacityProperties)
		if (array_key_exists("OPACITY", $attributes)) {
			$obj->setStyle("fill-opacity", $attributes["OPACITY"]);
			$obj->setStyle("stroke-opacity", $attributes["OPACITY"]);
			$obj->setStyle("stop-opacity", $attributes["OPACITY"]);
		}
	}

	private function applyInheritance(&$child, &$parent) {
		// fill
		if (($child->getStyle('fill')==null) && ($parent->getStyle('fill')!=null))
			$child->setStyle('fill',$parent->getStyle('fill'));
		if (($child->getStyle('fill-opacity')==null) && ($parent->getStyle('fill-opacity')!=null))
			$child->setStyle('fill-opacity',$parent->getStyle('fill-opacity'));
		// stroke
		if (($child->getStyle('stroke')==null) && ($parent->getStyle('stroke')!=null))
			$child->setStyle('stroke',$parent->getStyle('stroke'));
		if (($child->getStyle('stroke-width')==null) && ($parent->getStyle('stroke-width')!=null))
			$child->setStyle('stroke-width',$parent->getStyle('stroke-width'));
		if (($child->getStyle('stroke-opacity')==null) && ($parent->getStyle('stroke-opacity')!=null))
			$child->setStyle('stroke-opacity',$parent->getStyle('stroke-opacity'));
		if (($child->getStyle('stroke-linecap')==null) && ($parent->getStyle('stroke-linecap')!=null))
			$child->setStyle('stroke-linecap',$parent->getStyle('stroke-linecap'));
		if (($child->getStyle('stroke-linejoin')==null) && ($parent->getStyle('stroke-linejoin')!=null))
			$child->setStyle('stroke-linejoin',$parent->getStyle('stroke-linejoin'));
		if (($child->getStyle('stroke-dasharray')==null) && ($parent->getStyle('stroke-dasharray')!=null))
			$child->setStyle('stroke-dasharray',$parent->getStyle('stroke-dasharray'));
		// font
		if (($child->getStyle('font-style')==null) && ($parent->getStyle('font-style')!=null))
			$child->setStyle('font-style',$parent->getStyle('font-style'));
		if (($child->getStyle('font-variant')==null) && ($parent->getStyle('font-variant')!=null))
			$child->setStyle('font-variant',$parent->getStyle('font-variant'));
		if (($child->getStyle('font-weight')==null) && ($parent->getStyle('font-weight')!=null))
			$child->setStyle('font-weight',$parent->getStyle('font-weight'));
		if (($child->getStyle('font-size')==null) && ($parent->getStyle('font-size')!=null))
			$child->setStyle('font-size',$parent->getStyle('font-size'));
		if (($child->getStyle('font-family')==null) && ($parent->getStyle('font-family')!=null))
			$child->setStyle('font-family',$parent->getStyle('font-family'));
	}

	// validate xml
	private function validateXml($xml) {
		$hasError = false;
		// set xml parse options
		//$options = LIBXML_DTDLOAD | LIBXML_DTDVALID | LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_NOBLANKS | LIBXML_NSCLEAN | LIBXML_COMPACT;
		// update: do not use DTD validation
		// https://jwatt.org/svg/authoring/#doctype-declaration
		// http://www.w3.org/blog/systeam/2008/02/08/w3c_s_excessive_dtd_traffic/
		// http://www.w3.org/QA/2002/04/valid-dtd-list.html
		$options = LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_NOBLANKS | LIBXML_NSCLEAN | LIBXML_COMPACT;
                libxml_use_internal_errors(true);
                $doc = new DOMDocument('1.0', 'utf-8');
		// load xml
                $doc->loadXML($xml, $options);
		// get errors, if any
                $errors = libxml_get_errors();
		// look for any error with severity level 3 or higher, otherwise return fully processed XML
                if (empty($errors)) return $doc->saveXML($doc);
		foreach($errors as $error) {
                	if ($error->level >= 3) $this->svg->addError(substr($error->message,0,-1).' at line '.$error->line);
		}
		if ($hasError) return false;
		else return $doc->saveXML($doc);
        }

	// validate url
	private function validateUrl($url) {
		$u = strtolower(trim($url));
		if (preg_match("/^data:image.*;base64/i", $u)) {
			return true;
		} else {
			$p = split("://",$u);
			$a = array("http","https","ftp");
			if (!in_array($p[0],$a)) return false;

			return true;  //FIXME: actually validate content...

			// Get the URL header
			$h = get_headers($u);
			list($version, $status_code, $msg) = explode(' ', $h[0], 3);
			// Check the HTTP Status code
			switch($status_code) {
			/*
				200: Success
				400: Invalid request
				401: Login failure
				404: Not found
				500: Server replied with an error
				502: Server may be down or being upgraded
				503: Service unavailable
			*/
				case 200:
					return true;
					break;
				default:
					return false;
					break;



			}
			return false;
		}
	}

}
?>
