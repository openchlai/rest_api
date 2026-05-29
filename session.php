<?php

function ss_id ($id=null)
{
	// error_log ("SESSION ID: ".$id." | ".$_SERVER["HTTP_AUTHORIZATION"]);

	if (isset ($_SERVER["HTTP_AUTHORIZATION"])) 
	{
		$vv = explode (" ",$_SERVER["HTTP_AUTHORIZATION"]);
		if (strcasecmp ($vv[0], "bearer")==0) 
		{
			//error_log ("SESSION AUTH: BEARER ".$vv[1]." |".$id);
			return $vv[1];
		}
	}
	return session_id (); // get php session-id
}

function ss_open ($path, $name)
{
	// error_log ("SESSION OPEN: ".$path." ".$name);
	return true;
}

function ss_close ()
{
	// error_log ("SESSION CLOSE: ");
	return true;
}

function ss_read ($id)
{
	//b error_log ("SESSION READ: ");
	$id = ss_id ($id);
	$q = "SELECT data FROM session WHERE ss_id=?";
	$argv = [$id];
	$row = qryp ($q, "s", $argv, 1);
	if ($row)
	{
		// error_log ("SESSION READ: ".$id." |". $row[0	]);
		$vv = explode ("|",$row[0]);	
		$_SESSION["cc_user_id"] = $vv[0];
		$_SESSION["cc_user_usn"] = $vv[1];
		$_SESSION["cc_user_agentno"] = $vv[2];
		$_SESSION["cc_user_exten"] = $vv[3];
		$_SESSION["cc_user_contact_id"] = $vv[4];
		$_SESSION["cc_user_role"] = $vv[5];
		$q = "UPDATE session SET access=UNIX_TIMESTAMP(Now()) WHERE ss_id=?";
		qryp ($q,"s",$argv, 4);
	}
	return "";
}

function ss_write ($id, $data)
{
//	error_log ("SESSION WRITE: ".$id." |".$data);
	return true;
}

function ss_del ($row)
{
	$user_id = $row[3];

	$o = [];
	//ami_leaveq ($o, $_SESSION['cc_user_agentno'], "1");

	error_log ("SESSION del sessid=".$row[1]."--------------");
	$q = 'INSERT INTO session_log(created_on, ss_action, ss_start, ss_end, ss_id, ss_ip, user_id, user_usn, user_role, data, access) VALUES(UNIX_TIMESTAMP(Now()),"ss_del",?,UNIX_TIMESTAMP(Now()),?,?,?,?,?,?,?)';
	$argv = array ($row[0], $row[1], $_SERVER["REMOTE_ADDR"], $row[3], $row[4], $row[5], $row[6], $row[7]);
	qryp ($q, "ssssssss", $argv, 2);
	$q = "DELETE FROM session WHERE ss_id=?";
	$argv = array ($row[1]);
	qryp ($q, "s", $argv, 3);
	return true;
}

function ss_destroy ($id)
{
	error_log ("SESSION DESTROY: ".$id);
	$q = "SELECT created_on, ss_id, ss_ip, user_id, user_usn, user_role, data, access FROM session WHERE ss_id=?";
	$argv = [$id];
	$row = qryp ($q,"s",$argv,1,"db2");
	if ($row) ss_del ($row);
	return true;
}

function ss_gc ($n)
{
	error_log ("SESSION GC: ".$n); // // logout after 5 minutes of inactivity
	$q = "SELECT created_on, ss_id, ss_ip, user_id, user_usn, user_role, data FROM session WHERE UNIX_TIMESTAMP(Now())-access>36000"; 
	$argv = [];
	$res = qryp ($q,"",$argv,0,"db2");
	if (!$res) return true;
	$row = mysqli_fetch_row ($res);
	while ($row)
	{
		ss_del ($row);
		$row = mysqli_fetch_row ($res);
	}
	return true;
}

