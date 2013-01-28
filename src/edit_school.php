<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 0; //only super administrator

/**
 * school_info.php -- add school.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: March 26, 2005
 * By: M. Nielsen
 * Modified:
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


//check permission levels
$permission_level = getPermissionLevel($_SESSION['egps_username']);
if( $permission_level > $MINIMUM_AUTHORIZATION_LEVEL || $permission_level == NULL) {
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}

//************** validated past here ****************
$school_code="";
if(isset($_GET['school_code'])) $school_code= addslashes($_GET['school_code']);
if(isset($_POST['school_code'])) $school_code = addslashes($_POST['school_code']);

//get the coordination of services for this student...
$school_row="";
$school_query="SELECT * FROM school WHERE school_code=$school_code";
$school_result = mysql_query($school_query);
if(!$school_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$school_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
   $school_row=mysql_fetch_array($school_result);
}

if(!$school_row) {
   //we shouldn't be here without a student id.
   echo "You've entered this page without supplying a valid school id. Fatal, quitting";
   exit();
}

function parse_submission() {
    //returns null on success else returns $szError
    $regexp='/^[0-9]*$/';
    if(!preg_match($regexp, $_POST['school_code'])) return "You must supply a valid school code (numbers only)<BR>";
    if(!$_POST['school_name']) return "You must supply a school name<BR>";
    if(!$_POST['school_address']) return "You must supply a school address<BR>";
    if(!$_POST['school_colour']) $_POST['school_colour'] = "#FFFFFF";

    //check that colour is the correct pattern...
    $regexp = '/^#[0-9a-fA-F]{6}$/';
    if(!preg_match($regexp,$_POST['school_colour'])) return "Colour must be in '#RRGGBB' format<BR>";

    return NULL;
}

//check if we are modifying a student...
if(isset($_POST['edit_school'])) {
  $retval=parse_submission();
  if($retval != NULL) {
    //no way...
    $MESSAGE = $MESSAGE . $retval;
  } else {
    //we add the entry.
    $red=substr($_POST['school_colour'],1,2);
    $green=substr($_POST['school_colour'],3,2);
    $blue=substr($_POST['school_colour'],5,2);
    $insert_query = "UPDATE school SET school_name='" . addslashes($_POST['school_name']) . "',school_address='" . addslashes($_POST['school_address']) . "',red='$red',green='$green',blue='$blue'";
    $insert_query .= " WHERE school_code='$school_code' LIMIT 1";
    $insert_result = mysql_query($insert_query);
     if(!$insert_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '" . $insert_query . "<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
     } else {
        //redirect
        header("Location: " . IPP_PATH . "src/school_info.php");
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
         -Concept and Design by Grasslands IPP Design Group 2005
         -Programming and Database Design by M. Nielsen, Grasslands
          Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
    <script language="javascript" src="<?php echo IPP_PATH . "include/picker.js"; ?>"></script>
    <SCRIPT LANGUAGE="JavaScript">
      function confirmChecked() {
          var szGetVars = "schoollist=";
          var szConfirmMessage = "Are you sure you want to delete the following:\n";
          var count = 0;
          form=document.schoollist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + form.elements[x].name + ",";
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
                    <center><?php navbar("school_info.php"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">- Edit School-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN edit school -->
                        <center>
                        <form name="edit_school" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/edit_school.php"; ?>" method="post">
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Update'.</p>
                           <input type="hidden" name="edit_school" value="1">
                          </td>
                        </tr>
                        <tr>
                            <td valign="bottom" bgcolor="#E0E2F2" class="row_default">School Code:</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <input type="text" tabindex="1" name="school_code" value="<?php echo $school_row['school_code']; ?>" size="30" maxsize="254">
                            </td>
                            <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="4" class="row_default"><input type="submit" tabindex="5" value="Update" value="Update"></td>
                        </tr>
                        <tr>
                            <td valign="bottom" bgcolor="#E0E2F2" class="row_default">School Name:</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <input type="text" tabindex="2" name="school_name" value="<?php echo $school_row['school_name']; ?>" size="30" maxsize="254">
                            </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">School Address:</td>
                           <td bgcolor="#E0E2F2" class="row_default"><textarea name="school_address" tabindex="3" cols="30" rows="3" wrap="soft"><?php echo $school_row['school_address']; ?></textarea></td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">School Colour:</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <INPUT TYPE="TEXT" NAME="school_colour" MAXLENGTH="7" tabindex="4" SIZE="7" value="#<?php echo $school_row['red'] . $school_row['green'] . $school_row['blue']; ?>">
                               <a href="javascript:TCP.popup(document.forms['edit_school'].elements['school_colour'], 1)"><img width="15" height="13" border="0" alt="Click Here to Pick the color" src="<?php echo IPP_PATH . "images/colour_sel.gif"; ?>"></a>
                           </td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add school -->

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
              <?php navbar("school_info.php"); ?>
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
