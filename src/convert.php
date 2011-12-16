<?php

require_once("parser/Parser.php");
require_once("objects/Circle.php");	// for radial gradients

// get data
if (!isset($requestData)) {
	$requestData = "";
	if (isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
		$httpContent = fopen('php://input', 'r');
		while ($data = fread($httpContent, 1024)) {
			$requestData .= $data;
		}
		fclose($httpContent);
	}
}
if (empty($requestData)) exit;

// prepare parser
$parser = new Parser();

// parse SVG
$svg = $parser->parse($requestData);

// get errors, if any
$errors = $svg->getErrors();

// get list of defs
$defs = $svg->getDefs();

// process defs xlink references
processDefs();

// get svg group data
$groupData = $svg->getGroupData();

// begin JSON output with security prefix (http://www.sitepen.com/blog/2008/09/25/security-in-ajax/)
$j='{}&&[';

// output errors, if any
if (!empty($errors)) $j.='{"errors":'.json_encode($errors).'},';

// process svg group data
if (!empty($groupData)) processGroupData($groupData, $j, $svg->getBBox());

// end JSON output
$j.=']';

// clean up
$j = str_replace(',}','}',$j);
$j = str_replace(',]',']',$j);

// return JSON
echo $j;

// =================================================================

function processGroupData(&$group, &$json, $bbox=null) {
	$json.='{';
		//$json.='"name":"'.$group->getId().'",';
		if ($bbox) $json.='"bbox":'.json_encode($bbox).',';
		processTransforms($group, $json);
		if ($group->getChildrenCount() > 0) {
			$children = $group->getChildren();
			$json.='"children":[';
			foreach ($children as $child) {
				$shapeType = strtolower(get_class($child));
				if ($shapeType == "group") { 
					processGroupData($child, $json);   //recursive
				} else {
					$json.='{';
						$json.='"shape":{';
							//if ($child->getId()) $json.='"name":"'.$child->getId().'",';
							switch ($shapeType) {
								case "circle":
									$json.='"type":"circle",';
									$json.='"cx":'.$child->getCx().',';
									$json.='"cy":'.$child->getCy().',';
									$json.='"r":'.$child->getR().',';
									break;
								case "ellipse":
									$json.='"type":"ellipse",';
									$json.='"cx":'.$child->getCx().',';
									$json.='"cy":'.$child->getCy().',';
									$json.='"rx":'.$child->getRx().',';
									$json.='"ry":'.$child->getRy().',';
									break;
								case "line":
									$json.='"type":"line",';
									$json.='"x1":'.$child->getX1().',';
									$json.='"y1":'.$child->getY1().',';
									$json.='"x2":'.$child->getX2().',';
									$json.='"y2":'.$child->getY2().',';
									break;
								case "path":
									$json.='"type":"path",';
									$json.='"path":"'.$child->getPath().'",';
									break;
								case "polyline":
								case "polygon":
									$json.='"type":"polyline",';
									$json.='"points":'.$child->getPoints().',';	// array
									break;
								case "rectangle":
									$json.='"type":"rect",';
									$json.='"x":'.$child->getX().',';
									$json.='"y":'.$child->getY().',';
									$json.='"width":'.$child->getWidth().',';
									$json.='"height":'.$child->getHeight().',';
									$json.='"r":'.$child->getR().',';
									break;
								case "image":
									$json.='"type":"image",';
									$json.='"x":'.$child->getX().',';
									$json.='"y":'.$child->getY().',';
									$json.='"width":'.$child->getWidth().',';
									$json.='"height":'.$child->getHeight().',';
									$json.='"src":"'.$child->getXLink().'",';
									break;
								case "text":
									if ($child->getTextpath() !== null) {
										$json.='"type":"textpath",';
										$json.='"text":"'.$child->getText().'",';
										$json.='"align":"'.$child->getTextAnchor().'",';
										$json.='"decoration":"'.$child->getTextDecoration().'",';
									} else {
										$json.='"type":"text",';
										$json.='"x":'.$child->getX().',';
										$json.='"y":'.$child->getY().',';
										$json.='"text":"'.$child->getText().'",';
										$json.='"align":"'.$child->getTextAnchor().'",';
										$json.='"decoration":"'.$child->getTextDecoration().'",';
									}
									break;
							}
						$json.='},';
						if (($shapeType === "text") && ($child->getTextpath() !== null)) processTextpath($child, $json);
						processTransforms($child, $json);
						processStyles($child, $json, $group);
						processEffects($child, $json);
					$json.='},';
				}
			}
			$json.=']';
		}
	$json.='},';
}

