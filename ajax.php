<?php

require_once("include/Brutus.php");

/* Et tu? */
$BrutusInstance = new Brutus();

if( isset($_GET['verb']) )
{
	// Should return rendered results.
	$BrutusInstance->verb($_GET['verb']);
}

?>
