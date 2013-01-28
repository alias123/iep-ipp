<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody

/**
 * edit_testing_to_support_code.php
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: March 11, 2005
 * By: M. Nielsen
 * Modified: February 17, 2007
 *
 */

/*   INPUTS: $_GET['uid'] || $_PUT['uid']
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

$uid="";
if(isset($_GET['uid'])) $uid= addslashes($_GET['uid']);
if(isset($_POST['uid'])) $uid = addslashes($_POST['uid']);

//get the coordination of services for this student...
$testing_row = "";
$testing_query="SELECT * FROM testing_to_support_code WHERE uid=$uid";
$testing_result = mysql_query($testing_query);
if(!$testing_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$testing_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
 $testing_row=mysql_fetch_array($testing_result);
}
$student_id=$testing_row['student_id'];

if($student_id=="") {
   //we shouldn't be here without a student id.
   echo "Unable to determine student id from Testing uid. Fatal, quitting";
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

function asc2hex ($temp) {
   $len = strlen($temp);
   for ($i=0; $i<$len; $i++) $data.=sprintf("%02x",ord(substr($temp,$i,1)));
   return $data;
}

function parse_submission() {
    //returns null on success else returns $szError
    global $content,$fileName,$fileType;

    if(!$_POST['description']) return "You must supply a test description<BR>";
    if(!$_POST['recommendations']) return "You must supply recommendations<BR>";
    //check that date is the correct pattern...
    $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
    if(!preg_match($regexp,$_POST['date'])) return "Date must be in YYYY-MM-DD format<BR>";

     if($_FILES['supporting_file']['size'] <= 0 && $_FILES['supporting_file']['name'] !="") return "Zero bytes uploaded (Most likely the file was too large and the server timed out on upload or the file was not handled properly by the server)";
     if($_FILES['supporting_file']['size'] <= 0) { $fileName=""; $tmpName="";$fileSize=0;$fileType=null; return NULL; } //handle no file upload.
     if($_FILES['supporting_file']['size'] >= 1048576) return "File must be smaller than 1MB (1048567Bytes) but is " . $_FILES['supporting_file']['size'] . "MB"; //Must be less than 1 Megabyte

     //we have a file so get the file information...
     $fileName = addslashes($_FILES['supporting_file']['name']);
     $tmpName  = $_FILES['supporting_file']['tmp_name'];
     $fileSize = addslashes($_FILES['supporting_file']['size']);
     //$fileType = addslashes($_FILES['supporting_file']['type']);
     $fileType = "";

     if(is_uploaded_file($tmpName)){
       $ext =explode('.', $fileName);
       $ext = $ext[count($ext)-1];
     } else {
       return "Security problem: file does not look like an uploaded file<BR>";
     }

      $fp      = fopen($tmpName, 'rb');
      if(!$fp) return "Unable to open temporary upload file $tmpname<BR>";
      $content = fread($fp, filesize($tmpName));
      $content = addslashes($content);
      fclose($fp);

      //return $fileType . "<-filetype<BR>";

      switch($ext) {
         case "txt":
         case "rtf":
         case "TXT":
         case "RTF":
           //make sure we don't have binary data here.
           for($i = 0; $i < strlen($content); $i++){
              if(ord($content[$i]) > 127) { IPP_LOG("Attempted to upload binary data as txt in IPP Coordination of Services page for student #$student_id",$_SESSION['egps_username'],'ERROR'); return "Not a valid Text file: contains binary data<BR>"; }
           }
           $content=addslashes($content);
           $fileType="text/plain";
         break;
         case "pdf":
         case "PDF":
          if(strncmp("%PDF-",$content,5) != 0) { IPP_LOG("Attempted to upload file not recognized as PDF in first few bytes in IPP Coordination of Services page for student #$student_id",$_SESSION['egps_username'],'ERROR'); return "File does not appear to be a valid PDF file<BR>"; }
          $fileType="application/pdf";
         break;
         case "doc":
         case "DOC":
         //check for 0xD0CF (word document magic number)
         for($i=0;$i < 2; $i++) {
            $msg = $msg . $content[$i];
         }
         $msg = "0x" . bin2hex($msg);
         if($msg != "0xd0cf") { IPP_LOG("Attempted to upload file not recognized as MS Word Document in IPP Coordination of Services page for student #$student_id",$_SESSION['egps_username'],'ERROR'); return "File does not appear to be a valid MS Word Document file<BR>"; }
         $fileType="application/msword";
         break;
         default:
           return "File extension '$ext' on '$fileName' is not a recognized type please upload only MS Word, Plain Text, or PDF documents<BR>";
     }

     return NULL;
}

//check if we are modifying a student...
if(isset($_POST['edit_testing']) && $have_write_permission) {
  $retval=parse_submission();
  if($retval != NULL) {
    //no way...
    $MESSAGE = $MESSAGE . $retval;
  } else {
    //we add the entry.
     $update_query = "UPDATE testing_to_support_code SET test_description='". addslashes($_POST['description']) . "',administered_by='" . addslashes($_POST['administered_by']) ."',date='" . addslashes($_POST['date']) . "',recommendations='" . addslashes($_POST['recommendations']) . "'";
     if($fileName != "") $update_query = $update_query . ",filename='$fileName',file='$content'";
     $update_query .= " WHERE uid=$uid LIMIT 1";
     $update_result = mysql_query($update_query);
     if(!$update_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '" . substr($update_query,0,300) . "[truncated]'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
     } else {
       //redirect...
       header("Location: " . IPP_PATH . "src/testing_to_support_code.php?student_id=" . $student_id);
     }
     //$MESSAGE = $MESSAGE . $insert_query . "<BR>";
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
    <script language="javascript" src="<?php echo IPP_PATH . "include/popcalendar.js"; ?>"></script>
    <SCRIPT LANGUAGE="JavaScript">
      function confirmChecked() {
          var szGetVars = "strengthneedslist=";
          var szConfirmMessage = "Are you sure you want to modify/delete the following:\n";
          var count = 0;
          form=document.testing;
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
                    <center><?php navbar("testing_to_support_code.php?student_id=$student_id"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">-Edit Testing to Support Code<BR>(<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN add new entry -->
                        <center>
                        <form name="add_testing" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/edit_testing_to_support_code.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit entry</p>
                           <input type="hidden" name="edit_testing" value="1">
                           <input type="hidden" name="uid" value="<?php echo $uid; ?>">
                          </td>
                        </tr>
                        <tr>
                            <td valign="center" bgcolor="#E0E2F2" class="row_default">Test Description:</td><td bgcolor="#E0E2F2" class="row_default"><textarea name="description" tabindex="1" cols="30" rows="5" wrap="soft"><?php echo $testing_row['test_description']; ?></textarea></td>
                            <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="5" class="row_default"><input type="submit" tabindex="7" name="Update" value="Update"></td>
                        </tr>
                        <tr>
                            <td valign="center" bgcolor="#E0E2F2" class="row_default">Administered By:</td><td bgcolor="#E0E2F2" class="row_default"><input type="text" tabindex="2" name="administered_by" value="<?php echo $testing_row['administered_by']; ?>" size="30" maxsize="254"></td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Date: (YYYY-MM-DD)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="text" tabindex="3" name="date" value="<?php echo $testing_row['date']; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.date, 'yyyy-m-dd', 0, 0)">
                           </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Optional File Upload:<BR>(.doc,.pdf,.txt,.rtf)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
                               <input type="file" tabindex="4" name="supporting_file" value="<?php echo $_FILES['supporting_file']['name'] ?>">
                           </td>
                        </tr>
                        <tr>
                           <td valign="center" bgcolor="#E0E2F2" class="row_default">Results & Recommendations:</td><td bgcolor="#E0E2F2" class="row_default"><textarea name="recommendations" tabindex="6" cols="30" rows="5" wrap="soft"><?php echo $testing_row['recommendations']; ?></textarea></td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add new entry -->

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
            <?php navbar("testing_to_support_code.php?student_id=$student_id"); ?>
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
