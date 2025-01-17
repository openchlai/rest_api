<?php
include "../config.php"; //

$db = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");
$db2 = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");

include "model.php";
include "model_k.php";
include "model_qa.php";
include "model_qa_k.php";
include "../lib/rest.php";
include "../lib/session.php";
include "../lib/XLSXbuf.php"; 
include "../lib/rpc.php"; 
//include "../lib/dialplan.php";

/**
 * Copy a file from the PABX's recording archive to the client
 * @param string $uid The id of the file to copy
 */
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

/**
 * Send a request to the uhttpd server (running on 127.0.0.1:8383)
 * @param string $cmd The command to send to the uhttpd server
 * @param string $args The arguments to send with the command
 * @return array The response from the uhttpd server
 */
function muu ($cmd, $args)
{
	$url = "http://127.0.0.1:8383/".$cmd."/".$args;
        $r = array ('data'=>'', 'info'=>0);
        $ch = curl_init ();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
//      curl_setopt ($ch, CURLOPT_VERBOSE, 2);
        $r['data'] = curl_exec ($ch);
        $r['info'] = curl_getinfo ($ch);
        curl_close ($ch);
        error_log ("[muu] ". $cmd."/".$args." | ".$r['info']['http_code']);
        return $r;
}

/**
 * Notify users about a new activity
 * @param string $activity The activity to notify about (e.g. 'new case')
 * @param int $assigned_to_id The id of the user assigned to this activity
 * @param array $o The activity data
 * @param array $p The http request parameters
 */
function notify ($activity, $assigned_to_id, &$o, &$p)
{
	$p_ = $p;
	$ts = gettimeofday (true);
	$p_["action"] = "notif";
	$p_["activity"] = $activity;
	$p_["assigned_to_id"] = $assigned_to_id;
	// if timestamp is missing, add it
	if (!isset ($o["src_ts"]) || strlen ($o["src_ts"])<1) {
		$p_["src_ts"] = $ts;
	}
	// if source is missing, add it
	if (!isset ($o["src"]) || strlen ($o["src"])<1) {
		$p["src"] = $p_["activity_"];
	}
	// if source uid is missing, add it
	if (!isset ($o["src_uid"]) || strlen ($o["src_uid"])<1) {
		$p_["src_uid"] = (_val_id() + _rands(4,"num"));
	}
	// log this for debugging
	error_log ("[notify](".$activity.") o:".json_encode ($o));
	error_log ("[notify](".$activity.") p:".json_encode ($p_));
	// post the notification to the activities endpoint
	rest_uri_post ("activities", "", NULL, $o, $p_);
	// notify the client ui to update the notification list
	// ati -> notify client ui to update notif list
}

/**
 * Notify function to handle message notifications.
 *
 * @param string $verb Action verb for the notification.
 * @param string $src Source of the message.
 * @param string $src_uid Unique identifier for the source.
 * @param string $src_address Address of the source.
 * @param string $src_usr User associated with the source.
 * @param string $src_msg Message content.
 * @param string $src_vector Vector information for the source.
 * @param int $m Mode flag.
 *
 * @return int Status code of the notification process.
 */
