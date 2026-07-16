<?php

// step 1: include the rest_api library
include "/var/www/html/myapp/rest_api/rest.php"; 

// step 2: include defination file(s) for your mysql table(s)
include "users.php";
// include others here

// step 3: invoke the api 
$db_username = "nginx";
$db_password = "";
$db_host = "";
$db_name = "myapp";
$db_sock = "/run/mysqld/mysqld.sock";

rest_uri ($db_username, $db_password, $db_host, $db_name, $db_sock);

?>
