<?php
include "config.php";

$db = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");
$db2 = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");

include "lib/rest.php";
include "lib/session.php";
include "lib/XLSXbuf.php";

include "users.php";

function _request_ () 
{
	$vw = "";
	$u = "";
	$suffix = "";
	$id = NULL;
	$o = [];
	$p = [];
	$fo = [];

	$rt = rest_uri_parse ($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], 3, $vw, $u, $suffix, $id, $o);
	if ($rt!=0) return $rt;
	
	if ($vw!="api")
	{
		return _vw ($vw);
	}

	if (isset ($_GET["logout"]))
	{
		//$o_ = ["action"=>"0"]; // remove member from queue(s)
		//_agent ($o_);
	}
	
	$rt = auth ($o);
	error_log ("auth (".$u.") ---------------------------------------------------------|".$rt);
	if ($rt!=0) return $rt;

	$p["auth_id"] = _S("cc_user_id");
	$p["auth_usn"] = _S("cc_user_usn");
	$p["auth_exten"] = _S("cc_user_exten");
	$p["auth_role"] = _S("cc_user_role");
	$p["profile_id"] = _S("cc_user_contact_id");

	_ks ();  // load models_k

	if (strlen($u)<1) return 200; // _home ($o,$p);

	if ($u=="resetAuthAdmin") return _reset_auth_admin ($o, $p);

	if ($u=="changeAuth") return _change_auth ($o, $p);

	//if ($u=="agent") return _agent ($o);

	//if ($u=="chan") return _chan ($o);

	if (!isset ($GLOBALS["MODELS"][$u])) return 404;

	$rt = 200;

	if ($_SERVER["REQUEST_METHOD"]=="POST")
	{
		error_log ("[o] ".json_encode ($o));

		$o['i_']=0;
		$rt = rest_uri_post ($u, $suffix, $id, $o, $p);

		// todo: additional actions eg wrap, notifications, etc

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
	

session_set_save_handler ("ss_open", "ss_close", "ss_read", "ss_write", "ss_destroy", "ss_gc");
session_name (THE_APP_SESSION_NAME);
session_start ();