function _notify_ ($verb, $src, $src_uid, $src_address, $src_usr, $src_msg, $src_vector, $m=1)
{
	$o = [];
	$p = [];
	
	// Generate unique identifier if not provided
	if (strlen($src_uid) < 1) {
		$src_uid = _val_id() . "-" . _rands(9, "num");
	}
	
	// Set source details in the object
	$o["src_ts"] = _val_id();
	$o["src"] = $src;
	$o["src_uid"] = $src_uid;
	$o["src_address"] = $src_address;
	$o["src_usr"] = $src_usr;
	$o["src_msg"] = $src_msg; 
	$o["src_vector"] = $src_vector;
	
	// Set gateway details
	$p["gateway_msg_id"] = _val_id() . "-" . _rands(9, "num");
	$p["gateway_session_id"] = $o["src_uid"];
	$o["i_"] = 0;
	
	// Simulate a POST request to 'messages' endpoint
	$rt = -1; // Placeholder for actual rest_uri_post call
	
	// If successful, process the message
	if ($rt == 201) {
		$msg = $o["src_msg"];
		
		// Clean and truncate message
		$msg = preg_replace('/[[:^print:]]/', ' ', $msg); 
		$msg = str_replace([' ', '&', '<', '>', "\r", "\n", "\t"], ['_', '', '', '', '', '', ''], $msg); 
		if (strlen($msg) > 60) {
			$msg = substr($msg, 0, 60) . "...";
		}
		
		// Create notification string and post to notification queue
		$s = $verb . "src=" . $o['src'] . "&address=" . $o['src_address'] . "&id=" . $o['src_uid'] . "&msg=" . $msg . "&agent=" . $o["src_usr"];
		muu("ati", $s);
		
		if ($m == 0) {
			return $rt;
		}
		
		$aa = [];
		$fo = [];
		
		// Simulate a GET request to 'messages' endpoint
		$rt = rest_uri_get("messages", "", $p["msg_id"], $fo, $p, $aa);
		if ($rt == 200) {
			return rest_uri_response("messages", "", $p["msg_id"], $o, $p, $aa, 201);
		}
	}
	
	// Log the message result
	error_log("msg rt=" . $rt . " | " . json_encode($p));
	return $rt;
}

/**
 * Send a message from the client to the Helpline API
 * @param object $o The data object
 * @param object $p The parameter object
 * @return void
 */
function message_out (&$o, &$p)
{
	// Get authorization token
	$hdrs = array ("Content-Type: application/json");
	$postdata = array
	(
		"username"=>$GLOBALS["API_GATEWAY_USN"],
		"password"=>$GLOBALS["API_GATEWAY_PASS"]  
	);
	$token = kurl ($GLOBALS["API_GATEWAY_AUTH"], 60, json_encode($postdata), $hdrs);
	
	// Set API endpoint URL
	$api_url = $GLOBALS["API_GATEWAY_SEND_MSG"];
	
	// Set request headers
	$hdrs = array ("Content-Type: application/json", ("Authorization: Token ".json_decode($token["data"],true)["token"]));
	
	// Set data to be sent
	$postdata = array 
	(
		"chat_sender"=>$o['src_address'], 
		"chat_receiver"=>$p['src_usr'], 
		"chat_message"=>$o['src_msg'], 
		"chat_session"=>$o['src_callid'], 
		"chat_channel"=> $o["src"],
		"chat_source"=>"OUTBOX"//"HELPLINE"
	);
	
	// If the message is to be closed, set API endpoint URL and data to be sent
	if (isset ($o["close"]))
	{
		$api_url = $GLOBALS["API_GATEWAY_CLOSE_MSG"].$o["src_callid"]."/close/";
		$postdata = array ("chat_source"=>"HELPLINE");
	}
	
	// Log the data to be sent
	error_log ("[postdata] ".json_encode($postdata));
	
	// Send the request
	kurl ($api_url, 60, json_encode($postdata), $hdrs);
	
	// Update notification status
	$s = "read?id=".$o["src_uid"]."&";
	if (isset ($o["close"])) $s .= "action=close&"; // close session
        muu ("ati", $s); // update notification status
}

/*************  ✨ Codeium Command 🌟  *************/

/**
 * Handle incoming messages from the API Gateway
 * @param object $o The data object
 * @param object $p The parameter object
 * @return void
 */
