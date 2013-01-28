<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody

/**
 * transition_plan.php
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: February 22, 2005
 * By: M. Nielsen
 * Modified: February 17,2007
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
require_once(IPP_PATH . 'include/supporting_functions.php');

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

//function asc2hex ($temp) {
//   $len = strlen($temp);
 //  for ($i=0; $i<$len; $i++) $data.=sprintf("%02x",ord(substr($temp,$i,1)));
//   return $data;
//}

//check if we are modifying a student...
if(isset($_POST['add_transition_plan']) && $have_write_permission) {
  //check that date is the correct pattern...
  $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
 if($_POST['date'] == "" || $_POST['plan'] == "") { $MESSAGE = $MESSAGE . "You must supply both a date and a plan<BR>"; }
 else {
  if(!preg_match($regexp,$_POST['date'])) {
    //no way...
    $MESSAGE = $MESSAGE . "Date must be in YYYY-MM-DD format<BR>";
  } else {
    //we add the entry.
    $insert_query = "INSERT INTO transition_plan (student_id,date,plan) VALUES (" . addslashes($student_id) . ",'" . addslashes($_POST['date']) . "','" . addslashes($_POST['plan']) . "')";
    $insert_result = mysql_query($insert_query);
     if(!$insert_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$insert_query' <BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
     } else {
        unset($_POST['plan']);
        unset($_POST['date']);
     }
   }
 }
}

//check if we are deleting some entries...
if(isset($_GET['delete_x']) && $permission_level <= $IPP_MIN_DELETE_TRANSITION_PLAN && $have_write_permission ) {
    $delete_query = "DELETE FROM transition_plan WHERE ";
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
}

//get the coordination of services for this student...
$transition_query="SELECT * FROM transition_plan WHERE student_id=$student_id ORDER BY date DESC";

$transition_result = mysql_query($transition_query);
if(!$transition_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$transition_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
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
      function confirmChecked() {
          var szGetVars = "strengthneedslist=";
          var szConfirmMessage = "Are you sure you want to modify/delete the following:\n";
          var count = 0;
          form=document.strengthneedslist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + "ID #" + form.elements[x].name + ",";
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

                        <center><table><tr><td><center><p class="header">-Transition Plan (<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN add new entry -->
                        <center>
                        <form name="add_transition_plan" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/transition_plan.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Add Plan</p>
                           <input type="hidden" name="add_transition_plan" value="1">
                           <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                          </td>
                        </tr>
                        <tr>
                            <td bgcolor="#E0E2F2" class="row_default">Plan:</td><td bgcolor="#E0E2F2" class="row_default">
                            <textarea name="plan" tabindex="1" cols="40" rows="10" wrap="soft"><?php if(isset($_POST['plan'])) echo $_POST['plan']; ?></textarea>
                            </td>
                            <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="2" class="row_default"><input type="submit" tabindex="3" name="add" value="add"></td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Date: (YYYY-MM-DD)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="text" tabindex="2" name="date" value="<?php if(isset($_POST['date'])) echo $_POST['date']; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.date, 'yyyy-m-dd', 0, 0)">&nbsp;&nbsp;(determines school year)
                           </td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add new entry -->

                        <!-- BEGIN transitions table -->
                        <form name="transtionplans" onSubmit="return confirmChecked();" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/transition_plan.php"; ?>" method="get">
                        <input type="hidden" name="student_id" value="<?php echo $student_id ?>">
                        <center><table width="80%" border="0" cellpadding="0" cellspacing="1">
                        <tr><td colspan="7">Transition Plans (click to edit):</td></tr>
                        <?php
                        $bgcolor = "#DFDFDF";

                        //print the header row...
                        echo "<tr><td bgcolor=\"#E0E2F2\">&nbsp;</td><td bgcolor=\"#E0E2F2\">uid</td><td align=\"center\" bgcolor=\"#E0E2F2\">Plan</td><td align=\"center\" bgcolor=\"#E0E2F2\">Date</td></tr>\n";
                        while ($transition_row=mysql_fetch_array($transition_result)) { //current...
                            echo "<tr>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><input type=\"checkbox\" name=\"" . $transition_row['uid'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\">" . $transition_row['uid'] . "</td>";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><a href=\"" . IPP_PATH . "src/edit_transition_plan.php?uid=" . $transition_row['uid'] . "\" class=\"editable_text\">" . checkspelling($transition_row['plan'])  ."</td>\n";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><a href=\"" . IPP_PATH . "src/edit_transition_plan.php?uid=" . $transition_row['uid'] . "\" class=\"editable_text\">" . $transition_row['date']  ."</td>\n";
                            echo "</tr>\n";
                            if($bgcolor=="#DFDFDF") $bgcolor="#CCCCCC";
                            else $bgcolor="#DFDFDF";
                        }
                        ?>
                        <tr>
                          <td colspan="7" align="left">
                             <table>
                             <tr>
                             <td nowrap>
                                <img src="<?php echo IPP_PATH . "images/table_arrow.png"; ?>">&nbsp;With Selected:
                             </td>
                             <td>
                             <?php
                                //if we have permissions also allow delete.
                                if($permission_level <= $IPP_MIN_DELETE_COORDINATION_OF_SERVICES && $have_write_permission) {
                                    echo "<INPUT NAME=\"delete\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" value=\"1\">";
                                }
                             ?>
                             </td>
                             </tr>
                             </table>
                          </td>
                        </tr>
                        </table></center>
                        </form>
                        <!-- end transitions table -->

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