function processTextpath(&$shape, &$json) {
	global $defs;
	$xlink = trim($shape->getTextpath(),' #');
	foreach($defs as $def) {
		if ($def['id'] === $xlink) {
			$json.='"textpath":"'.$def['value']->getPath().'",';
			break;
		}
	}
}

function processTransforms(&$shape, &$json) {
	$transform = $shape->getTransform();
	if ($transform && !$transform->isIdentity()) {
		$json.='"transform":{';
			$json.='"xx":'.$transform->getXx().',';
			$json.='"yx":'.$transform->getYx().',';
			$json.='"xy":'.$transform->getXy().',';
			$json.='"yy":'.$transform->getYy().',';
			$json.='"dx":'.$transform->getDx().',';
			$json.='"dy":'.$transform->getDy().',';
		$json.='},';
	}
}

function processStyles(&$shape, &$json, &$group) {
	// inherit painting properies (http://www.w3.org/TR/SVG/painting.html#InheritanceOfPaintingProperties)
	applyInheritance(&$shape, &$group);

	// stroke
	if (($shape->getStyle('stroke')==null) || (strtolower($shape->getStyle('stroke'))=='none')) { }
	else {
		$json.='"stroke":{';
			$json.='"type":"stroke",';
			if($shape->getStyle('stroke')) {
				$json.='"color":{';
					$color = $shape->getStyle('stroke');
					$opacity = 1;
					if ($shape->getStyle('stroke-opacity')) $opacity = $shape->getStyle('stroke-opacity');
					$color = color2rgba($color,$opacity);
					if (($color[0]==='iri') && !empty($color[2]) && is_object($color[2])) {
						// set color to first gradient stop since Dojox.gfx doesn't support gradient stroke
						// http://docs.dojocampus.org/dojox/gfx#stroke-property
						if ($color[2]->getStops()) {
							$s = $color[2]->getStops();
							if (!empty($s)) $json.=rgba2json(color2rgba($s[0]->getColor()));
							else $json.=rgba2json(array(0,0,0,1));
						} else {
							$json.=rgba2json(array(0,0,0,1));
						}
					} else {
						$json.=rgba2json($color);
					}
				$json.='},';
			}
			if($shape->getStyle('stroke-dasharray')) $json.='"style":"'.$shape->getStyle('stroke-dasharray').'",';
			if($shape->getStyle('stroke-width')) $json.='"width":'.$shape->getStyle('stroke-width').',';
			if($shape->getStyle('stroke-linecap')) $json.='"cap":"'.$shape->getStyle('stroke-linecap').'",';
			if($shape->getStyle('stroke-linejoin')) {
				if(strtolower($shape->getStyle('stroke-linejoin')) == 'miter') $json.='"join":4,';
				else $json.='"join":"'.$shape->getStyle('stroke-linejoin').'",';
			}
		$json.='},';
	}

	// fill
	if (($shape->getStyle('fill')==null) || (strtolower($shape->getStyle('fill'))=='none')) { }
	else {
		$opacity = 1;
		if ($shape->getStyle('fill-opacity')) $opacity = $shape->getStyle('fill-opacity');
		$json.='"fill":{';
			processFill($shape->getStyle('fill'),$opacity,$shape,$json);
		$json.='},';
	}

	// font
	if ($shape instanceof Text) {
		$fontStyle = $shape->getStyle('font-style');
		$fontVariant = $shape->getStyle('font-variant');
		$fontWeight = $shape->getStyle('font-weight');
		$fontSize = $shape->getStyle('font-size');
		$fontFamily = $shape->getStyle('font-family');
		if ($fontStyle || $fontVariant || $fontWeight || $fontSize || $fontFamily) {
			$json.='"font":{';
				if ($fontStyle) $json.='"style":"'.trim($fontStyle).'",';
				if ($fontVariant) $json.='"variant":"'.trim($fontVariant).'",';
				if ($fontWeight) $json.='"weight":"'.trim($fontWeight).'",';
				if ($fontSize) $json.='"size":"'.trim($fontSize).'",';
				if ($fontFamily) $json.='"family":"'.trim($fontFamily).'",';
			$json.='},';
		}
	}

}