function _message_in (&$o, &$p)
{
	// Log the message
	error_log ("   message_in -----". json_encode ($o));

	// Set properties of the message
	$o_ = [];
	$o_['i_']=0;
	$o_["src"] = $o["channel"];
	$o_["src_uid"] = $o["message_id"]; 
	$o_["src_callid"] = $o["session_id"]; 
	$o_["src_address"] = $o["from"];
	$o_["src_msg"] = $o["message"];
	$o_["src_ts"] = $o["timestamp"];
	$o_["src_vector"] = "1";
        if ($o_['src']	=='safepal') $o_['src_mime'] = "application/json";

	// Post the message to the messages endpoint
	$rt = rest_uri_post ("messages", "", NULL, $o_, $p);
	if ($rt==201) 
	{
		// Get the message text
		$msg = $o_["src_msg"];

		// Replace non-printable characters with spaces
		$msg = preg_replace ('/[[:^print:]]/', ' ', $msg); 

		// Replace special characters with underscore
		$msg = str_replace ([' ', '&', '<', '>', "\r","\n","\t"], ['_', '', '', '', '', '', ''], $msg); 

		// Truncate the message if it's too long
		if (strlen ($msg)>30) $msg = substr ($msg,0,30)."..."; // trunccate to fit in notif 

		// Post the message to the notif_queue
		// $s = "msg?src=".$o_['src']."&address=".$o_['src_address']."&id=".$o_['src_uid']."&msg=".$msg."&";
		$s = "msg?src=".$o_['src']."&address=".$o_['src_address']."&id=".$o_['src_callid']."&msg=".$msg."&";
		muu ("ati", $s); // post to notif_queue

		// Get the message
		$aa = [];
		$fo = [];
		$rt = rest_uri_get ("messages", "", $p["msg_id"], $fo, $p, $aa);
		if ($rt==200)
		{
			return rest_uri_response ("messages", "", $p["msg_id"], $o, $p, $aa, 201);
		}
	}
	error_log (" msg rt=".$rt." | ". json_encode ($p));
	return $rt;
}
/******  e5a4a3aa-557d-4825-b8e6-424062f5c02c  *******/

/*************  ✨ Codeium Command 🌟  *************/
/**
 * Handle supervisor requests.
 * 
 * @param object $o The data object
 * @return void
 */
function _sup (&$o) // "action=", "exten="
{
	$usn = $_SESSION["cc_user_usn"];
	$exten = $_SESSION["cc_user_exten"];
	$role = $_SESSION["cc_user_role"];

	// Log the supervisor request
	error_log ("[sup] request ".json_encode ($o)." | ".$usn. " ". $exten);
	
	// Check if the user is a supervisor
	if ($role!=2 && $role!=99) return 404;
	
	// Get the AMI state
	$r = muu ("ami","sync?c=-1&");
	$o_ = json_decode ($r['data'], true);
	$k = array_keys ($o_['channels']);
	$n = count ($k);
	$me = null;
	$agent = null;
	for ($i=0; $i<$n; $i++) 
	{
		$ch = $o_['channels'][$k[$i]];
		// Skip if the channel is not a supervisor
		if (strlen ($ch[16])>0) continue;
		// Find the supervisor's channel
		if ($me===null && $ch[4]==$exten && $ch[5]=="Supervisor") 
		if ($me===null && $ch[4]==$exten && $ch[5]=="Supervisor") //substr($ch[43],0,10)=="supervisor") 
		{ 
			$me=$k[$i]; 
			continue; 
		}
		// Find the agent's channel
		if ($agent===null && isset ($o["exten"]) && $ch[0]==$o["exten"]) 
		if ($agent===null && isset ($o["exten"]) && $ch[0]==$o["exten"]) // find agent uid
		{
			$agent=$k[$i];
			continue;
		}
	}
	// Construct the AMI request
	$s = "sup?action=".$o['action']."&usr=".$usn."&exten=".$exten;
	if (isset ($o["exten"])) $s .= "&agent=".$o['exten'];
	if ($me!=null) $s .= "&chan=".$o_['channels'][$me][3];
	if ($agent!=null) $s .= "&uid=".$agent;
	muu ("ami",$s); 
	// Return the response
	header ("HTTP/1.0 203 Wait");
	header ('Content-Type: application/json');
	$ts = time ();
	echo '{ "action":[["'.$ts.'","'.$o['action'].'"]] }'; 
	return 201;
}

/**
 * Handle ChannelBridge (CB) requests.
 * 
 * @param object $o The data object
 * @return void
 */
