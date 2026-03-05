<?php
include "/var/www/html/helpline/config.php"; //
//include "../config.php";

$db = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");
$db2 = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");

include "model.php";
include "model_k.php";
include "../lib/rest.php";
include "../lib/session.php";
include "../lib/XLSXbuf.php"; 
include "../lib/rpc.php"; 
include "model_qa.php";
include "model_qa_k.php";
//include "email.php";

$FN = ["resetAuthAdmin"=>1, "sendOTP"=>1, "changeAuthOTP"=>1, "changeAuth"=>1, "dash"=>1, "wallonly"=>1, "agent"=>1, "chan"=>1, "sup"=>1, "msg"=>1]; // non-crud endpoints

function copy_from_pabx ($uid) // copy from archive
{
	$url = $GLOBALS["RECORDING_ARCHIVE_URL"].$uid;
	$r = array ('data'=>'', 'info'=>0);
	$ch = curl_init ();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
//      curl_setopt ($ch, CURLOPT_VERBOSE, 2);
	$r['data'] = curl_exec ($ch);
	$r['info'] = curl_getinfo ($ch);
	error_log ("[copy_from_archive] ". $url." ".$r['info']['http_code']);
	if ($r['info']['http_code']==200)
	{
		header ("Content-Type: " . $r['info']['content_type']);
		header ("Content-Length: " . strlen ($r['data']));
		// header ('Content-Description: File Download');
		header ('Content-Disposition: attachment; filename="'.$uid.'.ogg"');
		// header ('Expires: 0');
		header ('Cache-Control: no-cache');
		// header ('Pragma: public');
		header ("Content-Transfer-Encoding: binary");
		echo $r['data'];
		// error_log (json_encode ($r['info']));
		exit (0);
	}
}

function muu ($cmd, $args, $timeout=30) // timeout
{
	//$url = "http://127.0.0.1:8383/".$cmd."/".$args;
	$url = "https://".$GLOBALS["VA_SIP_HOST"]."/".$cmd."/".$args;   //error_log($url);
	$r = array ('data'=>'', 'info'=>0);
	$ch = curl_init ();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
	//curl_setopt ($ch, CURLOPT_VERBOSE, 2);
	//curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, true);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$r['data'] = curl_exec ($ch);
	$r['error'] = curl_error($ch);
	$r['info'] = curl_getinfo ($ch);
	curl_close ($ch);
	error_log ("[muu] ". $cmd."/".$args." | ".$r['info']['http_code']."|".$r['error']."|".$url);
	//error_log ("[muu] >> ". json_encode($r));
	return $r;
}

