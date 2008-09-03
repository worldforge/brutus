<?php

require_once("include/Brutus.php");

/* Et tu? */
$BrutusInstance = new Brutus( array("rootLogDirectoryPath"=>"brenda_logs") );

$BrutusInstance->renderable->assign("appTitle","Brutus");

if( isset($_GET['verb']) )
{
	$BrutusInstance->verb($_GET['verb']);
}
elseif( isset($_POST['verb']) )
{
	$BrutusInstance->verb($_POST['verb']);
}
$BrutusInstance->Render();


?>
