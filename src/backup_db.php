<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 0; //superadmin only

/**
 * backup_dabase.php
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Dump of database for superadmin users only
 * Created: June 15, 2006
 * By: M. Nielsen
 * Modified:
 *
 */

/*   INPUTS: $_GET['student_id'] || $_PUT['student_id']
 *
 */
 //

/**
 * Path for IPP required files.
 */

$MESSAGE = "";

define('IPP_PATH','../');

/* eGPS required files. */
require_once(IPP_PATH . 'etc/init.php');
require_once(IPP_PATH . 'include/db.php');
require_once(IPP_PATH . 'include/auth.php');
require_once(IPP_PATH . 'include/log.php');
require_once(IPP_PATH . 'include/user_functions.php');


//header('Pragma: no-cache'); //don't cache this page!
//header("Cache-Control: no-cache, must-revalidate");
//header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
Header('Pragma: public, no-cache');  //IE6 SUCKS


if(isset($_POST['LOGIN_NAME']) && isset( $_POST['PASSWORD'] )) {
    if(!validate( $_POST['LOGIN_NAME'] ,  $_POST['PASSWORD'] )) {
        $MESSAGE = $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
        require(IPP_PATH . 'src/login.php');
        exit();
    }
} else {
    if(!validate()) {
        $MESSAGE = $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
        require(IPP_PATH . 'src/login.php');
        exit();
    }
}
//************* SESSION active past here **************************

//check permission levels
$permission_level = getPermissionLevel($_SESSION['egps_username']);
if( $permission_level > $MINIMUM_AUTHORIZATION_LEVEL || $permission_level == NULL) {
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}


//************** validated past here SESSION ACTIVE WRITE PERMISSION CONFIRMED****************

//include class
require_once(IPP_PATH . 'include/MySQLDump.class.php');

//create new instance of MySQLDump
$backup = new MySQLDump();
 
//set drop table if exists
$backup->droptableifexists = true;

//connect to mysql server (host, user, pass, db)
$backup->connect($mysql_data_host,$mysql_data_username,$mysql_data_password,'ipp');

//if not connected, display error
if (!$backup->connected) { die('Error: '.$backup->mysql_error); }

//get all tables in db
$backup->list_tables();

//reset buffer
$buffer = '';

//go through all tables and dump them to buffer
foreach ($backup->tables as $table) {
    $buffer .= $backup->dump_table($table);
}

if (strstr($HTTP_USER_AGENT,"MSIE 5.5")) { // had to make it MSIE 5.5 because if 6 has no "attachment;" in it it defaults to "inline"
    $attachment = "";
} else {
    $attachment = "attachment;";
}

header("Pragma: ");
header("Cache-Control: ");

header("Content-Length: " . strlen($buffer));

//display dumped buffer
header("Content-Type: text/x-sql");
//header("Content-Disposition: attachment; filename=ipp_database_" . date("Y-m-d_H.m.s") . ".sql");
header("Content-disposition: $attachment filename=\"ipp_database_" . date("Y-m-d_H.m.s") . ".sql\"");

echo $buffer; //we can use htmlspecialchars in case that there are some html tags in database



  exit();
?>