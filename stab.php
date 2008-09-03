<?php

require_once("include/Brutus.php");

/* Et tu? */
$BrutusInstance = new Brutus( array("rootLogDirectoryPath"=>"brenda_logs") );

$BrutusInstance->renderable->assign("appTitle","Brutus");

/* Find any logs not yet parsed and start do do so. */
$BrutusInstance->verb( "resolveUnparsedLogs" );


if( isset($_GET['verb']) )
{
	$BrutusInstance->verb($_GET['verb']);
}

//$BrutusInstance->renderable->display('index.html.tpl');

?>
