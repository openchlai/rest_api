<?php
include "config.php";

$db = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");
$db2 = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");

include "../rest.php";
include "users.php";

function _request_ () 
{
	$u = "";
	$suffix = "";
	$id = NULL;
	$o = [];
	$p = [];
	$fo = [];

	$rt = rest_uri_parse ($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], 3, $vw, $u, $suffix, $id, $o);
	if ($rt!=0) return $rt;

	if (!isset ($GLOBALS["MODELS"][$u])) return 404;

	$rt = 200;

	if ($_SERVER["REQUEST_METHOD"]=="POST")
	{
		$o['i_']=0;
		$rt = rest_uri_post ($u, $suffix, $id, $o, $p);
		$a = $GLOBALS["MODELS"][$u];
		$k = model_k_id ($u, $suffix, $a);
		$id = "-2";
		if (($rt==201 || $rt==202) && isset ($p[$k])) $id = $p[$k];
		error_log ("POST ".$k."=".$id."|".$rt);
	}

	if ($rt==200 || $rt==201 || $rt==202)
	{
		$rt = rest_uri_get ($u, $suffix, $id, $o, $p, $rt);
	}

	return $rt;
}

$rt = _request_ ();
if ($rt>399) rest_uri_response_error ($rt);

?>
