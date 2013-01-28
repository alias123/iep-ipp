<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 50; //Teaching Staff

/**
 * edit_coding.php -- change student code.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: March 11, 2006
 * By: M. Nielsen
 * Modified: February 17, 2007 M. Nielsen
 *
 */

/*   INPUTS: $_GET['uid'],$_POST['uid']
 *
 */

/**
 * Path for IPP required files.
 */

$MESSAGE = "";

//$IPP_CODINGS = array("No Code", "Code 40","Code 50","Code 80", "ESL");   //no code is special case

define('IPP_PATH','../');

/* eGPS required files. */
require_once(IPP_PATH . 'etc/init.php');
require_once(IPP_PATH . 'include/db.php');
require_once(IPP_PATH . 'include/auth.php');
require_once(IPP_PATH . 'include/log.php');
require_once(IPP_PATH . 'include/user_functions.php');
require_once(IPP_PATH . 'include/navbar.php');

header('Pragma: no-cache'); //don't cache this page!

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

$uid="";
if(isset($_GET['uid'])) $uid= addslashes($_GET['uid']);
if(isset($_POST['uid'])) $uid = addslashes($_POST['uid']);

//check this students existing coding...
$code_row="";
$code_query = "SELECT * FROM coding WHERE uid=$uid";
$code_result = mysql_query ($code_query);
if(!$code_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$code_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
   $code_row= mysql_fetch_array($code_result);
}


$student_id=$code_row['student_id'];
if($student_id=="") {
   //we shouldn't be here without a username.
   echo "Cannot get student id from coding id. Fatal, quitting";
   exit();
}

//check permission levels
$permission_level = getPermissionLevel($_SESSION['egps_username']);
if( $permission_level > $MINIMUM_AUTHORIZATION_LEVEL || $permission_level == NULL) {
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}

$our_permission = getStudentPermission($student_id);
if($our_permission == "WRITE" || $our_permission == "ASSIGN" || $our_permission == "ALL") {
    //we have write permission.
    $have_write_permission = true;
}  else {
    $have_write_permission = false;
}

//************** validated past here SESSION ACTIVE WRITE PERMISSION CONFIRMED****************

//check if we are updating this coding...we already have permission if we are here.
if(isset($_POST['modify_coding']) =="1" && $have_write_permission ) {
    $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
     if(!preg_match($regexp,$_POST['start_date'])) { $MESSAGE = $MESSAGE . "Start Date must be in YYYY-MM-DD format<BR>"; }
     else {
       if(!($_POST['end_date'] == ""  || preg_match($regexp,$_POST['end_date']))) { $MESSAGE = $MESSAGE . "End Date must be in YYYY-MM-DD format<BR>"; }
       else {
           $update_query = "UPDATE coding SET code='" . AddSlashes($_POST['code']) . "',start_date='" . addslashes($_POST['start_date']) . "'";
           if($_POST['end_date'] == "") $update_query .= ",end_date=NULL";   //set no end date.
           else $update_query .= ",end_date='" . addslashes($_POST['end_date']) . "'";
           $update_query .= " WHERE uid=$uid LIMIT 1";
           $update_result = mysql_query($update_query);
           if(!$update_result) {
              $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$update_query'<BR>";
              $MESSAGE=$MESSAGE . $error_message;
              IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
           } else {
             //redirect
             //$MESSAGE = $update_query . "<BR>";
             header("Location: " . IPP_PATH . "src/coding.php?student_id=" . $student_id);
           }
       }
    }
}

