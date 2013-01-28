<?php

/**
 * db.php -- IEP-IPP database utilities
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: May 13, 2005
 * By: M. Nielsen
 * Modified: May 25, 2005
 *
 */
    function connectUserDB() {
        //connects to the USER DB as specified in the config file.
        //inputs: none
        //returns $db_user - handle to user database or FALSE on fail.
        //if FALSE returns $error_message
        global $mysql_user_host,$mysql_user_username,$mysql_user_password,$mysql_user_database,$error_message;

        $link = mysql_connect($mysql_user_host,$mysql_user_username,$mysql_user_password);
        if($link == FALSE) {
           $error_message = "Could not connect to database: (". __FILE__ . ":" . __LINE__ . ") for the following reason: '" . mysql_error() . "'<BR>\n";
           return FALSE;
        }
        $db_user = mysql_select_db($mysql_user_database);
        if(!$db_user) {
           $error_message = "Could not select database: (". __FILE__ . ":" . __LINE__ . ")'" . $mysql_user_database . "' for the following reason: '" . mysql_error() . "'</BR>\n";
           return FALSE;
        }

        return $db_user;
    }

    function connectIPPDB() {
        //connects to the eGPS DB
        //inputs: none
        //returns $db_user - handle to user database or FALSE on fail.
        //if FALSE returns $error_message
        global $mysql_data_database,$mysql_data_host,$mysql_data_username,$mysql_data_password,$error_message;

        $link = mysql_connect($mysql_data_host,$mysql_data_username,$mysql_data_password);
        if($link == FALSE) {
           $error_message = "Could not connect to database on $mysql_data_host: (". __FILE__ . ":" . __LINE__ . ") for the following reason: '" . mysql_error() . "'<BR>\n";
           return FALSE;
        }
        $db_user = mysql_select_db($mysql_data_database);
        if(!$db_user) {
           $error_message = "Could not select database: (". __FILE__ . ":" . __LINE__ . ")'" . $mysql_user_database . "' for the following reason: '" . mysql_error() . "'</BR>\n";
           return FALSE;
        }

        return $db_user;
    }


?>
