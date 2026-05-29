<?php

$RESOURCES["auth"]  = ["auth","","0","0","0",   	"Auth","","",""];
$RESOURCES["users"] = ["auth","user","3","0","0",	"User","","",""];

$RIGHTS["1"]["auth"]  = ["1","0","0","1","1","id=","auth_id"];
$RIGHTS["1"]["users"] = ["1","0","0","1","1"];

$MODELS["auth"] = 
[
	array ("id","",				"0","2","","", "","","", "ID",""),
	array ("created_on","",			"0","3","","", "","","", "Created On",""),
	array ("created_by","",			"0","2","","", "","","", "Created By",""),
	array ("created_by_id","",		"0","2","","", "","","", "Created By ID",""),
	array ("created_by_role","",		"0","2","","", "","","", "Created By Role",""),

	array ("usn","",				"3","2","u","", "","","", "Username",""),
	array ("role","",				"3","2","m","", "","","", "Role",""),
	array ("is_active","",             "3","2","v","", "","1","", "Active",""),

	array ("contact_id","",			"1","2","","f", "","","", "Contact ID",""),
	array ("contact_fullname","",		"3","2","","",  "","","", "Fullname",""),
	array ("contact_fname","",		"3","2","m","", "","","", "First Name",""),
	array ("contact_lname","",		"3","2","","",  "","","", "Last Name",""),
	array ("contact_phone","",		"3","2","","p", "","","", "Phone",""),
	array ("contact_email","",		"3","2","m","",  "","","", "Email",""),
	array ("contact_location","",		"3","2","","",  "","","", "Location",""),
];
$MODELS["users"] = $MODELS["auth"];

/*
CREATE TABLE `auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_on` int(11) DEFAULT NULL,
  `created_by` char(255) DEFAULT NULL,
  `created_by_id` int(11) DEFAULT NULL,
  `created_by_role` char(2) DEFAULT NULL,
  `session_id` char(64) DEFAULT NULL,
  `session_ip` char(32) DEFAULT NULL,
  `usn` char(255) DEFAULT NULL,
  `pass` char(255) DEFAULT NULL,
  `role` char(255) DEFAULT NULL,
  `exten` char(255) DEFAULT NULL,
  `is_active` char(1) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `contact_fullname` char(255) DEFAULT NULL,
  `contact_fname` char(255) DEFAULT NULL,
  `contact_lname` char(255) DEFAULT NULL,
  `contact_phone` char(32) DEFAULT NULL,
  `contact_email` char(255) DEFAULT NULL,
  `contact_location` char(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exten_` (`exten`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/

$APIS["users"] =
[
	array ("users","","dup","id","user_id",NULL,"contact_id:contact_id"), // get contact_id 
	array ("users","","dup","usn","usn","id",":!=:user_id: 0",NULL,"usn:usn"), // check duplicate usn
	array ("users","","params", "contact_fname","fname", "contact_phone","phone", "contact_email","email"), // used by try // avoid duplicating contact
	array ("users","","try"), // test if will succeed b4 adding contact
	array ("contacts","","include"), 
	array ("contacts","_dup","include"),        
	array ("users","",""),
	// array ("extens","","dup","user_id","user_id",NULL,"id"), // get exten_id (if exists) 
	// array ("extens","",""),
	// array ("users","extens","agg4", "id","user_id",NULL,  "user_id","user_id"), // update user.exten	
];

$APIS["users_deactivate"] = 
[
        array ("users","","params", "is_active"," 0"),
        array ("users","",""),
];

?>