function _chan (&$o)
{
	$usn = $_SESSION["cc_user_usn"];
	$exten = $_SESSION["cc_user_exten"];
	
	error_log ("[chan] request ".json_encode ($o)." | ".$usn. " ". $exten);
	
	if ($o['action']>2 && $o['action']<6) // cb_xfer, cb_conference, cb_resume
	{
		// Redirect to the Asterisk AMI
		$s = "redirect?action=".$o['action']."&exten=".$o['exten']."&chan1=".$o['chan']."&chan3=".$o['chan3']."&";
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
			// Get extension from the user table
			$row = qryp ("SELECT exten FROM auth WHERE id=?", "s", [$o['user_id']], 1);
			if ($row)  $o['add'] = $row[0];
		}
		
		// Validate extension
		if (strlen ($o['add'])!=3 || !is_numeric ($o['add'])) 
		{
			header ("HTTP/1.0 412 Wait");
			header ('Content-Type: application/json');
			echo '{ "errors":[["error","Invalid Extension"]] }'; 
			return 413;
		}
	
		// Set extension for the channel bridge
		$o['exten'] = 'CB'.$o['src_uid'];
		if (isset ($o['cbid']) && strlen ($o['cbid'])>0) { $o['exten'] = $o['cbid']; $o['chan2'] = ''; } // dont redirect peer already in bridge
		if (!isset ($o['chan2'])) $o['chan2'] = '';
		// Redirect to the Asterisk AMI
		$s = "redirect?action=".$o['action']."&usr=".$o['usr']."&exten=".$o['exten']."&chan1=".$o['chan']."&chan2=".$o['chan2']."&add=".$o['add']."&ref=".$o['src_address']."&";
		muu ("ami", $s); 
	}
	
	if ($o['action']==0)
	{
		// Hangup the channel
		$s = "hangup?chan=".$o['chan3']."&";
		muu ("ami", $s); 
	}

	// Return a JSON response
	$ts = time ();
	$add = '';
	if (isset ($o['add'])) $add = $o['add'];
	header ("HTTP/1.0 203 Wait");
	header ('Content-Type: application/json');
	echo '{ "action":[["'.$ts.'","'.$o['action'].'","'.$add.'"]] }'; 
	return 202;
}


/**
 * Handles agent requests and returns various statistics or user data.
 * 
 * @param object $o Output parameter for the response data.
 * @return int HTTP status code.
 */
function _agent (&$o)
{
	$usn = $_SESSION["cc_user_usn"];
	$exten = $_SESSION["cc_user_exten"];
	$role = $_SESSION["cc_user_role"];

	error_log ("[agent] request ".json_encode ($o)." | ".$usn. " ". $exten);

	if ($o['action']=='0') // get chan id
	{
		// Get the ATI state
		$r = muu ("ati","sync?c=-1&");
		$o_ = json_decode ($r['data'], true);
		$k = array_keys ($o_['ati']);
		$n = count ($k);
		$me = null;
		for ($i=0; $i<$n; $i++) if ($o_['ati'][$k[$i]][0]==$exten) { $me=$k[$i]; break; }
		$s = "usr?action=0&id=".$me."&";
		if ($me!=null) muu ("ati",$s); 
		if ($me==null) error_log (" * [ati agent not found ] ");

		// Get the AMI state
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
		// Get the current date and day of week
		$k = ['any','sun','mon','tue','wed','thu','fri','sat'];
		$q = "SELECT UNIX_TIMESTAMP(Date(Now())), Dayofweek(Now())";
		$row = qryp ($q, "", [], 1);
		$at = [];
		$av = [$row[0], $row[0], _S('cc_user_id')];	
		// Fetch active campaigns
		$q = "SELECT workinghour.campaign_id, campaign_campaign FROM workinghour INNER JOIN member ON workinghour.campaign_id=member.campaign_id && source='1' && dt0<=? && dt1>=? && ".$k[$row[1]]."='1' && user_id=?";  
		$res = qryp ($q, "sss", $av, 0);
		$campaigns = "";
		$outbound = 0;
		while ($row = mysqli_fetch_row ($res))
		{
			error_log ("MBR: [".$row[0]." ".$row[1]."]");
			$v = $row[0];
			if ($row[1]==2) { $v="O"; $outbound++; }
			if ($outbound>1) continue;
			$campaigns .= $v.",";
		}
		if ($campaigns=="") $campaigns = "0,";

		if ($role==6) // login to ati only
		{
			// Login to ATI only (no AMI)
			$s = "usr?action=1&usr=".$exten."&interface=2&exten=".$GLOBALS['VA_SIP_USER_PREFIX'].$exten."&queue=".$campaigns."&";
			muu ("ati",$s); 
			$campaigns = "Z,";
		}

		// Login to AMI
		$s = "usr?action=1&usr=".$exten."&interface=2&exten=".$GLOBALS['VA_SIP_USER_PREFIX'].$exten."&queue=".$campaigns."&";
		muu ("ami",$s); 

	}
	
	if ($o['action']=='4') // enable auto answer
	{
		// Enable auto answer on the agent
		$s = "usr?action=4&usr=".$exten."&";
		muu ("ami",$s); 
	}
	
	if ($o['action']=='5') // disable auto answer
	{
		// Disable auto answer on the agent
		$s = "usr?action=5&usr=".$exten."&";
		muu ("ami",$s); 
	}

	return 203;
}

