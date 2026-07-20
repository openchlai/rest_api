<?php

// step 1: include the rest_api library
include "/var/www/html/myapp/rest_api/rest_api.php"; 

// step 2: invoke the api
rest_api 
( 
	"nginx",   			// database username -- using auth_socket here
	"",				// database password	
	"",				// database host name
	"myapp",			// database name
	"/run/mysqld/mysqld.sock",	// mysql sock file // if mysql on localhost -- not applicable if mysql is on a remote machine
	"/var/www/html/myapp/models",   // folder containing config files that extend mysql schema definations
	0,				// user_id
	"test",				// user_name
	"admin"				// user_role -- should be a valid string recognized by authorization callback
);

// step 3: test. for example: 
// 
//     curl "http://localhost/myapp/api/users/"
//
// --------------------------    

?>
