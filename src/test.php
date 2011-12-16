<?php

$file = "../svg-test-samples/".$_GET["suite"]."/".$_GET["file"].".svg";

if (file_exists($file)) {
	$requestData = file_get_contents($file);
	include 'convert.php';
} else {
	echo "{}&&[]";
}

exit;

?>