/**
 * Handles wall-only requests and returns various statistics or user data.
 *
 * @param array $o Output parameter for the response data.
 * @param array $p Input parameters for the request.
 * @return int HTTP status code.
 */
function _wallonly (&$o, &$p)
{
    // Sync stats from rpt.
    muu("rpt", "");

    // Check if metrics are requested
    if (isset($_GET["metrics"]))
    {
        // Handle dash_period request
        if (isset($_GET["dash_period"]))
        {
            // Set date for today
            $_GET["dt"] = _str2ts("today");
            // muu("rpt",""); // Uncomment if needed to sync stats again
		/**
		 * Get the current date
		 */
		/**
		 * Get the number of cases today
		 */
		/**
		 * Get the number of cases closed this month
		 */
		/**
		 * Get the number of ongoing cases
		 */
                /**
		 * Get the total number of cases
		 */
		/**
		 * Get the number of calls today
		 */
		/**
		 * Get the total number of calls
		 */
        }
        header("HTTP/1.1 200");
        header('Content-Type: application/json');
        echo '{';

        // Set a default user role for session
        $_SESSION["cc_user_role"] = "2";
        $aa = [];
        $av = [];
        $fo = $_GET;

        // Retrieve call data
        $rt = rest_uri_get("calls", "", NULL, $fo, $p, $aa);
        if ($rt == 200)
        {
            rest_uri_response("calls", "", NULL, $o, $p, $aa, 0);
        }

        echo ', "stats":{ ';

        // Get current date and month timestamps
        $q = "SELECT UNIX_TIMESTAMP(Date(Now())) dt, UNIX_TIMESTAMP(CONCAT(YEAR(Now()),'-',MONTH(Now()),'-01')) mn";
        $row = qryp($q, "", $av, 1);
        $dt_now = $row[0];
        $mn_now = $row[1];

        // Query statistics for cases
        $q = "SELECT COUNT(id) FROM kase WHERE dt=?";
        $av_ = [$dt_now];
        $row = qryp($q, "s", $av_, 1);
        echo '"cases_today":"' . $row[0] . '",';

        $q = "SELECT COUNT(id) FROM kase WHERE status=2";
        $av_ = [$mn_now];
        $row = qryp($q, "", $av, 1);
        echo '"cases_closed_this_month":"' . $row[0] . '",';

        $q = "SELECT COUNT(id) FROM kase WHERE status=1";
        $row = qryp($q, "", $av, 1);
        echo '"cases_ongoing_total":"' . $row[0] . '",';

        $q = "SELECT COUNT(id) FROM kase";
        $row = qryp($q, "", $av, 1);
        echo '"cases_total":"' . $row[0] . '",';

        // Query statistics for calls
        $q = "SELECT COUNT(id) FROM chan WHERE dt=?";
        $av_ = [$dt_now];
        $row = qryp($q, "s", $av_, 1);
        echo '"calls_today":"' . $row[0] . '",';

        $q = "SELECT COUNT(id) FROM chan";
        $av_ = [$dt_now];
        $row = qryp($q, "", $av, 1);
        echo '"calls_total":"' . $row[0] . '"';

        // Uncomment the following block to include additional hangup status data
        /*
        $q = "SELECT hangup_status, has_case_id, count(hangup_status) FROM chan WHERE hangup_status=5 GROUP BY hangup_status, has_case_id";
        $res = qryp($q, "sss", $av, 0);
        while ($row = mysqli_fetch_row($res))
        {
            echo '"has_case_id_' . $row[1] . '":"' . $row[2] . '",';
        }

        $q = "SELECT hangup_status, has_case_id, count(hangup_status) FROM chan WHERE dt=? && hangup_status=5 && has_case_id='1'";
        $av_ = [$dt_now];
        $row = qryp($q, "s", $av_, 1);
        echo '"responsive_calls_today":"' . $row[2] . '"';
        */

        echo '}';

        echo '}';
        return 200;
    }

    // Check if stats are requested
    if (isset($_GET["stats"]))
    {
        // Query for activity statistics
        $q = "SELECT SUM(if(hangup_status_txt='answered',1,0)), SUM(if(hangup_status<5 && peer_hangup!='1',1,0)), SUM(if(hangup_status_txt='answered',src_status_duration,0)) FROM activity WHERE dt=? AND src_usr=? && src='call' && src_vector='2'";
        $_GET["dt"] = _str2ts("today");
        $av = [__VESC(_G("dt")), __VESC(_G("exten"))];
        $row = qryp($q, "ss", $av, 1);
        echo '{ "stats":[';
        if ($row) echo '["' . $row[0] . '","' . $row[1] . '","' . $row[2] . '"]';
        echo ']}';
        return 200;
    }

    // Default response for user data
    $q = "SELECT id, usn, exten, role FROM auth WHERE exten=?";
    $av = [__VESC(_G("exten"))];
    $row = qryp($q, "s", $av, 1);
    header("HTTP/1.0 200 OK");
    header('Content-Type: application/json');
    echo '{ "users":[["' . $row[0] . '","' . $row[1] . '"]] }';
    return 200;
}

