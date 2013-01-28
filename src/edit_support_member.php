<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody

/**
 * edit_support_member.php -- strength and needs management.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: February 9, 2005
 * By: M. Nielsen
 * Modified: February 17, 2007 M. Nielsen
 *
 */

/*   INPUTS: $_GET['student_id'] or PUT
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
require_once(IPP_PATH . 'include/mail_functions.php');

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
} else {
   $student_id = addslashes($student_id);
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

//get the support list for this student...
$support_row="";
$support_query="SELECT * FROM support_list LEFT JOIN support_member ON support_list.egps_username=support_member.egps_username WHERE student_id=$student_id AND support_list.egps_username='" . AddSlashes($_GET['username']) . "'";
$support_result = mysql_query($support_query);
if(!$support_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$support_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
    $support_row = mysql_fetch_array($support_result);
}

//check if we are modifying...
if($have_write_permission && isset($_GET['modify'])) {
    //check if we are trying to update the permission but don't have the rights...
    if($_GET['permission'] != $support_row['permission'] && !($our_permission == "ALL" || $our_permission == "ASSIGN")) {
        $MESSAGE = $MESSAGE . "You don't have the permission level necessary to modify permission levels<BR>";
    } else {
       if(($_GET['permission'] == "ALL" || $_GET['permission'] == "ASSIGN") && !($our_permission == "ALL")) {
         $MESSAGE = $MESSAGE . "You must be set with 'ALL' level permission to grant 'ASSIGN' permissions and higher";
       } else {
         //we need to update the information here...
         $update_query = "UPDATE support_list SET support_area='" . addslashes($_GET['support_area']) . "', permission='" . addslashes($_GET['permission']) . "' WHERE student_id=$student_id AND egps_username='" . AddSlashes($_GET['username']) . "'";
         $update_result = mysql_query($update_query);
         if(!$update_result) {
              $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$update_query'<BR>";
              $MESSAGE= $MESSAGE . $error_message;
              IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
         } else {
            if(isset($_GET['mail_notification'])) {
              mail_notification(AddSlashes($_GET['username']),"This email has been sent to you to notify you that your permission levels for " . $student_row['first_name'] . " " . $student_row['last_name'] . "'s IPP on the $IPP_ORGANIZATION online individual program plan system have been changed to " . addslashes($_GET['permission']) . " access.");
            }
            //we need to redirect back to main...
            header("Location: " . IPP_PATH . "src/student_view.php?student_id=$student_id");
         }
       }
    }
}

//redo the query...one day should come up with a more efficient method...
//get the support list for this student...
$support_row="";
$support_query="SELECT * FROM support_list LEFT JOIN support_member ON support_list.egps_username=support_member.egps_username WHERE student_id=$student_id AND support_list.egps_username='" . AddSlashes($_GET['username']) . "'";
$support_result = mysql_query($support_query);
if(!$support_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$support_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
    $support_row = mysql_fetch_array($support_result);
}

/*************************** popup chooser support function ******************/
    function createJavaScript($dataSource,$arrayName='rows'){
      // validate variable name
      if(!is_string($arrayName)){
        $MESSAGE = $MESSAGE . "Error in popup chooser support function name supplied not a valid string  (" . __FILE__ . ":" . __LINE__ . ")";
        return FALSE;
      }

    // initialize JavaScript string
      $javascript='<!--Begin popup array--><script>var '.$arrayName.'=[];';

    // check if $dataSource is a file or a result set
      if(is_file($dataSource)){
       
        // read data from file
        $row=file($dataSource);

        // build JavaScript array
        for($i=0;$i<count($row);$i++){
          $javascript.=$arrayName.'['.$i.']="'.trim($row[$i]).'";';
        }
      }

      // read data from result set
      else{

        // check if we have a valid result set
        if(!$numRows=mysql_num_rows($dataSource)){
          die('Invalid result set parameter');
        }
        for($i=0;$i<$numRows;$i++){
          // build JavaScript array from result set
          $javascript.=$arrayName.'['.$i.']="';
          $tempOutput='';
          //output only the first column
          $row=mysql_fetch_array($dataSource);

          $tempOutput.=$row[0].' ';

          $javascript.=trim($tempOutput).'";';
        }
      }
      $javascript.='</script><!--End popup array-->'."\n";

      // return JavaScript code
      return $javascript;
    }

    function echoJSServicesArray() {
        global $MESSAGE,$student_id;
        //get a list of all available goal categories...
        $catlist_query="SELECT name FROM typical_long_term_goal_category WHERE is_deleted='N' ORDER BY name ASC";
        $catlist_result=mysql_query($catlist_query);
        if(!$catlist_result) {
            $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$catlist_query'<BR>";
            $MESSAGE= $MESSAGE . $error_message;
            IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
            return;
        } else {
             //call the function to create the javascript array...
             echo createJavaScript($catlist_result,"popuplist");
        }
    }