//get the valid codes...
$valid_code_query="SELECT * FROM valid_coding WHERE 1";
$valid_code_result = mysql_query ($valid_code_query);
if(!$valid_code_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$valid_code_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

?> 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
    <TITLE><?php echo $page_title; ?></TITLE>
    <style type="text/css" media="screen">
        <!--
            @import "<?php echo IPP_PATH;?>layout/greenborders.css";
        -->
    </style>
    <!-- All code Copyright &copy; 2005 Grasslands Regional Division #6.
         -Concept and Design by Grasslands IPP Design Group 2005
         -Programming and Database Design by M. Nielsen, Grasslands
          Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
    <script language="javascript" src="<?php echo IPP_PATH . "include/popcalendar.js"; ?>"></script>
    <SCRIPT LANGUAGE="JavaScript">
      function deleteChecked() {
          var szGetVars = "delete_history=";
          var szConfirmMessage = "Are you sure you want to delete history:\n";
          var count = 0;
          form=document.ipphistorylist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + "ID #" + form.elements[x].name + "\n";
                     count++;
                  }
              }
          }
          if(!count) { alert("Nothing Selected"); return false; }
          if(confirm(szConfirmMessage))
              return true;
          else
              return false;
      }

      function noPermission() {
          alert("You don't have the permission level necessary"); return false;
      }
    </SCRIPT>
</HEAD>
    <BODY>
        <table class="shadow" border="0" cellspacing="0" cellpadding="0" align="center">  
        <tr>
          <td class="shadow-topLeft"></td>
            <td class="shadow-top"></td>
            <td class="shadow-topRight"></td>
        </tr>
        <tr>
            <td class="shadow-left"></td>
            <td class="shadow-center" valign="top">
                <table class="frame" width=620px align=center border="0">
                    <tr align="Center">
                    <td><center><img src="<?php echo $page_logo_path; ?>"></center></td>
                    </tr>
                    <tr><td>
                    <center><?php navbar("coding.php?student_id=$student_id"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">-Edit Student Code-</p></center></td></tr></table></center>
                        <BR>

                        <center>
                        <form name="changeCode" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/edit_coding.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="2">
                          <p class="info_text">Edit and click 'Update Coding'.</p>
                           <input type="hidden" name="modify_coding" value="1">
                           <input type="hidden" name="uid" value="<?php echo $uid; ?>">
                          </td>
                        </tr>
                        <tr>
                            <td valign="bottom" align="center" bgcolor="#E0E2F2" class="row_default">Code</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <select name="code">
                            <?php
                            while($valid_code_row = mysql_fetch_array($valid_code_result)) {
                            //foreach($IPP_CODINGS as $index => $value) {
                              if($code_row['code'] == $valid_code_row['code_number']) {
                                echo "<option selected value=\"" . $valid_code_row['code_number'] . "\">" . $valid_code_row['code_number'] . '-' . $valid_code_row['code_text'] . "</option>";
                              } else {
                                echo "<option value=\"" . $valid_code_row['code_number'] . "\">" . $valid_code_row['code_number'] . '-' . $valid_code_row['code_text'] .  "</option>";
                              }
                            }
                            ?>
                            </select>
                            </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Start Date: (YYYY-MM-DD)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="text" name="start_date" value="<?php echo $code_row['start_date']; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.start_date, 'yyyy-m-dd', 0, 0)">
                           </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">End Date: (YYYY-MM-DD)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="text" name="end_date" value="<?php echo $code_row['end_date']; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.end_date, 'yyyy-m-dd', 0, 0)">
                           </td>
                        </tr>
                        <tr>
                            <td valign="bottom" align="center" bgcolor="#E0E2F2" colspan="2"><input type="submit" value="Update Coding"></td>
                        </tr>
                        </table>
                        </form>
                        </center>

                        </div>
                        </td>
                    </tr>
                </table></center>
            </td>
            <td class="shadow-right"></td>   
        </tr>
        <tr>
            <td class="shadow-left">&nbsp;</td>
            <td class="shadow-center">
            <?php navbar("coding.php?student_id=$student_id"); ?>
            </td>
            <td class="shadow-right">&nbsp;</td>
        </tr>
        <tr>
            <td class="shadow-bottomLeft"></td>
            <td class="shadow-bottom"></td>
            <td class="shadow-bottomRight"></td>
        </tr>
        </table> 
        <center>System Copyright &copy; 2005 Grasslands Regional Division #6.</center>
    </BODY>
</HTML>
