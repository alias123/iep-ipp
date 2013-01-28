<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody

/**
 * supervisor_view.php -- change supervisor/view history.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: July 18, 2005
 * By: M. Nielsen
 * Modified: January 2,2006  By: M. Nielsen
 * Modified: February 17, 2007 M. Nielsen
 *
 */

/*   INPUTS: $_GET['student_id']
 *
 */

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

$student_id="";
if(isset($_GET['student_id'])) $student_id= $_GET['student_id'];
if(isset($_POST['student_id'])) $student_id = $_POST['student_id'];

if($student_id=="") {
   //we shouldn't be here without a student id.
   echo "You've entered this page without supplying a valid student id. Fatal, quitting";
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

$student_query = "SELECT * FROM student WHERE student_id = " . addslashes($student_id);
$student_result = mysql_query($student_query);
if(!$student_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {$student_row= mysql_fetch_array($student_result);}

//check if we are adding...
if(isset($_GET['add']) && $have_write_permission && $_GET['supervisor'] != "SELECT") {

   //check for duplicate...
   $check_query = "SELECT * FROM supervisor WHERE egps_username='" . addslashes($_GET['supervisor']) . "' AND end_date IS NULL AND student_id=" . addslashes($student_id);
   $check_result = mysql_query($check_query);
   if(!$check_result) {
      $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$check_query'<BR>";
      $MESSAGE=$MESSAGE . $error_message;
      IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
   } else {
       if(mysql_num_rows($check_result) > 0) {
           $check_row = mysql_fetch_array($check_result);
           $MESSAGE = $MESSAGE . "'" . $check_row['egps_username'] . "' is already a supervisor<BR>";
       } else {
           $add_query = "INSERT INTO supervisor (egps_username,student_id,position,start_date,end_date) VALUES ('" . addslashes($_GET['supervisor']) . "'," . addslashes($student_id) . ",'" . addslashes($_GET['position']) . "',NOW(),NULL)";
           $add_result = mysql_query($add_query);
           if(!$add_result) {
              $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$add_query'<BR>";
              $MESSAGE=$MESSAGE . $error_message;
              IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
           }
       }
    }
   //$MESSAGE = $MESSAGE . $add_query . "<BR>";
}

//check if we are deleting some peeps...
if(isset($_GET['delete_x']) && $permission_level <= $IPP_MIN_DELETE_SUPERVISOR_PERMISSION && $have_write_permission ) {
    $delete_query = "DELETE FROM supervisor WHERE ";
    foreach($_GET as $key => $value) {
        if(preg_match('/^(\d)*$/',$key))
        $delete_query = $delete_query . "uid=" . $key . " or ";
    }
    //strip trailing 'or' and whitespace
    $delete_query = substr($delete_query, 0, -4);
    //$MESSAGE = $MESSAGE . $delete_query . "<BR>";
    $delete_result = mysql_query($delete_query);
    if(!$delete_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$delete_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    }
    //$MESSAGE = $MESSAGE . $delete_query . "<BR>";
}

//check if we are setting some peeps no longer supervisor...
if(isset($_GET['set_not_supervisor_x']) && $have_write_permission ) {
    $modify_query = "UPDATE supervisor SET end_date=NOW() WHERE ";
    foreach($_GET as $key => $value) {
        if(preg_match('/^(\d)*$/',$key))
        $modify_query = $modify_query . "uid=" . $key . " OR ";
    }
    //strip trailing 'or' and whitespace
    $modify_query = substr($modify_query, 0, -4);
    //$MESSAGE = $MESSAGE . $modify_query . "<BR>";
    $modify_result = mysql_query($modify_query);
    if(!$modify_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$modify_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    }
}

//check this students existing supervisor...
$supervisor_query = "SELECT * FROM supervisor WHERE student_id =" . AddSlashes($student_id) . " AND end_date IS NULL ORDER BY start_date DESC";
$supervisor_result = mysql_query ($supervisor_query);
if(!$supervisor_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$supervisor_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

//get a history...
$supervisor_history_query = "SELECT * FROM supervisor WHERE student_id=" . addslashes($student_id) . " AND end_date IS NOT NULL ORDER BY end_date DESC";
$supervisor_history_result = mysql_query ($supervisor_history_query);
if(!$supervisor_history_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$supervisor_history_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

//get a list of all support members to build supervisor list...
$support_member_query = "SELECT * FROM support_list WHERE student_id=" . addslashes($student_id);
$support_member_result = mysql_query($support_member_query);
if(!$support_member_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$support_member_query'<BR>";
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
         -User Interface Design and Educational Factors by P Stoddart,
          Grasslands Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
    <SCRIPT LANGUAGE="JavaScript">
      function confirmChecked() {
          var szGetVars = "delete_supervisor=";
          var szConfirmMessage = "Are you sure you want to modify supervisors:\n";
          var count = 0;
          form=document.supervisorhistorylist;
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
                    <center><?php navbar("student_view.php?student_id=$student_id"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">-Manage Supervisors (<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN add supervisor -->
                        <center>
                        <form name="addsupervisor" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/supervisor_view.php"; ?>" method="get" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Add'.</p>
                           <input type="hidden" name="modify_supervisor" value="1">
                           <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                          </td>
                        </tr>
                        <tr>
                            <td valign="bottom" bgcolor="#E0E2F2">Supervisor*</td><td bgcolor="#E0E2F2">
                            <select name="supervisor">
                            <option>SELECT</option>
                            <?php
                            while ($support_member_row = mysql_fetch_array($support_member_result)) {
                               echo "<option>" . $support_member_row['egps_username'] . "</option>\n";
                            }
                            ?>
                            </select>
                            </td>
                            <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="2"><input type="submit" name="add" value="add"></td>
                        </tr>
                        <tr>
                           <td valign="bottom" bgcolor="#E0E2F2">Position:</td><td bgcolor="#E0E2F2"><input type="text" name="position" value="" size="30"></td>
                        </tr>
                        <tr>
                            <td valign="bottom" align="center" bgcolor="#E0E2F2" colspan="3"><p class="small_text">*Must be present in <?php echo $student_row['first_name'] . " " . $student_row['last_name'] ?>'s <a href="<?php echo IPP_PATH; ?>/src/modify_ipp_permission.php?student_id=<?php echo $student_id; ?>">support member</a> list</p></td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add supervisor -->

                        <!-- BEGIN ipp history table -->
                        <form name="supervisorhistorylist" onSubmit="return confirmChecked();" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/supervisor_view.php"; ?>" method="get">
                        <input type="hidden" name="student_id" value="<?php echo $student_id ?>">
                        <center><table width="80%" border="0">

                        <?php
                        $bgcolor = "#DFDFDF";

                        //print the header row...
                        echo "<tr><td bgcolor=\"#E0E2F2\">&nbsp;</td><td bgcolor=\"#E0E2F2\">UID</td><td align=\"center\" bgcolor=\"#E0E2F2\">Username</td><td align=\"center\" bgcolor=\"#E0E2F2\">Position</td><td align=\"center\" bgcolor=\"#E0E2F2\">Start Date</td><td align=\"center\" bgcolor=\"#E0E2F2\">End Date</td></tr>\n";
                        while ($supervisor_row=mysql_fetch_array($supervisor_result)) { //current...
                            echo "<tr>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><input type=\"checkbox\" name=\"" . $supervisor_row['uid'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['uid'] . "</td>";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['egps_username']  ."</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['position'] . "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['start_date'] . "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">-Current-</td>\n";
                            echo "</tr>\n";
                            if($bgcolor=="#DFDFDF") $bgcolor="#CCCCCC";
                            else $bgcolor="#DFDFDF";
                        }
                        while ($supervisor_row=mysql_fetch_array($supervisor_history_result)) { //previous...
                            echo "<tr>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><input type=\"checkbox\" name=\"" . $supervisor_row['uid'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['uid'] . "</td>";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['egps_username'] ."</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['position'] . "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['start_date'] . "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">" . $supervisor_row['end_date'] . "</td>\n";
                            echo "</tr>\n";
                            if($bgcolor=="#DFDFDF") $bgcolor="#CCCCCC";
                            else $bgcolor="#DFDFDF";
                        }
                        ?>
                        <tr>
                          <td colspan="6" align="left">
                             <table>
                             <tr>
                             <td nowrap>
                                <img src="<?php echo IPP_PATH . "images/table_arrow.png"; ?>">&nbsp;With Selected:
                             </td>
                             <td>
                             <?php
                                if($have_write_permission) {
                                    echo "<INPUT NAME=\"set_not_supervisor\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Not Supervisor\" border=\"0\" value=\"set_not_supervisor\">";
                                }
                                //if we have permissions also allow delete and set all.
                                if($permission_level <= $IPP_MIN_DELETE_SUPERVISOR_PERMISSION && $have_write_permission) {
                                    echo "<INPUT NAME=\"delete\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" value=\"delete\">";
                                }
                             ?>
                             </td>
                             </tr>
                             </table>
                          </td>
                        </tr>
                        </table></center>
                        </form>
                        <!-- end ipp history table -->

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
            <?php navbar("student_view.php?student_id=$student_id"); ?>
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
