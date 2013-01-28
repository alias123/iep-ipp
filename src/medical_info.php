<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody

/**
 * medical_info.php  //students general medical information.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: July 26, 2005
 * By: M. Nielsen
 * Modified: February 17, 2007. M. Nielsen
 *
 */

/*   INPUTS: $_GET['student_id'] || $_PUT['student_id']
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

function asc2hex ($temp) {
   $len = strlen($temp);
   for ($i=0; $i<$len; $i++) $data.=sprintf("%02x",ord(substr($temp,$i,1)));
   return $data;
}

function parse_submission() {
    //returns null on success else returns $szError
    global $content,$fileName,$fileType;

    //check that date is the correct pattern...
    $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
    if(!preg_match($regexp,$_POST['date'])) return "Date must be in YYYY-MM-DD format<BR>";

     if($_POST['description'] == "") return "You must supply a description<BR>";

     if($_FILES['supporting_file']['size'] <= 0) { $fileName=""; $tmpName="";$fileSize=0;$fileType=null; return NULL; } //handle no file upload.
     if($_FILES['supporting_file']['size'] >= 1048576) return "File must be smaller than 1MB (1048567Bytes) but is " . $_FILES['supporting_file']['size'] . "MB"; //Must be less than 1 Megabyte

     //we have a file so get the file information...
     $fileName = addslashes($_FILES['supporting_file']['name']);
     $tmpName  = $_FILES['supporting_file']['tmp_name'];
     $fileSize = addslashes($_FILES['supporting_file']['size']);
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

      switch($ext) {
         case "txt":
         case "rtf":
         case "TXT":
         case "RTF":
           //make sure we don't have binary data here.
           for($i = 0; $i < strlen($content); $i++){
              if(ord($content[$i]) > 127) { IPP_LOG("Attempted to upload binary data as txt in IPP Coordination of Services page for student #$student_id",$_SESSION['egps_username'],'ERROR'); return "Not a valid Text file: contains binary data<BR>"; }
           }
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
if(isset($_POST['add_medical_info']) && $have_write_permission) {
  $retval=parse_submission();
  if($retval != NULL) {
    //no way...
    $MESSAGE = $MESSAGE . $retval;
  } else {
    //we add the entry.
    $insert_query = "INSERT INTO medical_info (student_id,copy_in_file,is_priority,date,description,file,filename) VALUES (" . addslashes($student_id) . ",'";
     if(isset($_POST['report_in_file'])) $insert_query = $insert_query . "Y";
     else $insert_query = $insert_query . "N";
     if(isset($_POST['is_priority'])) $insert_query = $insert_query . "','Y";
     else $insert_query = $insert_query . "','N";
     $insert_query = $insert_query . "','" . addslashes($_POST['date']) . "','" . addslashes($_POST['description']) . "','$content','$fileName')";
     $insert_result = mysql_query($insert_query);
     if(!$insert_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '" . substr($insert_query,0,100) . "[truncated]'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
     } else {
       //$MESSAGE = $MESSAGE . $insert_query . "<BR>";
        unset($_POST['date']);
        unset($_POST['description']);
        unset($_POST['report_in_file']);
        unset($_POST['is_priority']);
     }
  }
}

//check if we are deleting some entries...
if(isset($_GET['delete_x']) && $permission_level <= $IPP_MIN_DELETE_MEDICAL_INFO && $have_write_permission ) {
    $delete_query = "DELETE FROM medical_info WHERE ";
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

//check if we are setting some entries to not in file...
//if($_GET['set_not_in_file'] && $have_write_permission ) {
//    $update_query = "UPDATE coordination_of_services SET report_in_file='N' WHERE ";
//    foreach($_GET as $key => $value) {
//        if(preg_match('/^(\d)*$/',$key))
//        $update_query = $update_query . "uid=" . $key . " or ";
//    }
    //strip trailing 'or' and whitespace
//    $update_query = substr($update_query, 0, -4);
    //$MESSAGE = $MESSAGE . $update_query . "<BR>";
    //$update_result = mysql_query($update_query);
//    if(!$update_result) {
//        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$update_query'<BR>";
//        $MESSAGE= $MESSAGE . $error_message;
//        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
//    }
//}

//check if we are setting some entries to in file...
//if($_GET['set_in_file'] && $have_write_permission ) {
//    $update_query = "UPDATE coordination_of_services SET report_in_file='Y' WHERE ";
//    foreach($_GET as $key => $value) {
//        if(preg_match('/^(\d)*$/',$key))
//        $update_query = $update_query . "uid=" . $key . " or ";
//    }
    //strip trailing 'or' and whitespace
//    $update_query = substr($update_query, 0, -4);
    //$MESSAGE = $MESSAGE . $update_query . "<BR>";
    //$update_result = mysql_query($update_query);
//    if(!$update_result) {
//        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$update_query'<BR>";
//        $MESSAGE= $MESSAGE . $error_message;
//        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
//    }
//}

//get the coordination of services for this student...
$medical_query="SELECT * FROM medical_info WHERE student_id=$student_id ORDER BY is_priority ASC, date DESC";

$medical_result = mysql_query($medical_query);
if(!$medical_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$medical_query'<BR>";
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
          form=document.medicalinfo;
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

                        <center><table><tr><td><center><p class="header">-Medical Information(<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN add new entry -->
                        <center>
                        <form name="add_medical_info" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/medical_info.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Add a new entry</p>
                           <input type="hidden" name="add_medical_info" value="1">
                           <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                          </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Date: (YYYY-MM-DD)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="text" tabindex="1" name="date" value="<?php if(isset($_POST['date'])) echo $_POST['date']; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.date, 'yyyy-m-dd', 0, 0)">
                           </td>
                           <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="5" class="row_default"><input type="submit" tabindex="6" name="add" value="add"></td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Optional File Upload:<BR>(.doc,.pdf,.txt,.rtf)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
                               <input type="file" tabindex="2" name="supporting_file" value="<?php if(isset($_FILES['supporting_file']['name'])) echo $_FILES['supporting_file']['name'] ?>">
                           </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Report in File:</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="checkbox" tabindex="3" name="report_in_file" <?php if(isset($_POST['report_in_file']) && $_POST['report_in_file']) echo "checked";?>>
                           </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Priority Entry:</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="checkbox" tabindex="4" name="is_priority" <?php if(isset($_POST['is_priority']) && $_POST['is_priority']) echo "checked";?>>
                           </td>
                        </tr>
                        <tr>
                           <td valign="center" bgcolor="#E0E2F2" class="row_default">Description:</td><td bgcolor="#E0E2F2" class="row_default"><textarea name="description" tabindex="5" cols="30" rows="3" wrap="SOFT"><?php  if(isset($_POST['description'])) echo $_POST['description']; ?></textarea></td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add new entry -->

                        <!-- BEGIN medical table -->
                        <form name="medicalinfo" onSubmit="return confirmChecked();" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/medical_info.php"; ?>" method="get">
                        <input type="hidden" name="student_id" value="<?php echo $student_id ?>">
                        <center><table width="80%" border="0" cellpadding="0" cellspacing="1">
                        <tr><td colspan="7">Current Medical Information (click to edit):</td></tr>
                        <?php
                        $bgcolor = "#DFDFDF";

                        //print the header row...
                        echo "<tr><td bgcolor=\"#E0E2F2\">&nbsp;</td><td bgcolor=\"#E0E2F2\">uid</td><td align=\"center\" bgcolor=\"#E0E2F2\">Date</td><td align=\"center\" bgcolor=\"#E0E2F2\">Description</td><td align=\"center\" bgcolor=\"#E0E2F2\">In File</td><td align=\"center\" bgcolor=\"#E0E2F2\">Priority</td><td align=\"center\" bgcolor=\"#E0E2F2\">File</td></tr>\n";
                        while ($medical_row=mysql_fetch_array($medical_result)) { //current...
                            echo "<tr>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><input type=\"checkbox\" name=\"" . $medical_row['uid'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\">" . $medical_row['uid'] . "</td>";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><a href=\"" . IPP_PATH . "src/edit_medical_info.php?uid=" . $medical_row['uid'] . "\" class=\"editable_text\">" . $medical_row['date']  ."</a></td>\n";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><a href=\"" . IPP_PATH . "src/edit_medical_info.php?uid=" . $medical_row['uid'] . "\" class=\"editable_text\">" . checkSpelling($medical_row['description'])  ."</a></td>\n";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><center><a href=\"" . IPP_PATH . "src/edit_medical_info.php?uid=" . $medical_row['uid'] . "\" class=\"editable_text\">" . $medical_row['copy_in_file'] . "</a></center></td>\n";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><center><a href=\"" . IPP_PATH . "src/edit_medical_info.php?uid=" . $medical_row['uid'] . "\" class=\"editable_text\">"; if($medical_row['is_priority'] == "Y") echo "<img src=\"" . IPP_PATH . "images/caution.gif" . "\" border=\"0\">"; else echo "N"; echo "</a></center></td>\n";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><center>"; if($medical_row['filename'] =="") echo "-none-"; else echo "<a href=\"" . IPP_PATH . "src/get_attached.php?table=medical_info&uid=" . $medical_row['uid'] ."&student_id=" . $student_id ."\">Download</a>"; echo "</center></td>\n";
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
                                //if($have_write_permission) {
                                //    echo "<INPUT NAME=\"set_not_in_file\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Not+In+File\" border=\"0\" value=\"1\">";
                                //    echo "<INPUT NAME=\"set_in_file\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=In+File\" border=\"0\" value=\"1\">";
                                //}
                                //if we have permissions also allow delete and set all.
                                if($permission_level <= $IPP_MIN_DELETE_MEDICAL_INFO && $have_write_permission) {
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
                        <!-- end medical table -->

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