/**
 * @brief Handle dashboard requests
 *
 * @param object $o The data object
 * @param object $p The parameters object
 * @return integer The HTTP response code
 */
function _dash (&$o, &$p)
{
	error_log ("[dash] called | ".json_encode ($o)." | ".$_SESSION["cc_user_role"]);
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
	
	error_log ("[dash] params: dash_period=".$dash_period." dash_gbv=".$dash_gbv." dash_src=".$dash_src." ts=".$dash_ts);
	echo '"dash":[["'.$dash_period.'","'.$dash_gbv.'","'.$dash_src.'","'.$dash_ts.'","'.$_gbv_[$dash_gbv].'","'.$_src_[$dash_src].'"]]'."\n";
		
	// Get the case sources
	$aa = array ("ctx"=>"", "f"=>"", "w"=>"", "s"=>"", "sort"=>"", "lim"=>"");
	$av = [];
	ctx_rights ("cases", $aa, $av, $p, $rights["cases"]);
	if (strlen ($_gbv_[$dash_gbv])>0) k_c ("kase", "gbv_related", explode (",", $_gbv_[$dash_gbv]), $aa, $av);
	if (strlen ($dash_ts)>0) 	  k_d ("kase", "created_on", explode (";", $dash_ts), $aa, $av);
	$q = "SELECT src, COUNT(id) FROM kase ".$aa["w"]." GROUP BY src";
	error_log ("[dash] query: ".$q." | ".json_encode ($av)." | ".$_SESSION["cc_user_role"]);
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

/**
 * @brief Handle home requests
 *
 * @param object $o The data object
 * @param object $p The parameters object
 * @return integer The HTTP response code
 */
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
	// Get the logged in user
	if (rest_uri_get ("auth","", $p["auth_id"], $fo, $p, $aa)==200) rest_uri_response ("auth","", $p["auth_id"], $o, $p, $aa, 0);
        	
	echo ",";
	// Get the contacts
	if (rest_uri_get ("contacts","", "0", $fo, $p, $aa)==200) rest_uri_response ("contacts","", "0", $o, $p, $aa, 0); // load contacts_k
	
	echo ",";
	// Get the categories
	if (rest_uri_get ("categories","", "-9", $fo, $p, $aa)==200) rest_uri_response ("categories","", "-9", $o, $p, $aa, 0);

       	echo ",";
	// Get the cases
	if (rest_uri_get ("cases","", "0", $fo, $p, $aa)==200) rest_uri_response ("cases","", "0", $o, $p, $aa, 0); // also load case_subs

	echo ",";
	// Get the messages
	if (rest_uri_get ("messages","", "0", $fo, $p, $aa)==200) rest_uri_response ("messages","", "0", $o, $p, $aa, 0); // load messages_k

	echo ",";
	// Get the questions/answers
	if (rest_uri_get ("qas","", "0", $fo, $p, $aa)==200) rest_uri_response ("qas","", "0", $o, $p, $aa, 0); // load qas_k

	echo ",";
	// Get the dispositions
	if (rest_uri_get ("dispositions","", "0", $fo, $p, $aa)==200) rest_uri_response ("dispositions","", "0", $o, $p, $aa, 0); // load dispositions_k
	
	echo ",";
	// Get the activities
	$fo_=["_c"=>"10"];
	if (rest_uri_get ("activities","", NULL, $fo_, $p, $aa)==200) rest_uri_response ("activities","", NULL, $o, $p, $aa, 0); // load activities

	echo ","; 
	// Get the age groups
	$fo_ = ["_c"=>"1000", "root_id"=>$GLOBALS["AGE_GROUP_ROOT_ID"] ];
	if (rest_uri_get ("categories","_age_group", NULL, $fo_, $p, $aa)==200) rest_uri_response ("categories","_age_group", NULL, $o, $p, $aa, 0);
	
	// $q = "SELECT GROUP_CONCAT(DISTINCT campaign_id) FROM workinghour WHERE dt0<=unix_timestamp(date(now())) && dt1>=unix_timestamp(date(now()))";
	// $av = [];
	// $row = qryp ($q, "", $av, 1);
	// $active_campaigns = "0";
	// if ($row && strlen ($row[0])>0) $active_campaigns=$row[0];
	// $fo_=["id"=>$active_campaigns];
	// error_log ("ACTIVE CAMPAIGNS: ".$active_campaigns);
	// echo ",";
	// // Get the active campaigns
	// if (rest_uri_get ("campaigns","", NULL, $fo, $p, $aa)==200) rest_uri_response ("campaigns","", NULL, $o, $p, $aa, 0);
	
	// $q = "SELECT GROUP_CONCAT(DISTINCT user_id) FROM shift WHERE dt0<=unix_timestamp(date(now())) && dt1>=unix_timestamp(date(now()))";
	// $av = [];
	// $row = qryp ($q, "", $av, 1);
	// $active_users = "0";
	// if ($row && strlen ($row[0])>0) $active_users=$row[0];
	// $fo=["_c"=>"1000"]; // ["id"=>$active_users];
	// error_log ("ACTIVE USERS: ".$active_users);
	// echo ",";
	// // Get the active users
	// if (rest_uri_get ("users","", NULL, $fo, $p, $aa)==200) rest_uri_response ("users","", NULL, $o, $p, $aa, 0);
	
	echo ",";
	// Get the dashboard
	_dash ($o, $p);

        echo "}";
        return 200;
}

