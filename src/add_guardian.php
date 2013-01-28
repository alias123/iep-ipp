<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100;    //everybody (do checks within document)

/**
 * add_guardian.php -- add student guardian
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: July 07, 2005
 * By: M. Nielsen
 * Modified: February 17,2007.
 *
 */

/**
 * Path for IPP required files.
 */

if(isset($MESSAGE)) $MESSAGE = $MESSAGE; else $MESSAGE="";

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

//check permission levels
$permission_level = getPermissionLevel($_SESSION['egps_username']);
if( $permission_level > $MINIMUM_AUTHORIZATION_LEVEL || $permission_level == NULL) {
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}

if(!isset($_GET['student_id'])) {
    //ack
    echo "You've come to this page without a valid student ID<BR>To what end I wonder...<BR>";
    exit();
} else {
    $student_id=$_GET['student_id'];
}

$student_query = "select * from student where student.student_id=" . $_GET['student_id'];
$student_result = mysql_query($student_query);
if(!$student_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

$student_row=mysql_fetch_array($student_result);

//get our permissions for this student...
$current_student_permission = getStudentPermission($student_row['student_id']);

//check if we need to update the guardian list and have the required permissions to do so...
if(!($current_student_permission == "ALL" || $current_student_permission == "ASSIGN" || $current_student_permission == "WRITE")) {
    //yeah, we don't have permission to be here throw a security fail...
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}

function parse_submission() {
    if(!$_GET['first_name']) return "You must supply a first name<BR>";
    if(!$_GET['last_name']) return "You must supply a last name<BR>";

    return NULL;
}

//ok, are we adding now??
if(isset($_GET['add_guardian'])) {
  //parse??
  $retval = parse_submission();
  if($retval != NULL) {
      $MESSAGE = $MESSAGE . $retval;
  } else {
      $guardian_query="INSERT INTO guardian (first_name,last_name) VALUES ('" . addslashes($_GET['first_name']) . "','" . addslashes($_GET['last_name']) . "')";
      $guardian_result=mysql_query($guardian_query);
       if(!$guardian_result) {
           $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$guardian_query'<BR>";
           $MESSAGE=$MESSAGE . $error_message;
           IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
       } else {
         //attach to student ID and redirect...
            $guardian_id = mysql_insert_id();
            $guardians_query="INSERT INTO guardians (student_id,guardian_id,from_date,to_date) VALUES (" . addslashes($_GET['student_id']) . ",$guardian_id,NOW(),null)";
            $guardians_result=mysql_query($guardians_query);
            if(!$guardians_result) {
                 $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$guardians_query'<BR>";
                 $MESSAGE=$MESSAGE . $error_message;
                 IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
            } else {
               //redirect to student page....
               header("Location: ./guardian_view.php?student_id=" . addslashes($_GET['student_id']));
               exit();
            }
         }
  }
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
         -Concept and Design by Grasslands IPP Focus Group 2005
         -Programming and Database Design by M. Nielsen, Grasslands
          Regional Division #6
         -User Interface Design and Educational Factors by P Stoddart,
          Grasslands Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
     <SCRIPT LANGUAGE="JavaScript">
      function notYetImplemented() {
          alert("Functionality not yet implemented"); return false;
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
                    <center><?php navbar("guardian_view.php?student_id=$student_id"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center>
                        <table width="80%" cellspacing="0" cellpadding="0"><tr><td><center><p class="header">- IPP Add Guardian-</p></center></td></tr><tr><td><center><p class="bold_text"> <?php echo $student_row['first_name'] . " " . $student_row['last_name'] .  ", Permission: " . $current_student_permission;?></p></center></td></tr></table>
                        </center>
                        <BR>
                        <center>
                        <form name="addGuardian" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/add_guardian.php"; ?>" method="get">
                        <table border="0" cellpadding="0" cellspacing="0" width="80%">
                        <tr>
                          <td colspan="2">
                          <p class="info_text">Fill out and click 'Add Guardian'.</p>
                          <input type="hidden" name="add_guardian" value="1">
                          <input type="hidden" name="student_id" value="<?php echo $student_row['student_id']; ?>">
                          </td>
                        </tr>

                        <tr>
                          <td bgcolor="#E0E2F2" align="left">First Name:</td>
                          <td bgcolor="#E0E2F2">
                            <input type="text" tabindex="1" name="first_name" size="30" maxsize="125" value="<?php if(isset($user_row['first_name'])) echo $user_row['first_name']; else if(isset($_POST['first_name'])) echo $_POST['first_name'];?>">
                          </td>
                        </tr>
                        <tr>
                          <td bgcolor="#E0E2F2" align="left">Last Name:</td>
                          <td bgcolor="#E0E2F2">
                            <input type="text" name="last_name" tabindex="2" size="30" maxsize="125" value="<?php if(isset($user_row['last_name'])) echo $user_row['last_name']; else if(isset($_POST['last_name'])) echo $_POST['last_name']; ?>">
                          </td>
                        </tr>
                        <tr>
                            <td valign="bottom" align="center" bgcolor="#E0E2F2" colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <td valign="bottom" align="center" bgcolor="#E0E2F2" colspan="2">&nbsp;&nbsp;<input tabindex="3" type="submit" value="Add Guardian"></td>
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
            &nbsp;
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
