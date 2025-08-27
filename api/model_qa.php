<?php

$RK = array 
(
	"qas"=>			array ("qa","",			"1","0","0",	"Quality Assessment",	"","",			""), 
);

$qas_def = array
(
	array ("id","",				"0","2","","", "","","",	"ID",""),
	array ("created_on","",			"0","3","","", "","","",	"Created On",""),
	array ("created_by","",			"0","2","","", "","","",	"Supervisor",""),
	array ("created_by_id","",		"0","2","","", "","","",	"Supervisor ID",""),
	array ("created_by_role","",		"0","2","","", "","","", 	"Supervisor Role",""),
	
	array ("chan_uniqueid","",		"1","2","m","f","","","",	"Call ID",""),
	array ("chan_chan_ts","",		"1","4","","",	"","","",	"Call Date time",""),
	array ("chan_vector","",		"1","4","","",	"","","",	"Direction",""),
	array ("chan_phone","",			"1","2","","",	"","","",	"Phone",""),
	array ("chan_usr","",			"1","2","","",	"","","",	"Extension",""),
	array ("chan_wait_time","",		"1","4","","",	"","","",	"Wait Time",""),
	array ("chan_talk_time","",		"1","4","","",	"","","",	"Talk Time",""),
	array ("chan_hold_time","",		"1","4","","",	"","","",	"Hold Time",""),
	array ("chan_hangup_reason","",		"1","2","","",	"","","",	"Hangup Reason",""),
	array ("chan_hangup_status","",		"1","2","","",	"","","",	"Hangup Status",""),
	array ("chan_user_id","",		"1","4","","", "","","", 	"User ID",""),
	array ("chan_user_name","",		"1","2","","", "","","", 	"Username",""),
	array ("chan_user_role","",		"1","2","","", "","","", 	"User Role",""),
	
	array ("opening_phrase","",		"1","4","m","", "","","", "Opening",""),
	array ("non_interrupting","",		"1","4","m","", "","","", "Non Interruption",""),
	array ("empathy","",			"1","4","m","", "","","", "Empathy",""),
	array ("paraphrasing","",		"1","4","m","", "","","", "Paraphrasing",""),
	array ("courteous","",			"1","4","m","", "","","", "Courteous",""),
	array ("nonhesitant","",		"1","4","m","", "","","", "Non Hesitant",""),
	array ("extra_mile_willingness","",	"1","4","m","", "","","", "Extra Mile Willingness",""),
	array ("confirms_client_satisfaction","",	"1","4","m","", "","","", "Checks Clients Satisfaction",""),
	array ("follows_up_on_case_updates","",		"1","4","m","", "","","", "Follows up",""),
	array ("accuracy","",			"1","4","m","", "","","", "Accuracy",""),
	array ("grammar","",			"1","4","m","", "","","", "Gramma",""),
	array ("consults","",			"1","4","m","", "","","", "Consults",""),
	array ("procedure_adherance","",	"1","4","m","", "","","", "Procedure Adherance",""),
	array ("educative","",			"1","4","m","", "","","", "Educative",""),
	array ("notifies_hold","",		"1","4","m","", "","","", "Notifies Hold",""),
	array ("updates_hold","",		"1","4","m","", "","","", "Updates Hold",""),
	array ("call_closing_coutesy","",	"1","4","m","", "","","", "Call Closing",""),
		
	array ("opening_phrase_comments","",	"1","4","","", "","","", "Call Opening Comments",""),
	array ("listening_comments","",		"1","4","","", "","","", "Listening Comments",""),
	array ("pro_active_comments","",	"1","4","","", "","","", "Proactive Comments",""),
	array ("resolution_comments","",	"1","4","","", "","","", "Resolution Comments",""),
	array ("hold_comments","",		"1","4","","", "","","", "Hold Comments",""),
	array ("call_closing_comments","",	"1","4","","", "","","", "Call Closing Comments",""),
	array ("feedback","",			"1","4","m","", "","","", "Feedback",""),

	array ("greeting_score","",		"4","4","","", "qas","opening_phrase","", "Greeting Score",""),
	array ("listening_score","",		"4","4","","", "qas","non_interrupting+empathy+paraphrasing+courteous+nonhesitant","", "Listening Score",""),
	array ("proactive_score","",		"4","4","","", "qas","extra_mile_willingness+confirms_client_satisfaction+follows_up_on_case_updates","", "Proactive Score",""),
	array ("resolution_score","",		"4","4","","", "qas","accuracy+grammar+consults+procedure_adherance+educative","", "Resolution Score",""),
	array ("holding_score","",		"4","4","","", "qas","notifies_hold+updates_hold","", "Holding Score",""),
	array ("closing_score","",		"4","4","","", "qas","call_closing_coutesy","", "Closing Score",""),
	array ("total_score","",		"5","4","","", "qas","greeting_score+listening_score+proactive_score+resolution_score+holding_score+closing_score","", "Total Score",""),
	
	array ("greeting_score_p","",		"5","4","","", "qas","ROUND((opening_phrase*100/2),0)","", "Greeting Score",""),
	array ("listening_score_p","",		"5","4","","", "qas","ROUND(((non_interrupting+empathy+paraphrasing+courteous+nonhesitant)*100/10),0)","", "Listening Score %",""),
	array ("proactive_score_p","",		"5","4","","", "qas","ROUND(((extra_mile_willingness+confirms_client_satisfaction+follows_up_on_case_updates)*100/6),0)","", "Proactive Score %",""),
	array ("resolution_score_p","",		"5","4","","", "qas","ROUND(((accuracy+grammar+consults+procedure_adherance+educative)*100/10),0)","", "Resolution Score %",""),
	array ("holding_score_p","",		"5","4","","", "qas","ROUND(((notifies_hold+updates_hold)*100/4),0)","", "Holding Score %",""),
	array ("closing_score_p","",		"5","4","","", "qas","ROUND(((call_closing_coutesy)*100/2),0)","", "Closing Score %",""),
	array ("total_score_p","",		"5","4","","", "qas","ROUND(((greeting_score+listening_score+proactive_score+resolution_score+holding_score+closing_score)*100/34) )","", "Total Score %",""),
	
	array ("yr","",				"4","2","","",   "qas","UNIX_TIMESTAMP(CONCAT(YEAR(FROM_UNIXTIME(chan_chan_ts)),'-01-01'))","",   "Year",":d:y:0: "), 
        array ("mn","",				"4","2","","",   "qas","UNIX_TIMESTAMP(CONCAT(YEAR(FROM_UNIXTIME(chan_chan_ts)),'-',MONTH(FROM_UNIXTIME(chan_chan_ts)),'-01'))","",   "Month",":d:my:0: "), 
        array ("wk","",				"4","2","","",   "qas","UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(chan_chan_ts))) - (DAYOFWEEK(FROM_UNIXTIME(chan_chan_ts))*86400)","",   "Week",":d:dmy:0: "), 
        array ("dt","",				"4","2","","",   "qas","UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(chan_chan_ts)))","",   "Date",":d:dmy:0: "), 
	array ("hr","",				"4","2","","",   "qas","UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(chan_chan_ts))) + (HOUR(FROM_UNIXTIME(chan_chan_ts))*3600)","",   "Hour",":d:dmyh:0: "), 
        array ("h","",				"4","2","","",   "qas","HOUR(FROM_UNIXTIME(chan_chan_ts))","",   "Hour",""), 
);
	

$qas_api = array 
(
	array ("calls","","dup", "uniqueid","chan_uniqueid",NULL, "uniqueid", "chan_ts", "usr", "phone", "ring_time", "wait_time", "talk_time", "hold_time", "hangup_status", "vector", "user_id", "user_name"),
	array ("qas","",""),
	array ("qas","qas","agg4",  "id","qa_id",NULL,  "id","qa_id"), 
	array ("qas","qas","agg5",  "id","qa_id",NULL,  "id","qa_id"), 
	array ("calls","qas","agg4",  "uniqueid","chan_uniqueid",NULL,  "id","qa_id") // update qa_done flag & qa_id
);

$qas_subs = 
[
["calls","","",		"uniqueid","chan_uniqueid"],
];

?>