function ss_new ($row) // id, usn, agentno, exten, contact_id, contact_role
{
	error_log ("SESSION NEW regen--------------");
	session_regenerate_id (true);

	$id = session_id ();
	error_log ("SESSION ss_new=".$id."---------------");
	$s = $row[0]."|".$row[1]."|".$row[2]."|".$row[3]."|".$row[4]."|".$row[5]."|";
	$_SESSION["cc_user_id"] = $row[0];
	$_SESSION["cc_user_usn"] = $row[1];
	$_SESSION["cc_user_agentno"] = $row[2];
	$_SESSION["cc_user_exten"] = $row[3];
	$_SESSION["cc_user_contact_id"] = $row[4];
	$_SESSION["cc_user_role"] = $row[5];

	$q = 'INSERT INTO session(created_on, access, ss_id, ss_ip, user_id, user_usn, user_role, data) VALUES(UNIX_TIMESTAMP(Now()),UNIX_TIMESTAMP(Now()),?,?,?,?,?,?)';
	$argv = array ($id, $_SERVER["REMOTE_ADDR"], $row[0], $row[1], $row[5], $s);
	qryp ($q, "ssssss", $argv, 2);

	$q = 'INSERT INTO session_log(created_on, ss_action, ss_start, ss_id, ss_ip, user_id, user_usn, user_role, data) VALUES(UNIX_TIMESTAMP(Now()),"ss_new",UNIX_TIMESTAMP(Now()),?,?,?,?,?,?)';
	qryp ($q, "ssssss", $argv, 2);
}

function ss_new_phone ($otp_id, $addr_id, $addr, $role)
{
	error_log ("SESSION regen-pre--------------");
	session_regenerate_id (true);

	$id = session_id ();
	$s = 'cc_otp_id|s:'.strlen($otp_id).':"'.$otp_id.'";';
	$s .= 'cc_addr_id|s:'.strlen($addr_id).':"'.$addr_id.'";';
	$s .= 'cc_addr|s:'.strlen($addr).':"'.$addr.'";';
	error_log ("SESSION new sessid (reset)=".$id." | ".$s);
	$q = 'INSERT INTO session(created_on, access, ss_id, ss_ip, user_usn, user_role, data) VALUES(UNIX_TIMESTAMP(Now()),UNIX_TIMESTAMP(Now()),?,?,?,?,?)';
	$argv = array ($id, $_SERVER["REMOTE_ADDR"], $addr, $role, $s);
	qryp ($q, "sssss", $argv, 2);
	$q = 'INSERT INTO session_log(created_on, ss_action, ss_start, ss_id, ss_ip, user_usn, user_role, data) VALUES(UNIX_TIMESTAMP(Now()),"ss_new",UNIX_TIMESTAMP(Now()),?,?,?,?,?)';
	qryp ($q, "sssss", $argv, 2);
}

function ss ()
{
	$ssid = ss_id("");
	echo '"ss":[["'.$ssid.'"';
	if (isset ($_SESSION["cc_user_id"])) 
	{
		echo ',"'.$_SESSION["cc_user_id"].'", "'.$_SESSION["cc_user_usn"].'", "'.$_SESSION["cc_user_role"].'","'.$_SESSION["cc_user_contact_id"].'"'; //
	}
	echo ']]'; 
}

function change_auth (&$o, &$p)
{
	$user_id="0";
	$e = 0;

	if (isset ($o["pass0"])) 
	{
		$user_id = $_SESSION["cc_user_id"];
		$pass = hash ("sha256", $o["pass0"]); 	
		$argv = array ($user_id, $pass);
		$row = qryp ("SELECT id FROM auth WHERE id=? AND pass=?","ss",$argv,1);
		if ($row==NULL)
		{
			$e += _val_error ("auth",0,"pass0","","INVALID","That is not your current password.");
		}
	}
	
	if (isset ($o["otp"])) 
	{
		$pass = hash ("sha256", $o["otp"]); 	
		$argv = array ($pass);
		// $row = qryp ("SELECT id FROM auth WHERE otp=1 && updated_on<UNIX_TIMESTAMP(Now())+600 && pass=?","s",$argv,1);
		$row = qryp ("SELECT id FROM auth WHERE otp=1 && pass=?","s",$argv,1);
		if ($row==NULL)
		{
			$e += _val_error ("auth",0,"otp",$o["otp"],"INVALID","Invalid OTP.");
		}
		if ($row!=NULL) $user_id = $row[0];
	}
	
	if (!isset ($o["pass0"]) && !isset ($o["otp"])) 
	{
		$e += _val_error ("auth",0,"pass0","","INVALID","Invalid Request.");
	}
	
	if (!isset ($o["pass1"]))
	{
		$e += _val_error ("auth",0,"addr",($o["pass1"]),"INVALID","Password is blank");
	}

	if ($o["pass1"]!=$o["pass2"])
	{
		$e += _val_error ("auth",0,"addr",($o["pass1"]),"INVALID","Passwords Do no match");
	}

	if (strlen ($o["pass1"])<8)
	{
		$e += _val_error ("auth",0,"addr",($o["pass1"]),"INVALID","Password Must be 8 or more characters");
	}

	if ($e>0) return 412;

	$newpass = hash ("sha256", $o["pass1"]); 
	$q = "UPDATE auth SET pass=?, otp='0', updated_on=UNIX_TIMESTAMP(Now()) WHERE id=? AND pass=?";
	$argv = array ($newpass, $user_id, $pass);
	$af = qryp ($q,"sss",$argv,3);

	error_log ("[change_auth] ".$af." |". json_encode ($argv));

	$s = "Password Changed";
	if ($af<1) $s = "New Password same as Old Password";

	header ("HTTP/1.0 202 OK");
	header ('Content-Type: application/json');
        echo '{"auth_nb":[["'.$s.'"]]}';
}

