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
	"84edgvygf5uigfc5r67c",		// authorization token (session token) used to retrieve session and user details and permissions
	"localhost",			// OAuth2 authorization host/ip -- if blank the endpoint is a (php) file path
	"/myapp/auth"			// OAuth2 authorization endpoint -> on success returns OAuth 2.0 objecti
);

// step 3: test. for example: 
// 
//     curl "http://localhost/myapp/api/users/"
//
// --------------------------    

?>