function applyInheritance(&$shape, &$group) {
	// fill
	if (($shape->getStyle('fill')==null) && ($group->getStyle('fill')!=null))
		$shape->setStyle('fill',$group->getStyle('fill'));
	if (($shape->getStyle('fill-opacity')==null) && ($group->getStyle('fill-opacity')!=null))
		$shape->setStyle('fill-opacity',$group->getStyle('fill-opacity'));
	// stroke
	if (($shape->getStyle('stroke')==null) && ($group->getStyle('stroke')!=null))
		$shape->setStyle('stroke',$group->getStyle('stroke'));
	if (($shape->getStyle('stroke-width')==null) && ($group->getStyle('stroke-width')!=null))
		$shape->setStyle('stroke-width',$group->getStyle('stroke-width'));
	if (($shape->getStyle('stroke-opacity')==null) && ($group->getStyle('stroke-opacity')!=null))
		$shape->setStyle('stroke-opacity',$group->getStyle('stroke-opacity'));
	if (($shape->getStyle('stroke-linecap')==null) && ($group->getStyle('stroke-linecap')!=null))
		$shape->setStyle('stroke-linecap',$group->getStyle('stroke-linecap'));
	if (($shape->getStyle('stroke-linejoin')==null) && ($group->getStyle('stroke-linejoin')!=null))
		$shape->setStyle('stroke-linejoin',$group->getStyle('stroke-linejoin'));
	if (($shape->getStyle('stroke-dasharray')==null) && ($group->getStyle('stroke-dasharray')!=null))
		$shape->setStyle('stroke-dasharray',$group->getStyle('stroke-dasharray'));
	// font
	if ($shape instanceof Text) {
		if (($shape->getStyle('font-style')==null) && ($group->getStyle('font-style')!=null))
			$shape->setStyle('font-style',$group->getStyle('font-style'));
		if (($shape->getStyle('font-variant')==null) && ($group->getStyle('font-variant')!=null))
			$shape->setStyle('font-variant',$group->getStyle('font-variant'));
		if (($shape->getStyle('font-weight')==null) && ($group->getStyle('font-weight')!=null))
			$shape->setStyle('font-weight',$group->getStyle('font-weight'));
		if (($shape->getStyle('font-size')==null) && ($group->getStyle('font-size')!=null))
			$shape->setStyle('font-size',$group->getStyle('font-size'));
		if (($shape->getStyle('font-family')==null) && ($group->getStyle('font-family')!=null))
			$shape->setStyle('font-family',$group->getStyle('font-family'));
	}
}