/************************ end popup chooser support funtion  ******************/

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
         -CSS and layout images are courtesy A. Clapton.
     -->
    <script language="javascript" src="<?php echo IPP_PATH . "include/popupchooser.js"; ?>"></script>
    <script language="javascript" src="<?php echo IPP_PATH . "include/autocomplete.js"; ?>"></script>
     <?php
       //output the javascript array for the chooser popup
       echoJSServicesArray();
     ?>
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
                    <center><?php navbar("modify_ipp_permission.php?student_id=$student_id"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">-Edit Permissions(<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN add supervisor -->
                        <center>
                        <form name="edit_support_member" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/edit_support_member.php"; ?>" method="get" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Modify'.</p>
                           <input type="hidden" name="modify" value="1">
                           <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                           <input type="hidden" name="username" value="<?php echo $_GET['username']; ?>">
                          </td>
                        </tr>
                        <tr>
                            <td valign="bottom" bgcolor="#E0E2F2" class="row_default">Username:</td><td bgcolor="#E0E2F2" class="row_default"><?php echo $_GET['username']; ?></td>
                            <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="4" class="row_default"><input type="submit" name="modify" value="modify"></td>
                        </tr>
                        <tr>
                           <td valign="bottom" bgcolor="#E0E2F2" class="row_default">IPP Permission:</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <select name="permission">
                                   <option value="">-Choose-</option>
                                   <option value="READ" <?php if($support_row['permission'] == 'READ') echo "SELECTED"; ?>>Read (Read Only)</option>
                                   <option value="WRITE" <?php if($support_row['permission'] == 'WRITE') echo "SELECTED"; ?>>Write (Read and Write)</option>
                                   <option value="ASSIGN" <?php if($support_row['permission'] == 'ASSIGN') echo "SELECTED"; ?>>Assign (Read,Write,Assign others permissions)</option>
                                   <option value="ALL" <?php if($support_row['permission'] == 'ALL') echo "SELECTED"; ?>>All (Unlimited permission)</option>
                               </select>
                           </td>
                        </tr>
                        <tr>
                           <td valign="center" bgcolor="#E0E2F2" class="row_default">Support Area:</td><td bgcolor="#E0E2F2" class="row_default"><input type="text" size="40" name="support_area" onkeypress="return autocomplete(this,event,popuplist)" value="<?php echo $support_row['support_area'];?>">&nbsp;<img align="top" src="<?php echo IPP_PATH . "images/choosericon.png"; ?>" height="17" width="17" border=0 onClick="popUpChooser(this,document.all.support_area);" ></td>
                        </tr>
                        <tr>
                            <td bgcolor="#E0E2F2" class="row_default">
                              Send email notification
                            </td>
                            <td bgcolor="#E0E2F2">
                             <input type="checkbox" <?php if(!isset($_POST['ACTION']) || (isset($_POST['ACTION']) && isset($_POST['mail_notification']))) echo "checked"; ?> name="mail_notification">
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
            <?php navbar("modify_ipp_permission.php?student_id=$student_id"); ?>
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