/**
 * Main request handler
 * 
 * @return int the HTTP status code of the request
 */
function _request_ ()
{
	$u = "";
	$suffix = "";
	$id = NULL;
	$o = [];
	$p = [];
	$fo = [];

	$rt = rest_uri_parse ($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], 3, $u, $suffix, $id, $o);
	if ($rt!=0) return $rt;
	
	// Handle wallonly requests
	if ($u=="wallonly") return _wallonly ($o, $p);

	// Handle sendOTP requests
	if ($u=="sendOTP") return _sendOTP ($o,$p);

	// Handle verifyOTP requests
	if ($u=="verifyOTP") return _verifyOTP ($o,$p); // onverify creates a 'temp' session to allow password reset

	// Handle logout requests
	if (isset ($_GET["logout"]))
	{
		$o_ = ["action"=>"0"]; // remove member from queue(s)
		_agent ($o_);
	}
	
	// Authenticate the user
	$rt = auth ($o);

	if ($rt!=0) return $rt;

	// Log the auth
	error_log ("auth (".$u.$suffix.") ---------------------------------------------------------|".json_encode ($p));
	$p["auth_id"] = _S("cc_user_id");
	$p["auth_exten"] = _S("cc_user_exten");
	$p["auth_usn"] = _S("cc_user_usn");
	$p["profile_id"] = _S("cc_user_contact_id"); 
	$p["auth_is_active"] = "1";
		
	// Handle empty requests
	if (strlen($u)<1) return _home ($o,$p);

	// Handle changeAuth requests
	if ($u=="changeAuth") return changeAuth ($o, $p);
	
	// Handle resetAuth requests
	if ($u=="resetAuth") return changeAuthAdmin ($id);
	
	// Handle agent requests
	if ($u=="agent") return _agent ($o);
	
	// Handle chan requests
	if ($u=="chan") return _chan ($o);
	
	// Handle sup requests
	if ($u=="sup") return _sup ($o);
	
	// Handle dash requests
	if ($u=="dash") 
	{
		echo '{';
		_dash ($o, $p);
		echo '}';
		return 200;
	}
	
	// Handle msg requests
	if ($u=="msg") return _message_in ($o, $p);
	
	// Handle GET requests
	if ($_SERVER["REQUEST_METHOD"]=="GET") 
	{
		$fo = $_GET;
		$rt = 200;
	
		// Handle cases requests
		if ($u=="cases") // eval _title
		{
			$_t = _G ("_title");
			if ($_t=="my_cases") $fo["created_by_id__i"] = $p["auth_id"]; // my cases 
			if ($_t=="esca_by_me") $fo["escalated_by_id__i"] = $p["auth_id"]; // esca_by_me
			if ($_t=="esca_to_me") $fo["escalated_to_id__i"] = $p["auth_id"]; // esca_to_me
			if ($_t=="all_cases_today") { $fo["created_on__i"] = strtotime(date("d F Y")); }
		
			// Handle yaxis and type for cases
			$case_count_yaxis = ["today"=>["hour","line"], "this_week"=>["dt","line"], "this_month"=>["dt","line"], "last_3_month"=>["mn","line"], "last_6_month"=>["mn","line"], "last_9_month"=>["mn","line"], "this_year"=>["mn","line"], "all"=>["yr","bar"] ];
			if (isset ($_GET["yaxis"]) && $_GET["yaxis"]=="dt" && isset ($_GET["dash_period"]))
			{
				error_log ("rpt? ".$_GET["yaxis"]." | ".$_GET["dash_period"]);
				$fo["yaxis"] = $case_count_yaxis[$_GET["dash_period"]][0];
				$fo["type"] = $case_count_yaxis[$_GET["dash_period"]][1];
			}
		}

		// Handle activities requests
		if ($u=="activities" && $suffix=="_case" && $id=="-1" && isset($o["src_msg"])) {$o["case_id"] = explode ("-", $o["src_msg"])[1];}
	}
		
	// Handle POST requests
	if ($_SERVER["REQUEST_METHOD"]=="POST")
	{
		// Handle reporters requests
		if ($u=="reporters") 
		{
			if ($id!=NULL) { $o["activity"]="9"; }; 
		}
			
		// Handle clients requests
		if ($u=="clients" && $id==NULL) { $o["activity"]="6"; }	
		if ($u=="perpetrators" && $id==NULL) { $o["activity"]="7"; }
		if ($u=="attachments" && $id==NULL) { $o["activity"]="8"; }			

		// Handle clients requests
		if ($u=="clients" && $id!=NULL) { $o["activity"]="10"; }
		if ($u=="perpetrators" && $id!=NULL) { $o["activity"]="11"; }
		if ($u=="attachments" && $id!=NULL) { $o["activity"]="12"; }	
	}
	
	// Handle cases requests
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
		
	// Handle messages requests
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
	
	// todo: increase wrapup to 5minutes on case_uuid // todo convert case_uuid GETs to PUTs
	
	// Handle cases requests
	if (($u=="cases" || $u=="dispositions") && $rt>200 && $rt<203 && isset ($o["src"]) && $o["src"]=="call") // shrink wrapup to 20 seconds on save
                {
			$s = "wrapup?action=0&usr=".$_SESSION["cc_user_exten"];
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
		
		if ($rt==201 && $u=="cases" && isset ($p["escalated_to_id"]) && $p["escalated_to_id"]>0)
		{
			//_notify ("msg?", "escalation", "", $p["auth_usn"], $p["escalated_to_exten"], ($GLOBALS["CASE_ID_PREFIX"].$p["case_id"]), "1", 0);

$rt = _request_ ();
if ($rt>299) rest_uri_response_error ($rt);
if ($rt==203)
{
	header ("HTTP/1.0 203 Wait");
	header ('Content-Type: application/json');
	echo '{ "action":[["notice","async"]] }'; 
}

?>