function processFill($fill,$opacity,&$shape,&$json) {
	$color = color2rgba($fill,$opacity);
	if (($color[0]==='iri') && !empty($color[2]) && is_object($color[2])) {
		// non-solid fill
		$fillType = strtolower(get_class($color[2]));
		switch ($fillType) {
			case "lineargradient":
				$json.='"type":"linear",';
				$gradient = $color[2];
				$bb = null;
				$x1 = $gradient->getX1();
				$y1 = $gradient->getY1();
				$x2 = $gradient->getX2();
				$y2 = $gradient->getY2();
				if ($x1==null || $y1==null || $x2==null || $y2==null) {
					$bb = $shape->getBBox();
					if ($x1==null) $x1=$bb['x'];
					if ($y1==null) $y1=$bb['y'];
					if ($x2==null) $x2=($bb['x']+$bb['width']);
					if ($y2==null) $y2=($bb['y']+$bb['height']);
				}
				if ($gradient->getTransform()) {
					// Dojox.gfx doesn't support gradient transform (http://www.w3.org/TR/SVG/pservers.html#RadialGradientElementGradientTransformAttribute)
					//   apply best guess:
					$transform = $gradient->getTransform();
					if ($transform && !$transform->isIdentity()) {
						$x1 = $transform->applyToX($x1,$y1);
						$y1 = $transform->applyToY($x1,$y1);
						$x2 = $transform->applyToX($x2,$y2);
						$y2 = $transform->applyToY($x2,$y2);
					}
				}
				if ((substr($x1,-1)=="%") || (substr($y1,-1)=="%") || (substr($x2,-1)=="%") || (substr($y2,-1)=="%")) {
					// linear gradient has percentage coordinates
					if (!$bb) $bb = $shape->getBBox();
					if (substr($x1,-1) == "%") $x1 = $bb['x'] + ((substr($x1,0,-1) / 100) * $bb['width']);
					if (substr($y1,-1) == "%") $y1 = $bb['y'] + ((substr($y1,0,-1) / 100) * $bb['height']);
					if (substr($x2,-1) == "%") $x2 = $bb['x'] + $bb['width'] - ((1 - (substr($x2,0,-1) / 100)) * $bb['width']);
					if (substr($y2,-1) == "%") $y2 = $bb['y'] + $bb['height'] - ((1 - (substr($y2,0,-1) / 100)) * $bb['height']);
				}
				$json.='"x1":'.$x1.',';
				$json.='"y1":'.$y1.',';
				$json.='"x2":'.$x2.',';
				$json.='"y2":'.$y2.',';
				$json.='"colors":[';
					$stops = $gradient->getStops();
					foreach($stops as $stop) {
						$json.='{';
							$json.='"offset":'.$stop->getOffset().',';
							$json.='"color":{';
								$json.=rgba2json(color2rgba($stop->getColor(),$stop->getOpacity()));
							$json.='},';
						$json.='},';
					}
				$json.=']';
				break;
			case "radialgradient":
				$json.='"type":"radial",';
				$gradient = $color[2];
				$cx = $gradient->getCx();
				$cy = $gradient->getCy();
				$r = $gradient->getR();
				$bb = null;
				if ($gradient->getTransform()) {
					// Dojox.gfx doesn't support gradient transform (http://www.w3.org/TR/SVG/pservers.html#RadialGradientElementGradientTransformAttribute)
					//   apply best guess:
					$transform = $gradient->getTransform();
					if ($transform && !$transform->isIdentity()) {
						$cx = $transform->applyToX($cx,$cy);
						$cy = $transform->applyToY($cx,$cy);
						// calculate 'r' (http://www.w3.org/TR/SVG/pservers.html#RadialGradientElementRAttribute)
						$c = new Circle();
						$bb = $shape->getBBox();
						$c->setCx($bb['x'] + ($bb['width'] / 2));
						$c->setCy($bb['y'] + ($bb['height'] / 2));
						if ($bb['width'] < $bb['height']) $c->setR($bb['width'] / 2);
						else $c->setR($bb['height'] / 2);
						$c->setTransform($transform);
						$r = $c->getR();
					}
				}
				if ((substr($cx,-1)=="%") || (substr($cy,-1)=="%") || (substr($r,-1)=="%")) {
					// radial gradient has percentage coordinates
					if (!$bb) $bb = $shape->getBBox();
					if (substr($cx,-1) == "%") $cx = $bb['x'] + ((substr($cx,0,-1) / 100) * $bb['width']);
					if (substr($cy,-1) == "%") $cy = $bb['y'] + ((substr($cy,0,-1) / 100) * $bb['height']);
					if (substr($r,-1) == "%") {
						if ($bb['width'] < $bb['height'])
							$r = $bb['width'] * (substr($r,0,-1) / 100);
						else
							$r = $bb['height'] * (substr($r,0,-1) / 100);
					}
				}
				$json.='"cx":'.$cx.',';
				$json.='"cy":'.$cy.',';
				$json.='"r":'.$r.',';
				$json.='"colors":[';
					$stops = $gradient->getStops();
					foreach($stops as $stop) {
						$json.='{';
							$json.='"offset":'.$stop->getOffset().',';
							$json.='"color":{';
								$json.=rgba2json(color2rgba($stop->getColor(),$stop->getOpacity()));
							$json.='},';
						$json.='},';
					}
				$json.=']';
				break;
			case "image":
				// pattern fill
				$json.='"type":"pattern",';
				$pattern = $color[2];
				$json.='"x":'.$pattern->getX().',';
				$json.='"y":'.$pattern->getY().',';
				$json.='"width":'.$pattern->getWidth().',';
				$json.='"height":'.$pattern->getHeight().',';
				$json.='"src":"'.$pattern->getXLink().'",';
				break;
		}
	} 
	else {
		$json.=rgba2json($color);
	}
}

