<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody

/**
 * edit_strength_need.php -- strength and needs management.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: March 11, 2005
 * By: M. Nielsen
 * Modified: February 17
 *
 */

/*   INPUTS: $_GET['uid']  or $_POST['uid']
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
if(isset($_POST['uid'])) $uid=$_POST['uid'];
else $uid=$_GET['uid'];
//run the strength/need query first then validate...
//get the strengths/needs for this student...
$strength_query="SELECT * FROM area_of_strength_or_need WHERE uid=" . addslashes($uid);
$strength_result = mysql_query($strength_query);
if(!$strength_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$strength_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
   $strength_row = mysql_fetch_array($strength_result);
}

$student_id=$strength_row['student_id'];

if($student_id=="") {
   //we shouldn't be here without a student id.
   echo "This entry has generated a 'null' student id, fatal error- quitting";
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
if(isset($_POST['edit_strength_or_need']) && $have_write_permission) {
   //minimal testing of input...
     if($_POST['strength_or_need'] == "") $MESSAGE = $MESSAGE . "You must choose either strength or need<BR>";
     if($_POST['is_valid'] != "Y" && $_POST['is_valid'] != "N") $MESSAGE = $MESSAGE . "Unknown 'ongoing' field value<BR>";
     else {
         $edit_query = "UPDATE area_of_strength_or_need SET strength_or_need='" . addslashes($_POST['strength_or_need']) . "',description='" . addslashes($_POST['description']) . "',is_valid='" . addslashes($_POST['is_valid']) . "' WHERE uid=" . addslashes($_POST['uid']) . " LIMIT 1";
         $edit_result = mysql_query($edit_query);
         if(!$edit_result) {
           $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$edit_query'<BR>";
           $MESSAGE=$MESSAGE . $error_message;
           IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
         } else {
           //redirect here...
           header("Location: " . IPP_PATH . "src/strength_need_view.php?student_id=" . $student_id);
         }
     }

   //$MESSAGE = $MESSAGE . $add_query . "<BR>";
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
    <SCRIPT LANGUAGE="JavaScript">
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
                    <center><?php navbar("strength_need_view.php?student_id=$student_id"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">-Edit Strengths & Needs(<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN edit strength/need -->
                        <center>
                        <form name="edit_strength_or_need" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/edit_strength_need.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Update'.</p>
                           <input type="hidden" name="edit_strength_or_need" value="1">
                           <input type="hidden" name="uid" value="<?php echo $strength_row['uid'];?>">
                          </td>
                        </tr>
                        <tr>
                           <td valign="bottom" bgcolor="#E0E2F2" class="row_default">Strength or Need:</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <select name="strength_or_need" tabindex="1">
                                   <option value="">-Choose-</option>
                                   <option value="Strength" <?php if($strength_row['strength_or_need'] == 'Strength') echo "SELECTED"; ?>>Strength</option>
                                   <option value="Need" <?php if($strength_row['strength_or_need'] == 'Need') echo "SELECTED"; ?>>Need</option>
                               </select>
                           </td>
                           <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="3" class="row_default"><input type="submit" tabindex="4" name="Update" value="Update"></td>
                        </tr>
                        <tr>
                           <td valign="center" bgcolor="#E0E2F2" class="row_default">Description:</td><td bgcolor="#E0E2F2" class="row_default"><textarea name="description" tabindex="2" cols="30" rows="5" wrap="soft"><?php echo $strength_row['description'];?></textarea></td>
                        </tr>
                        <tr>
                           <td valign="bottom" bgcolor="#E0E2F2" class="row_default">Ongoing:</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <select name="is_valid" tabindex="3">
                                   <option value="">-Choose-</option>
                                   <option value="Y" <?php if($strength_row['is_valid'] == 'Y') echo "SELECTED"; ?>>Yes</option>
                                   <option value="N" <?php if($strength_row['is_valid'] == 'N') echo "SELECTED"; ?>>No</option>
                               </select>
                           </td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add supervisor -->

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
            <?php navbar("strength_need_view.php?student_id=$student_id"); ?>
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