function reset_auth (&$o, &$p)
{
	$b = array ("users","","dup","id",".id", NULL, "id:user_id", "contact_id:contact_id", "contact_email:contact_email", "contact_phone:contact_phone", "contact_fullname:contact_fullname"); // get user_id 
	_dup ($b, $o, $p);
	$newpass = _rands (6,"num"); // generate random otp pass
	$newhash = hash ("sha256", $newpass); 
	$q = "UPDATE auth SET pass=?, otp='1', updated_on=UNIX_TIMESTAMP(Now()) WHERE id=?"; // NB: flagged as one time pass (otp)
	$argv = array ($newhash, $p['user_id']);
	$rt = qryp ($q,"ss",$argv,3);
	$p["otp"] = $newpass;
	return $rt;
}

function reset_auth_admin (&$o, &$p)
{
	error_log (" SESSION [_reset_auth_admin] ".$_SESSION["cc_user_role"]."-------------- [o] ".json_encode ($o));
	$e = 0;
	if ($_SESSION["cc_user_role"]!="99")
	{
		$e += _val_error ("auth",0,"role","","INVALID","Only Admin can reset password.");
		return 412;
	}
	$rt = reset_auth ($o, $p);	
	error_log (" SESSION [_reset_auth_admin] ".$_SESSION["cc_user_role"]."-------------- [o] (".$rt.") ".json_encode ($o));
	if ($rt>0) return 202;
	$e += _val_error ("auth",0,"role","","INVALID","Password Reset Error. Please Try Again.");
	return 412;
}

function auth ($o)
{
	$auth = [];

	if (isset ($_GET["logout"]))
	{
		error_log ("SESSION LOGOUT ".ss_id ());
		$_SESSION = array ();
		session_destroy ();
		return 401;
	}

	if (isset ($_SERVER["HTTP_AUTHORIZATION"])) 
	{
		$vv = explode (" ",$_SERVER["HTTP_AUTHORIZATION"]);

		if (strcasecmp ($vv[0], "basic")==0) 
		{
			$a = base64_decode ($vv[1], TRUE);
			if ($a) 
			{
				$aa = explode (":",$a);
				$auth["auth_u"] = $aa[0];
				$auth["auth_p"] = $aa[1];
				// error_log ("AUTH: BASIC: ".json_encode ($auth));
			}
		}
	}

	if (isset ($auth["auth_u"]))
	{
                $usn = $auth["auth_u"];
                $pass = hash ("sha256", $auth["auth_p"]); 
                $q = "SELECT id, usn, agentno, exten, contact_id, role FROM auth WHERE is_active='1' AND usn=? AND pass=?";
                $argv = [$usn, $pass];
		$row = qryp ($q, "ss", $argv, 1);  
                if ($row==NULL)
                {
                	_val_error ("auth", 0, "login", $usn, "REQUIRED", "Invalid Username or Password!");
                        return 412; // invalid username or password
                }
		ss_new ($row);
	}

	if (isset ($_SESSION["cc_user_id"]))
	{
		return 0; 
	}

	return 401; // Authentication Required
}

?>