function processEffects(&$shape, &$json) {
	global $defs;
	$filter = $shape->getStyle("filter");
	$filterGroup = null;
	if (ereg("url\(\#.*\)", $filter)) {
		// IRI reference
		// http://www.w3.org/TR/SVG/intro.html#TermIRIReference
		$filter = split('#', $filter);
		$filter = trim($filter[1],' )');
		foreach($defs as $def) {
			if ($def['id'] == $filter) {
				$filterGroup = $def['value'];
				break;
			}
		}
	}
	if ($filterGroup) {
		$json .= getEffect($filterGroup);
	}
}

function getEffect($filterGroup) {
	$effect = "";
	$feGaussianBlur = false;
	$feOffset = false;
	$feMerge = false;
	$feBlend = false;
	$dx = 4;
	$dy = 4;
	$size = 2.5;
	$filters = $filterGroup->getFilters();
	for ($i = 0; $i < count($filters); $i++) {
		if ($filters[$i]->getType() == "feGaussianBlur") {
			$feGaussianBlur = true;
			$size = $filters[$i]->getStdDeviation();
		} else if ($filters[$i]->getType() == "feOffset") {
			$feOffset = true;
			$dx = $filters[$i]->getDx();
			$dy = $filters[$i]->getDy();
		} else if ($filters[$i]->getType() == "feMerge") {
			$feMerge = true;
		} else if ($filters[$i]->getType() == "feBlend") {
			$feBlend = true;
		}
	}
	if ($feGaussianBlur && $feOffset && ($feMerge || $feBlend)) {
		// requires Shadow plugin: http://github.com/mrbluecoat/Dojox.gfx-Plugins/tree/master/Shadow/
		$effect='"effect":{';
			$effect.='"type":"shadow",';
			$effect.='"dx":'.$dx.',';
			$effect.='"dy":'.$dy.',';
			$effect.='"size":'.$size.',';
		$effect.='},';
	} else if ($feGaussianBlur) {
		// requires Blur plugin: http://github.com/mrbluecoat/Dojox.gfx-Plugins/tree/master/Blur/
		$effect='"effect":{';
			$effect.='"type":"blur",';
			$effect.='"size":'.$size.',';
		$effect.='},';
	}
	return $effect;
}

