<?php

class Transform {
	private $xx;
	private $yx;
	private $xy;
	private $yy;
	private $dx;
	private $dy;
	
	public function __construct() {
		$this->xx = 1;
		$this->yx = 0;
		$this->xy = 0;
		$this->yy = 1;
		$this->dx = 0;
		$this->dy = 0;
	}
	
	public function setFullMatrix($newXx, $newYx, $newXy, $newYy, $newDx, $newDy) {
		$this->xx = $newXx;
		$this->yx = $newYx;
		$this->xy = $newXy;
		$this->yy = $newYy;
		$this->dx = $newDx;
		$this->dy = $newDy;
	}

	public function getXx() { return $this->xx; }
	public function setXx($val) { $this->xx = parent::normalizeUnit($val,'x'); }

	public function getYx() { return $this->yx; }
	public function setYx($val) { $this->yx = parent::normalizeUnit($val); }

	public function getXy() { return $this->xy; }
	public function setXy($val) { $this->xy = parent::normalizeUnit($val); }

	public function getYy() { return $this->yy; }
	public function setYy($val) { $this->yy = parent::normalizeUnit($val,'y'); }

	public function getDx() { return $this->dx; }
	public function setDx($val) { $this->dx = parent::normalizeUnit($val,'x'); }

	public function getDy() { return $this->dy; }
	public function setDy($val) { $this->dy = parent::normalizeUnit($val,'y'); }
	
	public function setTranslate($newDx, $newDy = 0) {
		$this->setFullMatrix(1,0,0,1,$newDx, $newDy);
	}
	
	public function setScale($scaleX, $scaleY = -1) {
		if ($scaleY == -1) $scaleY = $scaleX;
		$this->setFullMatrix($scaleX, 0, 0, $scaleY, 0, 0);
	}
	
	public function setRotate($angle) {
		$this->setFullMatrix(cos(deg2rad($angle)), sin(deg2rad($angle)), -sin(deg2rad($angle)), cos(deg2rad($angle)), 0, 0);
	}
	
	public function setSkewX($angle) {
		$this->setFullMatrix(1, 0, tan(deg2rad($angle)), 1, 0, 0);
	}
	
	public function setSkewY($angle) {
		$this->setFullMatrix(1, tan(deg2rad($angle)), 0, 1, 0, 0);
	}
	
	public function applyToX($x, $y) {
		return $this->xx * $x + $this->xy * $y + $this->dx;
	}
	
	public function applyToY($x, $y) {
		return $this->yx * $x + $this->yy * $y + $this->dy;
	}

	// does multiplication of the form [$this]*[$other] and stores the result
	public function multiplyBy($other) {
		$xx = ($this->xx*$other->xx + $this->xy*$other->yx);
		$yx = ($this->yx*$other->xx + $this->yy*$other->yx);
		$xy = ($this->xx*$other->xy + $this->xy*$other->yy);
		$yy = ($this->yx*$other->xy + $this->yy*$other->yy);
		$dx = ($this->xx*$other->dx + $this->xy*$other->dy + $this->dx);
		$dy = ($this->yx*$other->dx + $this->yy*$other->dy + $this->dy);
		$this->setFullMatrix($xx, $yx, $xy, $yy, $dx, $dy);
	}

	public function isIdentity() {
		return ($this->xx == 1 && $this->yx == 0 && $this->xy == 0 && $this->yy == 1 && $this->dx == 0 && $this->dy == 0);
	}
}
?>
