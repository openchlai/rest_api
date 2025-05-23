<?php
include "/var/www/html/helpline/config.php"; //

$db = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");
$db2 = mysqli_connect (null, THE_DB_USN, null, THE_DB_NAME, null, THE_DB_SOCK) or die ("Could Not connect to Database Server.");

include "model.php";
include "model_k.php";
include "../lib/rest.php";
include "../lib/session.php";
include "../lib/rpc.php";

function subargs (&$b, &$aa, &$av, &$o, &$p)
{
	$w=" WHERE ";
	$e=0;
	$n = count ($b);
	for ($i=3; $i<$n; $i+=2)
	{
		$op = "=";
		$k = $b[($i+1)];
		$v = _kv ($k, $op, $o, $p);
		if ($v===NULL) { error_log ("[sub] (".$b[0].$b[1].")  ".$k." isnull "); $e++; continue; } 
		if ($i==3 && $b[$i]=="id" && strlen ($v)<1) $v="0";
		if ($i>3) $w = " && ";
		$aa["w"] .= $w.$b[$i]." ".$op."?";
		$aa["s"] .= "s";
		$av[] = $v;
	}
	return $e;
}

function fk ($u, &$row, &$p)
{
	$a = $GLOBALS[($u."_def")];
        $an = count ($a);
	for ($j=1; $j<$an; $j++)  // collect fk
	{
		$v=$row[$j];
		if ($a[$j][3]!='2') continue;
		$p[$a[$j][0]] = $v;
		error_log ("[fk] ".$j." : ".$a[$j][0]." = ".$v);
	}
}

function jo ($u, &$row, &$s)
{
	$a = $GLOBALS[($u."_def")];
	$an = count ($a);
	for ($j=0; $j<$an; $j++)
	{
		if ($j>0) $s .= ",\r\n"; 
		$s .= '"'.$a[$j][0].'":"'.$row[$j].'"';

	}
}

function case_sync (&$s)
{
	$s = "{";
	$case_activities_k = $GLOBALS["case_activities_k"];
	$o = [];
	$p = [];
	$aa = ["w"=>"WHERE activity IN (1,2,3)", "sort"=>"ORDER BY id", "lim"=>"LIMIT 1", "s"=>"" ];
	$av = [];
	$res = _select ("case_activities", $aa, $av);
	if ($res==NULL) return -1;
	$row = mysqli_fetch_row ($res);
	$p["case_id"] = $row[$case_activities_k["case_id"]];
	error_log ("---".json_encode ($p));

	// todo get max case_activity with current case_id
	
	$cases_k = $GLOBALS["cases_k"];
	$aa = ["w"=>"WHERE id=?", "sort"=>"", "lim"=>"", "s"=>"s" ];
        $av = [$p["case_id"]];
	$res = _select ("cases", $aa, $av);
        if ($res==NULL) return -1;
	$row = mysqli_fetch_row ($res);
	$p["case_ref"] = $row[$cases_k["ref"]];
	error_log ("---".json_encode ($p));
	jo ("cases", $row, $s);
	fk ("cases", $row, $p);

	// $subs = $GLOBALS["cases_subs"]; // subs
	$subs =
[
["reporters","","o",     "id","reporter_id"],
["perpetrators","","",  "case_id_","case_id"],
["clients","","",       "case_id_","case_id"],
["attachments","","",   "case_id_","case_id"],
//["case_activities","","","case_id","case_id"],
//["dispositions","","",  "id","dsp_id"],
//["reporters","_uuid","",     "id","reporter_uuid_id"],
];
	$n = count ($subs);
	for ($i=0; $i<$n; $i++)
	{
		$aa = ["w"=>"", "sort"=>"", "lim"=>"", "s"=>"" ];
		$av = [];
		if (subargs ($subs[$i], $aa, $av, $o, $p)!=0) continue;
		$res = _select ($subs[$i][0], $aa, $av);
		if ($res==NULL) return -1;
		$s .= "\r\n".',"'.$subs[$i][0].$subs[$i][1].'":';
		$s .= $subs[$i][2]=='o' ? '{' : '[';
		$r=0;
        	while (($row = mysqli_fetch_row ($res)))
		{
			if ($r>0) $s.=',';
			$s .= $subs[$i][2]=='o' ? '' : '{';
			jo ($subs[$i][0], $row, $s);
			$s .= $subs[$i][2]=='o' ? '' : '}';
			$r++;
		}
		$s .= $subs[$i][2]=='o' ? '}' : ']';
	}

	$s.="}";

	return 0;
}

$api_url = "https://backend.bitz-itc.com/api/webhook/helpline/case/ceemis/";
$api_hdrs = array 
(
"Content-Type: application/json"
);
$s="";
case_sync ($s);
kurl ($api_url, 60, $s, $api_hdrs);
header("HTTP/1.0 200 OK");
header ('Content-Type: application/json');
echo $s;

?>