// convert color to rgba format
function color2rgba($color, $opacity=1) {
	global $defs;
	// ignore input that is not array or string
	if (!is_array($color) && !is_string($color)) return array(0,0,0,1);
	// validate opacity
	if (!is_numeric($opacity) || $opacity<0 || $opacity>1) $opacity = 1;
	// prepare color
	if (is_string($color)) {
		$color = str_replace(' ','',trim($color));
		if (substr_count($color, ')') > 1) {
			$color = substr($color,0,strpos($color,')')+1);
		}
	}
	// convert color
	if (is_array($color)) {
		// rgb(a) array
		$range = range(0,255);
		// check for opacity
		if (!empty($color[3]) && is_numeric($color[3]) && $color[3]>=0 && $color[3]<=1) $opacity = $color[3];
		// check for rgb
		if (in_array($color[0],$range) && in_array($color[1],$range) && in_array($color[2],$range)) return array($color[0],$color[1],$color[2],$opacity);
		else return array(0,0,0,$opacity);
	} else if (ereg("#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})", $color)) {
		// html hex format
		$color = trim($color,' #');
		if (strlen($color) == 6) list($r, $g, $b) = array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);
		else if (strlen($color) == 3) list($r, $g, $b) = array($color[0].$color[0],$color[1].$color[1],$color[2].$color[2]);
		else list($r, $g, $b) = array('00','00','00');
		return array(hexdec($r),hexdec($g),hexdec($b),$opacity);
	} else if (ereg("rgb\([0-9]{1,3},[0-9]{1,3},[0-9]{1,3}\)", $color)) {
		// rgb string
		$color = str_replace('rgb(','',$color);
		$color = str_replace(')','',$color);
		$color = split(',', $color);
		return array($color[0],$color[1],$color[2],$opacity);
	} else if (ereg("url\(\#.*\)", $color)) {
		// IRI reference (defs)
		// http://www.w3.org/TR/SVG/intro.html#TermIRIReference
		$color = split('#', $color);
		$color = trim($color[1],' )');
		$match = null;
		foreach($defs as $def) {
			if ($def['id'] == $color) {
				$match = $def['value'];
				break;
			}
		}
		if ($match) return array('iri',$color,$match);
		else return array(0,0,0,1);
	} else {
		// assume color constant
		// http://www.w3.org/TR/SVG/types.html#ColorKeywords
		$color = strtolower($color);
		switch ($color) {
			case "aliceblue": return array(240, 248, 255, $opacity);
			case "antiquewhite": return array(250, 235, 215, $opacity);
			case "aqua": return array( 0, 255, 255, $opacity);
			case "aquamarine": return array(127, 255, 212, $opacity);
			case "azure": return array(240, 255, 255, $opacity);
			case "beige": return array(245, 245, 220, $opacity);
			case "bisque": return array(255, 228, 196, $opacity);
			case "black": return array( 0, 0, 0, $opacity);
			case "blanchedalmond": return array(255, 235, 205, $opacity);
			case "blue": return array( 0, 0, 255, $opacity);
			case "blueviolet": return array(138, 43, 226, $opacity);
			case "brown": return array(165, 42, 42, $opacity);
			case "burlywood": return array(222, 184, 135, $opacity);
			case "cadetblue": return array( 95, 158, 160, $opacity);
			case "chartreuse": return array(127, 255, 0, $opacity);
			case "chocolate": return array(210, 105, 30, $opacity);
			case "coral": return array(255, 127, 80, $opacity);
			case "cornflowerblue": return array(100, 149, 237, $opacity);
			case "cornsilk": return array(255, 248, 220, $opacity);
			case "crimson": return array(220, 20, 60, $opacity);
			case "cyan": return array( 0, 255, 255, $opacity);
			case "darkblue": return array( 0, 0, 139, $opacity);
			case "darkcyan": return array( 0, 139, 139, $opacity);
			case "darkgoldenrod": return array(184, 134, 11, $opacity);
			case "darkgray": return array(169, 169, 169, $opacity);
			case "darkgreen": return array( 0, 100, 0, $opacity);
			case "darkgrey": return array(169, 169, 169, $opacity);
			case "darkkhaki": return array(189, 183, 107, $opacity);
			case "darkmagenta": return array(139, 0, 139, $opacity);
			case "darkolivegreen": return array( 85, 107, 47, $opacity);
			case "darkorange": return array(255, 140, 0, $opacity);
			case "darkorchid": return array(153, 50, 204, $opacity);
			case "darkred": return array(139, 0, 0, $opacity);
			case "darksalmon": return array(233, 150, 122, $opacity);
			case "darkseagreen": return array(143, 188, 143, $opacity);
			case "darkslateblue": return array( 72, 61, 139, $opacity);
			case "darkslategray": return array( 47, 79, 79, $opacity);
			case "darkslategrey": return array( 47, 79, 79, $opacity);
			case "darkturquoise": return array( 0, 206, 209, $opacity);
			case "darkviolet": return array(148, 0, 211, $opacity);
			case "deeppink": return array(255, 20, 147, $opacity);
			case "deepskyblue": return array( 0, 191, 255, $opacity);
			case "dimgray": return array(105, 105, 105, $opacity);
			case "dimgrey": return array(105, 105, 105, $opacity);
			case "dodgerblue": return array( 30, 144, 255, $opacity);
			case "firebrick": return array(178, 34, 34, $opacity);
			case "floralwhite": return array(255, 250, 240, $opacity);
			case "forestgreen": return array( 34, 139, 34, $opacity);
			case "fuchsia": return array(255, 0, 255, $opacity);
			case "gainsboro": return array(220, 220, 220, $opacity);
			case "ghostwhite": return array(248, 248, 255, $opacity);
			case "gold": return array(255, 215, 0, $opacity);
			case "goldenrod": return array(218, 165, 32, $opacity);
			case "gray": return array(128, 128, 128, $opacity);
			case "grey": return array(128, 128, 128, $opacity);
			case "green": return array( 0, 128, 0, $opacity);
			case "greenyellow": return array(173, 255, 47, $opacity);
			case "honeydew": return array(240, 255, 240, $opacity);
			case "hotpink": return array(255, 105, 180, $opacity);
			case "indianred": return array(205, 92, 92, $opacity);
			case "indigo": return array( 75, 0, 130, $opacity);
			case "ivory": return array(255, 255, 240, $opacity);
			case "khaki": return array(240, 230, 140, $opacity);
			case "lavender": return array(230, 230, 250, $opacity);
			case "lavenderblush": return array(255, 240, 245, $opacity);
			case "lawngreen": return array(124, 252, 0, $opacity);
			case "lemonchiffon": return array(255, 250, 205, $opacity);
			case "lightblue": return array(173, 216, 230, $opacity);
			case "lightcoral": return array(240, 128, 128, $opacity);
			case "lightcyan": return array(224, 255, 255, $opacity);
			case "lightgoldenrodyellow": return array(250, 250, 210, $opacity);
			case "lightgray": return array(211, 211, 211, $opacity);
			case "lightgreen": return array(144, 238, 144, $opacity);
			case "lightgrey": return array(211, 211, 211, $opacity);
			case "lightpink": return array(255, 182, 193, $opacity);
			case "lightsalmon": return array(255, 160, 122, $opacity);
			case "lightseagreen": return array( 32, 178, 170, $opacity);
			case "lightskyblue": return array(135, 206, 250, $opacity);
			case "lightslategray": return array(119, 136, 153, $opacity);
			case "lightslategrey": return array(119, 136, 153, $opacity);
			case "lightsteelblue": return array(176, 196, 222, $opacity);
			case "lightyellow": return array(255, 255, 224, $opacity);
			case "lime": return array( 0, 255, 0, $opacity);
			case "limegreen": return array( 50, 205, 50, $opacity);
			case "linen": return array(250, 240, 230, $opacity);
			case "magenta": return array(255, 0, 255, $opacity);
			case "maroon": return array(128, 0, 0, $opacity);
			case "mediumaquamarine": return array(102, 205, 170, $opacity);
			case "mediumblue": return array( 0, 0, 205, $opacity);
			case "mediumorchid": return array(186, 85, 211, $opacity);
			case "mediumpurple": return array(147, 112, 219, $opacity);
			case "mediumseagreen": return array( 60, 179, 113, $opacity);
			case "mediumslateblue": return array(123, 104, 238, $opacity);
			case "mediumspringgreen": return array( 0, 250, 154, $opacity);
			case "mediumturquoise": return array( 72, 209, 204, $opacity);
			case "mediumvioletred": return array(199, 21, 133, $opacity);
			case "midnightblue": return array( 25, 25, 112, $opacity);
			case "mintcream": return array(245, 255, 250, $opacity);
			case "mistyrose": return array(255, 228, 225, $opacity);
			case "moccasin": return array(255, 228, 181, $opacity);
			case "navajowhite": return array(255, 222, 173, $opacity);
			case "navy": return array( 0, 0, 128, $opacity);
			case "oldlace": return array(253, 245, 230, $opacity);
			case "olive": return array(128, 128, 0, $opacity);
			case "olivedrab": return array(107, 142, 35, $opacity);
			case "orange": return array(255, 165, 0, $opacity);
			case "orangered": return array(255, 69, 0, $opacity);
			case "orchid": return array(218, 112, 214, $opacity);
			case "palegoldenrod": return array(238, 232, 170, $opacity);
			case "palegreen": return array(152, 251, 152, $opacity);
			case "paleturquoise": return array(175, 238, 238, $opacity);
			case "palevioletred": return array(219, 112, 147, $opacity);
			case "papayawhip": return array(255, 239, 213, $opacity);
			case "peachpuff": return array(255, 218, 185, $opacity);
			case "peru": return array(205, 133, 63, $opacity);
			case "pink": return array(255, 192, 203, $opacity);
			case "plum": return array(221, 160, 221, $opacity);
			case "powderblue": return array(176, 224, 230, $opacity);
			case "purple": return array(128, 0, 128, $opacity);
			case "red": return array(255, 0, 0, $opacity);
			case "rosybrown": return array(188, 143, 143, $opacity);
			case "royalblue": return array( 65, 105, 225, $opacity);
			case "saddlebrown": return array(139, 69, 19, $opacity);
			case "salmon": return array(250, 128, 114, $opacity);
			case "sandybrown": return array(244, 164, 96, $opacity);
			case "seagreen": return array( 46, 139, 87, $opacity);
			case "seashell": return array(255, 245, 238, $opacity);
			case "sienna": return array(160, 82, 45, $opacity);
			case "silver": return array(192, 192, 192, $opacity);
			case "skyblue": return array(135, 206, 235, $opacity);
			case "slateblue": return array(106, 90, 205, $opacity);
			case "slategray": return array(112, 128, 144, $opacity);
			case "slategrey": return array(112, 128, 144, $opacity);
			case "snow": return array(255, 250, 250, $opacity);
			case "springgreen": return array( 0, 255, 127, $opacity);
			case "steelblue": return array( 70, 130, 180, $opacity);
			case "tan": return array(210, 180, 140, $opacity);
			case "teal": return array( 0, 128, 128, $opacity);
			case "thistle": return array(216, 191, 216, $opacity);
			case "tomato": return array(255, 99, 71, $opacity);
			case "turquoise": return array( 64, 224, 208, $opacity);
			case "violet": return array(238, 130, 238, $opacity);
			case "wheat": return array(245, 222, 179, $opacity);
			case "white": return array(255, 255, 255, $opacity);
			case "whitesmoke": return array(245, 245, 245, $opacity);
			case "yellow": return array(255, 255, 0, $opacity);
			case "yellowgreen": return array(154, 205, 50, $opacity);
			default: return array(0,0,0,1);  // catch-all
		}
	}
}

// convert rgba color to JSON format
function rgba2json($rgba) {
	$json='"r":'.$rgba[0].',';
	$json.='"g":'.$rgba[1].',';
	$json.='"b":'.$rgba[2].',';
	$json.='"a":'.$rgba[3].',';
	return $json;
}

function processDefs() {
	global $defs;
	foreach($defs as $def) {
		if ($def['value'] && is_object($def['value']) && method_exists($def['value'],'getXLink')) {
			$xlink = $def['value']->getXLink();
			foreach($defs as $def2) {
				if (($def2['id'] === $xlink) && $def2['value'] && is_object($def2['value'])) {
					if ((($def['value'] instanceof LinearGradient) || ($def['value'] instanceof RadialGradient)) && (($def2['value'] instanceof LinearGradient) || ($def2['value'] instanceof RadialGradient)))
						$def['value']->setStops($def2['value']->getStops());
					break;
				}
			}
		}
	}
}

?>