function muu_ ($cmd, $args) // nb: does not wait for response
{
	$url = "tls://".$GLOBALS["VA_SIP_HOST"].":8384";
	$req = "GET /".$cmd."/".$args." HTTP/1.1\r\n\r\n";
	$ctx = stream_context_create (['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
	$fp = stream_socket_client ($url, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
	if (!$fp) 
	{
		error_log ("muu_: Connection failed - $errstr ($errno)");
		return;
	}
	stream_set_blocking ($fp, false);
	fwrite ($fp, $req);
	fflush ($fp);
	fclose ($fp);
	error_log ("muu_: dont wait (".$cmd.$args.")-------");
}

function case_notification ($src, $from, $to, $to_id, $to_exten, $msg,  $ca_id)
{
	$o_ = [];
	$p_ = [];
	$o_['i_']=0;
	$o_['contact_id'] = "-1";
	$o_['ca_id'] = $ca_id;
	$o_["src"] = $src;
	$o_["src_ts"] = _val_id()/10000;
	$o_["src_uid"] = "notify"._val_id();
	$o_["src_callid"] = $o_["src_uid"];
	$o_["src_address"] = $from;
	$o_["src_usr"] = $to_exten;	// nb: exten is nearly as good as user_id
	$o_["src_vector"] = "2"; 	// leg1 for notify is pseudo
	// $o_["src_status"] = "0-2-2";	// 
	$o_["action"] = "notify";
	error_log ("[notify] ".json_encode($o_));
	$rt = rest_uri_post ("activities", "", NULL, $o_, $p_);

	$s = "msg?ctx=notify&chan=casenotification&cid=000&exten=".$to_exten."&payload=notify&"; // nb static same channel (per user)
     muu ("ati", $s);
}

function message_out (&$o, &$p)
{
	// if (!isset ($o["partial"])) // complete
	{
		$hdrs = array ("Content-Type: application/json");
		$postdata = array
		(
			"username"=>$GLOBALS["API_GATEWAY_USN"],
			"password"=>$GLOBALS["API_GATEWAY_PASS"]  
		);
		$token = ""; // kurl ($GLOBALS["API_GATEWAY_AUTH"], 60, json_encode($postdata), $hdrs);
		$api_url = $GLOBALS["API_GATEWAY_SEND_MSG"];
		$hdrs = array ("Content-Type: application/json");// , ("Authorization: Token ".json_decode($token["data"],true)["token"]));
		$postdata = array 
		(
			//"chat_sender"=>$o['src_address'], 
			//"chat_receiver"=>$p['src_usr'], 
			//"chat_message"=>$o['src_msg'], 
			//"chat_session"=>$o['src_callid'], 
			//"chat_channel"=> $o["src"],
			//"chat_source"=>"OUTBOX"//"HELPLINE"
			"recipient"=>$o['src_address'],
			"message_type"=>"text",
			"content"=>$o["src_msg"]
		);
		if (isset ($o["close"]))
		{
			// $api_url = $GLOBALS["API_GATEWAY_CLOSE_MSG"].$o["src_callid"]."/close/";
			// $postdata = array ("chat_source"=>"HELPLINE");
		}
		error_log ("[postdata] ".json_encode($postdata));
		kurl ($api_url, 60, json_encode($postdata), $hdrs);
	}

	$s = "read?uid=".$o["src_uid"]."&";
	if (isset ($o["close"])) $s .= "args=close&"; // close session
     muu ("ati", $s); // update notification status
}

function _message_in (&$o, &$p)
{
	error_log ("   message_in -----". json_encode ($o));
	// curl -k "https://127.0.0.1/helpline/api/msg/" -H "Authorization: Bearer vd0ni7tps9la0fs6344mjikmt3" -H "Content-Type: application/json" -d '{"channel":"chat", "timestamp":"1680783378", "session_id":"1680783378", "message_id":"1680783378/00001", "from":"0700112233", "message":"how r u today?"}' -v
	$o_ = [];
	$o_['i_']=0;
	$o_["src"] = $o["channel"];
	$o_["src_uid"] = $o["message_id"]; 
	$o_["src_callid"] = $o["session_id"]; 
	$o_["src_address"] = $o["from"];
	$o_["src_msg"] = $o["message"];	// base64 encode
	$o_["src_ts"] = $o["timestamp"];
	$o_["src_vector"] = "1";
	$o_['src_mime'] = $o["mime"];
     if ($o_['src']=='safepal') 	$o_['src_mime'] = "application/json";
	if ($o_["src"]=="aii") 		$o_['src_usr']  = "*"; // dont acd! (by ati process)
	$rt = rest_uri_post ("messages", "", NULL, $o_, $p);
	if ($rt==201) 
	{
		$msg = $o_["src_msg"];
		$msg = preg_replace ('/[[:^print:]]/', ' ', $msg); 
		$msg = str_replace ([' ', '&', '<', '>', "\r","\n","\t"], ['_', '', '', '', '', '', ''], $msg); 
		if (strlen ($msg)>30) $msg = substr ($msg,0,30)."..."; // truncate to fit in notif 
		$s = "msg?ctx=".$o_['src']."&chan=".$o_['src_callid']."&cid=".$o_['src_address']."&payload=".$msg."&";
		if (isset($o_["src_usr"])) $s .= "exten=".$o_["src_usr"]."&"; // dont agtk!
		muu ("ati", $s); // post to notif_queue

		$aa = [];
		$fo = [];
		$rt = rest_uri_get ("messages", "", $p["msg_id"], $fo, $p, $aa);
		if ($rt==200)
		{
			return rest_uri_response ("messages", "", $p["msg_id"], $o, $p, $aa, 201);
		}
	}
	error_log ("   message_in ----- rt:".$rt." | ". json_encode ($p));
	return $rt;
}

function _sup (&$o) // "action=", "exten="
{
	$usn = $_SESSION["cc_user_usn"];
	$exten = $_SESSION["cc_user_exten"];
	$role = $_SESSION["cc_user_role"];

	error_log ("[sup] request ".json_encode ($o)." | ".$usn. " ". $exten);
	
	if ($role!=2 && $role!=99) return 404;
	
	$r = muu ("ami","sync?c=-1&");
	$o_ = json_decode ($r['data'], true);
	$k = array_keys ($o_['channels']);
	$n = count ($k);
	$me = null;
	$agent = null;
	for ($i=0; $i<$n; $i++) 
	{
		$ch = $o_['channels'][$k[$i]];
		if (strlen ($ch[16])>0) continue;
		if ($me===null && $ch[4]==$exten && $ch[5]=="Supervisor") //substr($ch[43],0,10)=="supervisor") 
		{ 
			$me=$k[$i]; 
			continue; 
		}
		if ($agent===null && isset ($o["exten"]) && $ch[0]==$o["exten"]) // find agent uid
		{
			$agent=$k[$i];
			continue;
		}
	}
	$s = "sup?action=".$o['action']."&usr=".$usn."&exten=".$exten;
	if (isset ($o["exten"])) $s .= "&agent=".$o['exten'];
	if ($me!=null) $s .= "&chan=".$o_['channels'][$me][3];
	if ($agent!=null) $s .= "&uid=".$agent;
	muu ("ami",$s); 
	header ("HTTP/1.0 203 Wait");
	header ('Content-Type: application/json');
	$ts = time ();
	echo '{ "action":[["'.$ts.'","'.$o['action'].'"]] }'; 
	return 201;
}

function _chan (&$o)
{
	$usn = $_SESSION["cc_user_usn"];
	$exten = $_SESSION["cc_user_exten"];
	
	error_log ("[chan] request ".json_encode ($o)." | ".$usn. " ". $exten);
	
	if ($o['action']>2 && $o['action']<6) // cb_xfer, cb_conference, cb_resume
	{
		$s = "redirect?action=".$o['action']."&exten=".$o['cbid']."&chan1=".$o['chan']."&chan2=".$o['chan2']."&";
		muu ("ami",$s); 
	}

	if ($o['action']>0 && $o['action']<3) // cb_invite, cb_dial
	{
		if (!isset ($o['add'])) $o['add']='';
			
		if (isset ($o['usn_']))
		{
			$o['add'] = $o['usn_'];
		}
		
		if (isset ($o['user_id']))
		{
			$row = qryp ("SELECT exten FROM auth WHERE id=?", "s", [$o['user_id']], 1);
			if ($row)  $o['add'] = $row[0];
		}
		
		if (strlen ($o['add'])!=3 || !is_numeric ($o['add'])) // validate exten
		{
			header ("HTTP/1.0 412 Wait");
			header ('Content-Type: application/json');
			echo '{ "errors":[["error","Invalid Extension"]] }'; 
			return 413;
		}
	
		$o['exten'] = 'CB'.$o['src_uid'];
		if (isset ($o['cbid']) && strlen ($o['cbid'])>0) { $o['exten'] = $o['cbid']; $o['chan2'] = ''; } // dont redirect peer; if peer already in bridge
		if (!isset ($o['chan2'])) $o['chan2'] = '';
		$s = "redirect?action=".$o['action']."&usr=".$o['usr']."&exten=".$o['exten']."&chan1=".$o['chan']."&chan2=".$o['chan2']."&add=".$o['add']."&ref=".$o['src_address']."&";
		muu ("ami", $s); 
	}
	
	if ($o['action']==0)
	{
		$s = "hangup?chan=".$o['chan3']."&";
		muu ("ami", $s); 
	}

	$ts = time ();
	$add = '';
	if (isset ($o['add'])) $add = $o['add'];
	header ("HTTP/1.0 203 Wait");
	header ('Content-Type: application/json');
	echo '{ "action":[["'.$ts.'","'.$o['action'].'","'.$add.'"]] }'; 
	return 202;
}

function _agent (&$o)
{
	$usn = $_SESSION["cc_user_usn"];
	$exten = $_SESSION["cc_user_exten"];
	$role = $_SESSION["cc_user_role"];

	error_log ("[agent] request ".json_encode ($o)." | ".$usn. " ". $exten);

	if ($o['action']=='0') // get chan id
	{
		$r = muu ("ati","sync?c=-1&");
		$o_ = json_decode ($r['data'], true);
		$k = array_keys ($o_['ati']);
		$n = count ($k);
		$me = null;
		for ($i=0; $i<$n; $i++) if ($o_['ati'][$k[$i]][0]==$exten) { $me=$k[$i]; break; }
		$s = "leave?uid=".$me."&";
		if ($me!=null) muu ("ati",$s); 
		if ($me==null) error_log (" * [ati agent not found ] ");

		$r = muu ("ami","sync?c=-1&");
		$o_ = json_decode ($r['data'], true);
		$k = array_keys ($o_['channels']);
		$n = count ($k);
		$me = null;
		for ($i=0; $i<$n; $i++) if ($o_['channels'][$k[$i]][0]==$exten) { $me=$k[$i]; break; }
		$s = "usr?action=0&call_id=".$me."&";
		if ($me!=null) muu ("ami",$s); 
		if ($me==null) error_log (" * [ami agent not found ] ");
	}

	if ($o['action']=='1')
	{
		//$k = ['any','sun','mon','tue','wed','thu','fri','sat'];
		//$q = "SELECT UNIX_TIMESTAMP(Date(Now())), Dayofweek(Now())";
		//$row = qryp ($q, "", [], 1);
		//$at = [];
		//$av = [$row[0], $row[0], _S('cc_user_id')];	
		//$q = "SELECT workinghour.campaign_id, campaign_campaign FROM workinghour INNER JOIN member ON workinghour.campaign_id=member.campaign_id && source='1' && dt0<=? && dt1>=? && ".$k[$row[1]]."='1' && user_id=?";  // fetch active campaigns
		//$res = qryp ($q, "sss", $av, 0);
		$campaigns = "0,";
		$outbound = 0;
		
		//while ($row = mysqli_fetch_row ($res))
		//{
		//	error_log ("MBR: [".$row[0]." ".$row[1]."]");
		//	$v = $row[0];
		//	if ($row[1]==2) { $v="O"; $outbound++; }
		//	if ($outbound>1) continue;
		//	$campaigns .= $v.",";
		//}
		//if ($campaigns=="") $campaigns = "0,";

		// if ($role==6) // login to ati only
		{
			$s = "join?cid=".$exten."&";
			muu ("ati",$s); 
			// $campaigns = "Z,";
			// $campaigns .= "Z,";
		}

		$s = "usr?action=1&usr=".$exten."&interface=2&exten=".$GLOBALS['VA_SIP_USER_PREFIX'].$exten."&queue=".$campaigns."&";
		muu ("ami",$s); 

	}
	
	if ($o['action']=='4') // enable auto answer
	{
		$s = "usr?action=4&usr=".$exten."&";
		muu ("ami",$s); 
	}
	
	if ($o['action']=='5') // disable auto answer
	{
		$s = "usr?action=5&usr=".$exten."&";
		muu ("ami",$s); 
	}

	return 203;
}

function _wallonly (&$o, &$p)
{
	muu_ ("rpt",""); // sync stats from rpt // rpty ();

	if (isset ($_GET["metrics"]))
	{
		if (isset ($_GET["dash_period"]))
		{
			$_GET["dt"] = _str2ts ("today");
               //  muu ("rpt",""); // sync stats from rpt // rpty ();
		}

		header("HTTP/1.1 200");
		header ('Content-Type: application/json');
		echo '{';

		$_SESSION["cc_user_role"] = "2";
		$aa = [];
		$av = [];
		$fo = $_GET;
		$rt = rest_uri_get ("calls", "", NULL, $fo, $p, $aa);
		if ($rt==200)
		{
			rest_uri_response ("calls", "", NULL, $o, $p, $aa, 0);
		}

		echo ', "stats":{ ';

		$q = "SELECT UNIX_TIMESTAMP(Date(Now())) dt, UNIX_TIMESTAMP(CONCAT(YEAR(Now()),'-',MONTH(Now()),'-01')) mn";
		$row = qryp ($q, "", $av, 1);
		$dt_now = $row[0];
		$mn_now = $row[1];

		$q = "SELECT COUNT(id) FROM kase WHERE dt=?";
		$av_ = [$dt_now];
		$row = qryp ($q, "s", $av_, 1);
		echo '"cases_today":"'.$row[0].'",';

		$q = "SELECT COUNT(id) FROM kase WHERE status=2";
		$av_ = [$mn_now];
		$row = qryp ($q, "", $av, 1);
		echo '"cases_closed_this_month":"'.$row[0].'",'; 

		$q = "SELECT COUNT(id) FROM kase WHERE status=1";
		$row = qryp ($q, "", $av, 1);
		echo '"cases_ongoing_total":"'.$row[0].'",';

		$q = "SELECT COUNT(id) FROM kase";
		$row = qryp ($q, "", $av, 1);
		echo '"cases_total":"'.$row[0].'",';

		$q = "SELECT COUNT(id) FROM chan WHERE dt=?";
                $av_ = [$dt_now];
                $row = qryp ($q, "s", $av_, 1);
                echo '"calls_today":"'.$row[0].'",';

		$q = "SELECT COUNT(id) FROM chan";
                $av_ = [$dt_now];
                $row = qryp ($q, "", $av, 1);
                echo '"calls_total":"'.$row[0].'"';

/*
		$q = "SELECT hangup_status, has_case_id, count(hangup_status) FROM chan WHERE hangup_status=5 GROUP BY hangup_status, has_case_id";
		$res = qryp ($q, "sss", $av, 0);
		while ($row = mysqli_fetch_row ($res))
		{
                	echo '"has_case_id_'.$row[1].'":"'.$row[2].'",';
		}
		
		$q = "SELECT hangup_status, has_case_id, count(hangup_status) FROM chan WHERE dt=? && hangup_status=5 && has_case_id='1'";
		$av_ = [$dt_now];
                $row = qryp ($q, "s", $av_, 1);
                echo '"responsive_calls_today":"'.$row[2].'"';
 */
		echo '}';

		echo '}';
          return 200;
	}

	if (isset ($_GET["stats"]))
	{
		// $q = "SELECT SUM(if(hangup_status_txt='answered',1,0)), SUM(if(hangup_reason='phone' and vector=2 and hangup_status<5 ,1,0)), SUM(talk_time) FROM chani WHERE dt=? AND usr=?";
		$q = "SELECT SUM(if(hangup_status_txt='answered',1,0)), SUM(if(hangup_status<5 && peer_hangup!='1',1,0)), SUM(if(hangup_status_txt='answered',src_status_duration,0)) FROM activity WHERE dt=? AND src_usr=? && src='call' && src_vector='2'";
		$_GET["dt"] = _str2ts ("today");
		$av = [__VESC(_G("dt")), __VESC(_G("exten"))];
        	$row = qryp ($q, "ss", $av, 1);
		echo '{ "stats":[';
		if ($row) echo '["'.$row[0].'","'.$row[1].'","'.$row[2].'"]';
		echo ']}'; 
		return 200;
	}

	$q = "SELECT id, usn, exten, role FROM auth WHERE exten=?";
	$av = [__VESC(_G("exten"))];
	$row = qryp ($q, "s", $av, 1);
	if (!$row) $row = ["","","",""];
	header("HTTP/1.0 200 OK");
	header ('Content-Type: application/json');
	echo '{ "users":[["'.$row[0].'","'.$row[1].'"]] }';
	return 200;
}

function _dash (&$o, &$p)
{
	$rights = $GLOBALS[("RIGHTS_".$_SESSION["cc_user_role"])];
	$dash_period = "this_month";
	$dash_gbv = "both";
	$dash_src = "all";
	if (isset ($_GET["dash_period"])) $dash_period = $_GET["dash_period"];
	if (isset ($_GET["dash_gbv"])) $dash_gbv = $_GET["dash_gbv"];
	if (isset ($_GET["dash_src"])) $dash_src = $_GET["dash_src"];

	$_gbv_ = array ("both"=>"", "vac"=>"0", "gbv"=>"1" );	
	$_src_ = array ("all"=>"", "call"=>"call", "sms"=>"sms", "email"=>"email", "social"=>"social", "walkin"=>"walkin" );
	$dash_ts = _str2ts ($dash_period);
	
	echo '"dash":[["'.$dash_period.'","'.$dash_gbv.'","'.$dash_src.'","'.$dash_ts.'","'.$_gbv_[$dash_gbv].'","'.$_src_[$dash_src].'"]]';
		
	$aa = array ("ctx"=>"", "f"=>"", "w"=>"", "s"=>"", "sort"=>"", "lim"=>"");
	$av = [];
	ctx_rights ("cases", $aa, $av, $p, $rights["cases"]);
	if (strlen ($_gbv_[$dash_gbv])>0) k_c ("kase", "gbv_related", explode (",", $_gbv_[$dash_gbv]), $aa, $av);
	if (strlen ($dash_ts)>0) 	  k_d ("kase", "created_on", explode (";", $dash_ts), $aa, $av);
	$q = "SELECT src, COUNT(id) FROM kase ".$aa["w"]." GROUP BY src";
	error_log ("[dash] ".$q." | ".json_encode ($av)." | ".$_SESSION["cc_user_role"]);
	$res = qryp ($q, $aa["s"], $av);
	$i = 0;
	$tot = 0;
	echo ',"case_source":{';
	while ($row = mysqli_fetch_row ($res))
	{
		if ($i>0) echo ","; 
		echo '"'.$row[0].'":["'.$row[0].'","'.$row[1].' Cases"]';
		$i++;
		if ($row[0] && strlen ($row[0])>0) $tot += $row[1]; 
	}
	if ($i>0) echo ","; 
	echo '"total":["total","'.$tot.' Cases"]}';
	return 200;
}

function _home (&$o, &$p)
{
	if (!isset ($GLOBALS[("RIGHTS_".$_SESSION["cc_user_role"])])) return 403;
	$rights = $GLOBALS[("RIGHTS_".$_SESSION["cc_user_role"])];
	$aa = [];
	$fo = [];
		
        header ("HTTP/1.0 200 OK");
	header ('Content-Type: application/json');
        echo "{";
       
	ss ();
	
	echo ",";
	if (rest_uri_get ("auth","", $p["auth_id"], $fo, $p, $aa)==200) rest_uri_response ("auth","", $p["auth_id"], $o, $p, $aa, 0);
        	
	echo ",";
	if (rest_uri_get ("contacts","", "0", $fo, $p, $aa)==200) rest_uri_response ("contacts","", "0", $o, $p, $aa, 0); // load contacts_k
	
	echo ",";
	if (rest_uri_get ("categories","", "-9", $fo, $p, $aa)==200) rest_uri_response ("categories","", "-9", $o, $p, $aa, 0);

	echo ",";
	if (rest_uri_get ("calls","", "0", $fo_, $p, $aa)==200) rest_uri_response ("calls","", "0", $o, $p, $aa, 0); // load calls

	echo ",";
	if (rest_uri_get ("messages","", "0", $fo, $p, $aa)==200) rest_uri_response ("messages","", "0", $o, $p, $aa, 0); // load messages_k

	echo ",";
	if (rest_uri_get ("dispositions","", "0", $fo, $p, $aa)==200) rest_uri_response ("dispositions","", "0", $o, $p, $aa, 0); // load dispositions_k

	//echo ",";
	//if (rest_uri_get ("qas","", "0", $fo, $p, $aa)==200) rest_uri_response ("qas","", "0", $o, $p, $aa, 0); // load qas_k

	echo ",";
	if (rest_uri_get ("cases","", "0", $fo, $p, $aa)==200) rest_uri_response ("cases","", "0", $o, $p, $aa, 0); // also load case_subs
	
	echo ",";
	if (rest_uri_get ("case_activities","", "0", $fo_, $p, $aa)==200) rest_uri_response ("case_activities","", "0", $o, $p, $aa, 0); // load calls

	//echo ","; 
	//$fo_ = ["_c"=>"1000", "root_id"=>$GLOBALS["AGE_GROUP_ROOT_ID"] ];
	//if (rest_uri_get ("categories","_age_group", NULL, $fo_, $p, $aa)==200) rest_uri_response ("categories","_age_group", NULL, $o, $p, $aa, 0);

	echo ",";
	$fo_=["_c"=>"10"];
	if (rest_uri_get ("activities","", NULL, $fo_, $p, $aa)==200) rest_uri_response ("activities","", NULL, $o, $p, $aa, 0); // load activities_k

	echo ",";
	$fo_=["_c"=>"10", "action"=>"notify"];
	if (rest_uri_get ("activities","_notify", NULL, $fo_, $p, $aa)==200) rest_uri_response ("activities","_notify", NULL, $o, $p, $aa, 0); // load activities

	echo ",";
	_dash ($o, $p);

     echo "}";
	return 200;
}

function _request_ ()
{
	$u = "";
	$suffix = "";
	$id = NULL;
	$o = [];
	$p = [];
	$fo = [];

	// error_log ("[request] ".$_SERVER["REQUEST_URI"]);

	$rt = rest_uri_parse ($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], 3, $u, $suffix, $id, $o);
	error_log ("[request] " . $u . "/" . $id ."|". $rt);
	if ($rt!=0) return $rt;
	
	if ($u=="wallonly") return _wallonly ($o, $p);

	// todo: reset_auth // aka sendOTP

	if ($u=="changeAuthOTP") return change_auth ($o, $p); // aka verify OTP

	if (isset ($_GET["logout"]))
	{
		$o_ = ["action"=>"0"]; // remove member from queue(s)
		_agent ($o_);
	}
	
	$rt = auth ($o);

	if ($rt!=0) return $rt;

	error_log ("auth (".$u.$suffix.") ---------------------------------------------------------|"._S("cc_user_usn"));	
	$p["auth_id"] = _S("cc_user_id");
	$p["auth_exten"] = _S("cc_user_exten");
	$p["auth_usn"] = _S("cc_user_usn");
	$p["profile_id"] = _S("cc_user_contact_id"); 
	$p["auth_is_active"] = "1";
		
	if (strlen($u)<1) return _home ($o,$p);

	if ($u=="resetAuthAdmin") 
	{
		$rt = reset_auth_admin ($o, $p);
		if ($rt==202)
		{
			error_log ("[otp] ".$p["otp"]);
			$p["recipient_id"] = $p["user_id"];
			email_send ($o, $p, "RESET_AUTH_ADMIN");
			header ("HTTP/1.0 202 OK");
			header ('Content-Type: application/json');
        		echo '{"auth_nb":[["info","Password Reset Successful. Email sent to: '.$p["contact_email"].'"]]}';
			// echo '{"auth_nb":[["info","Password Reset Successful. The new password is '.$o["otp"].'"]]}';
		}
		return $rt;
	}
	
	if ($u=="changeAuth") return change_auth ($o, $p); // aka change passwd
	
	if ($u=="agent") return _agent ($o);
	
	if ($u=="chan") return _chan ($o);
	
	if ($u=="sup") return _sup ($o);
	
	if ($u=="dash") 
	{
		echo '{';
		_dash ($o, $p);
		echo '}';
		return 200;
	}
	
	if ($u=="msg") return _message_in ($o, $p);

	if ($u=="mailer") return _mailer ($o, $p);

	if ($u=="kyc_verify") return _kyc_verify ($o, $p);
		
	if ($u=="kyc_otp") return _kyc_otp ($o, $p);

	if ($_SERVER["REQUEST_METHOD"]=="GET") 
	{
		$fo = []; //$_GET;
		foreach ($_GET as $k => $v) 
		{
			if (strlen($v)>0) // skip blank args
				$fo[$k]=$v; 
		}
		error_log ("GET-----------------------------------------");
		error_log (json_encode($fo));
	
		$rt = 200;
		if ($id=="-1") $o = $fo;
		
		if ($u=="calls" && isset ($_GET["dash_period"])) 
		{
			error_log ("dash ----|".json_encode ($_GET));
			$_GET["dt"] = _str2ts ("today");
			muu_ ("rpt",""); // sync stats from rpt
		}
	
		if ($u=="cases") // eval _title
		{
			$_t = _G ("_title");
			if ($_t=="my_cases") $fo["created_by_id__i"] = $p["auth_id"]; // my cases 
			if ($_t=="esca_by_me") $fo["escalated_by_id__i"] = $p["auth_id"]; // esca_by_me
			if ($_t=="esca_to_me") $fo["escalated_to_id__i"] = $p["auth_id"]; // esca_to_me
			if ($_t=="all_cases_today") { $fo["created_on__i"] = strtotime(date("d F Y")); }
		
			$case_count_yaxis = ["today"=>["hour","line"], "this_week"=>["dt","line"], "this_month"=>["dt","line"], "last_3_month"=>["mn","line"], "last_6_month"=>["mn","line"], "last_9_month"=>["mn","line"], "this_year"=>["mn","line"], "all"=>["yr","bar"] ];
			if (isset ($_GET["yaxis"]) && $_GET["yaxis"]=="dt" && isset ($_GET["dash_period"]))
			{
				error_log ("rpt? ".$_GET["yaxis"]." | ".$_GET["dash_period"]);
				$fo["yaxis"] = $case_count_yaxis[$_GET["dash_period"]][0];
				$fo["type"] = $case_count_yaxis[$_GET["dash_period"]][1];
			}
		}

		if ($u=="activities" && $suffix=="_case" && $id=="-1" && isset($o["src_msg"])) {$o["case_id"] = explode ("-", $o["src_msg"])[1];}
	}
		
	if ($_SERVER["REQUEST_METHOD"]=="POST")
	{
		if ($u=="clients" && $id==NULL) { $o["activity"]="6"; }	
		if ($u=="perpetrators" && $id==NULL) { $o["activity"]="7"; }
		if ($u=="attachments" && $id==NULL) { $o["activity"]="8"; }			

		if ($u=="reporters" && $id!=NULL) { $o["activity"]="9"; }; 
		if ($u=="clients" && $id!=NULL) { $o["activity"]="10"; }
		if ($u=="perpetrators" && $id!=NULL) { $o["activity"]="11"; }
		if ($u=="attachments" && $id!=NULL) { $o["activity"]="12"; }	
		
		if ($u=="clients" && $id!=NULL && isset ($o['is_delete'])) { $o["activity"]="13"; }
		if ($u=="perpetrators" && $id!=NULL && isset ($o['is_delete'])) { $o["activity"]="14"; }
		if ($u=="attachments" && $id!=NULL && isset ($o['is_delete'])) { $o["activity"]="15"; }
	
		if ($u=="cases") 
		{
			$o["activity"]="1"; 
			if ($id!=NULL)
			{
				$o["activity"]="2"; 
				if (isset ($o["case_category_id"])) $o["activity"]="3"; 

				if (isset ($o["escalated_to_id"]))
				{
					$dup_ = array ("cases","","dup","id","case_id",NULL, "escalated_to_id:escalated_to_id");
					$o_ = ["case_id"=>$id, "escalated_to_id"=>$o["escalated_to_id"]];
					$p_ = [];
					_dup ($dup_, $o_, $p_); 
					if ($o["escalated_to_id"]==$p_["escalated_to_id"])
					{ 
						unset ($o["escalated_to_id"]); // unset escalated_to if no change
					}
					if (isset ($o["escalated_to_id"]) && $o["escalated_to_id"]=="0")
					{
						$o["escalated_to"] = "";
						$o["escalated_to_role"] = "";
					}
				}
			}
			
			if ($id==NULL) $o['assigned_to_id'] = $p['auth_id']; 		
			if (isset ($o["escalated_to_id"])) $o['escalated_by_id'] = $p['auth_id'];
		}
		
		if ($u=="messages") // outgoing msgs
		{
			$tv = gettimeofday ();
			$p["src_vector"] = "2";
			$p["src_usr"] = $_SESSION["cc_user_exten"];
			//$p["gateway_session_id"] = $o["src_uid"];
			//$p["gateway_msg_id"] = $o["src"]."-".$tv["sec"]."-".$tv["usec"]; 
		}
					
		$o['i_']=0;
		$rt = rest_uri_post ($u, $suffix, $id, $o, $p);

		$a_ = $GLOBALS[($u."_def")];
		$k = model_k_id ($u, $suffix, $a_);
		$id = "-2";
		if (($rt==201 || $rt==202) && isset ($p[$k])) $id = $p[$k];
		error_log ("rt-->".$rt." ".$k);

		if ($rt>200 && $rt<203 && ($u=="cases" || $u=="dispositions") && isset ($o["src"]) && $o["src"]=="call") // shrink wrapup to 20 seconds on save
		{
			$s = "wrapup?action=0&usr=".$_SESSION["cc_user_exten"];
			if ($u=="disposition") $s .= "&hangup=1";
			$r = muu ("ami","sync?c=-1&");
			$o_ = json_decode ($r['data'], true);
			if (isset ($o_["channels"]) && isset ($o["src_uid"]) && isset ($o_["channels"][$o["src_uid"]]))  // get chan.uid (if rxists)
			{
				$s .= "&chan=".$o_["channels"][$o["src_uid"]][3]."&";
			}
			muu ("ami",$s);
		}
                
		if ($rt==201 && $u=="messages")
          {
			message_out ($o, $p);
		}

		if ($u=="cases" && $rt>200 && $rt<203)
		{
			if (isset ($o["escalated_to_id"]) && $o["escalated_to_id"]>0 && isset ($p["escalated_to_id"]) && $o["escalated_to_id"]==$p["escalated_to_id"])
			{
				error_log ("--> ".$o["escalated_to_id"]);
				case_notification ("escalation", $p["auth_usn"], $p["escalated_to"], $p["escalated_to_id"], $p["escalated_to_exten"], ("^#".$p["case_id"]." ".$p["case_category"]), $p["ca_id"]);
			}

			if ($rt==202 && $p["auth_id"]!=$p["case_created_by_id"])
			{
				case_notification ("update", $p["auth_usn"], $p["case_created_by"], $p["case_created_by_id"], $p["case_created_by_exten"], ("^#".$p["case_id"]." ".$p["case_category"]), $p["ca_id"]);
			}

			if ($rt==202 && $p["auth_id"]!=$p["case_assigned_to_id"]) 
			{
				case_notification ("update", $p["auth_usn"], $p["case_assigned_to"], $p["case_assigned_to_id"], $p["case_assigned_to_exten"], ("^#".$p["case_id"]." ".$p["case_category"]), $p["ca_id"]);
			}

			error_log ("SYNC ".$p["case_id"].", ".$p["dsp_id"].", ".$p["ca_id"]);
			// muu_ ("sync",""); // wakeup sync
		}
	}
	
	if ($rt==200 || $rt==201 || $rt==202)
	{
		error_log ("get-->".$rt." ".$id." | ".json_encode ($fo));
		$rt_ = $rt;
		$aa = [];
		$rt = rest_uri_get ($u, $suffix, $id, $fo, $p, $aa);
		if ($rt==200) 
		{
			error_log ("response: ".$id);
			$rt = rest_uri_response ($u, $suffix, $id, $o, $p, $aa, $rt_);
		}
	}
	
	return $rt;
}

session_set_save_handler ("ss_open", "ss_close", "ss_read", "ss_write", "ss_destroy", "ss_gc");
session_name ("HELPLINE_SESSION_ID");
session_start ();

$rt = _request_ ();
if ($rt>299) rest_uri_response_error ($rt);
if ($rt==203)
{
	header ("HTTP/1.0 203 Wait");
	header ('Content-Type: application/json');
	echo '{ "action":[["notice","async"]] }'; 
}

if ($rt==302)
{
	error_log ("loading subpage from url ---------------"); 
	//header ("HTTP/1.0 200 OK");
	//header ('Content-Type: text/html');
	//$GLOBALS["config"] = true;
	//include "/var/www/html/helpline/index.php";
}

?>
