<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 60; //teaching assistants and up

/**
 * edit_bug.php.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: March 13, 2006
 * By: M. Nielsen
 * Modified:
 *
 */

/*   INPUTS: none, nada...zip.
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

if(isset($_POST['uid'])) $uid=$_POST['uid'];
else $uid=$_GET['uid'];
//run the strength/need query first then validate...
//get the strengths/needs for this student...
$bug_row="";
$bug_query="SELECT * FROM bugs WHERE uid=" . addslashes($uid);
$bug_result = mysql_query($bug_query);
if(!$bug_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$bug_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
   $bug_row = mysql_fetch_array($bug_result);
}

//************** validated past here SESSION ACTIVE WRITE PERMISSION CONFIRMED****************

if($permission_level <= $IPP_MIN_EDIT_BUG_PERMISSION || $bug_row['username'] == $_SESSION['egps_username']) $have_write_permission = TRUE;
else $have_write_permission = FALSE;

//if($permission_level <= $IPP_MIN_EDIT_BUG_PERMISSION) $MESSAGE = $MESSAGE . "Have write permission<BR>";
//check if we are adding...
if(isset($_POST['edit_bug_report'])) {
   //minimal testing of input...
   if($_POST['bug'] == "") { $MESSAGE=$MESSAGE . "You must supply a bug/feature description<BR>"; }
   else {
     if(!$have_write_permission) { $MESSAGE = $MESSAGE . "You don't have permission<BR>"; }
     else {
       $update_query = "UPDATE bugs set bug='" . addslashes($_POST['bug']) . "'";
       if($permission_level <= $IPP_MIN_EDIT_BUG_PERMISSION) {
         $update_query .= ",resolution='" . addslashes($_POST['resolution']) . "',status='" . addslashes($_POST['status']) . "'";
       }
       $update_query .= " WHERE uid=$uid LIMIT 1";
       $update_result = mysql_query($update_query);
       if(!$update_result) {
         $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$update_query'<BR>";
         $MESSAGE=$MESSAGE . $error_message;
         IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
       } else {
         //redirect back...
         header("Location: " . IPP_PATH . "src/bug_report.php");
       }
     }
   }
   //$MESSAGE = $MESSAGE . $add_query . "<BR>";
}


//get enum fields for area...
function mysql_enum_values($tableName,$fieldName)
{
  $result = mysql_query("DESCRIBE $tableName");

  //then loop:
  while($row = mysql_fetch_array($result))
  {
   //# row is mysql type, in format "int(11) unsigned zerofill"
   //# or "enum('cheese','salmon')" etc.

   ereg('^([^ (]+)(\((.+)\))?([ ](.+))?$',$row['Type'],$fieldTypeSplit);
   //# split type up into array
   $ret_fieldName = $row['Field'];
   $fieldType = $fieldTypeSplit[1];// eg 'int' for integer.
   $fieldFlags = $fieldTypeSplit[5]; // eg 'binary' or 'unsigned zerofill'.
   $fieldLen = $fieldTypeSplit[3]; // eg 11, or 'cheese','salmon' for enum.

   if (($fieldType=='enum' || $fieldType=='set') && ($ret_fieldName==$fieldName) )
   {
     $fieldOptions = split("','",substr($fieldLen,1,-1));
     return $fieldOptions;
   }
  }

  //if the funciton makes it this far, then it either
  //did not find an enum/set field type, or it
  //failed to find the the fieldname, so exit FALSE!
  return FALSE;

}
$enum_options_type = mysql_enum_values("bugs","status");

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
                    <center><?php navbar("bug_report.php"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">- IPP Bug Tracking/Feature Request<BR></p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN edit bug -->
                        <center>
                        <form name="add_bug" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/edit_bug.php"; ?>" method="post">
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Update'.</p>
                           <input type="hidden" name="edit_bug_report" value="1">
                           <input type="hidden" name="uid" value="<?php echo $uid; ?>">
                          </td>
                        </tr>
                        <?php
                          if($permission_level <= $IPP_MIN_EDIT_BUG_PERMISSION) {
                          echo "<tr>";
                           echo "<td valign=\"bottom\" bgcolor=\"#E0E2F2\" class=\"row_default\">Status:</td>";
                           echo "<td bgcolor=\"#E0E2F2\" class=\"row_default\">";
                               echo "<select name=\"status\">";
                                   echo "<option value=\"\">-Choose-</option>";
                                   foreach($enum_options_type as $i => $value) {
                                      echo "<option value=\"$value\"";
                                      if($value == $bug_row['status']) echo " selected";
                                      echo ">$value</option>";
                                   }
                               echo "</select>";
                            echo "</td>";
                            echo " <td valign=\"center\" bgcolor=\"#E0E2F2\" class=\"row_default\">&nbsp;</td>";
                           echo "</tr>";

                          }
                        ?>
                        <tr>
                           <td valign="center" bgcolor="#E0E2F2" class="row_default">Bug Description or Feature Request:</td><td bgcolor="#E0E2F2" class="row_default"><textarea name="bug" cols="30" rows="5" wrap="soft"><?php echo $bug_row['bug']; ?></textarea></td>
                           <td valign="center" align="center" bgcolor="#E0E2F2" <?php if($permission_level <= $IPP_MIN_EDIT_BUG_PERMISSION) echo "rowspan=\"3\""; ?> class="row_default"><input type="submit" name="Update" value="Update"></td>
                         
                        </tr>
                        <?php
                        if($permission_level <= $IPP_MIN_EDIT_BUG_PERMISSION) {
                          echo "<tr><td valign=\"center\" bgcolor=\"#E0E2F2\" class=\"row_default\">Status:</td><td bgcolor=\"#E0E2F2\" class=\"row_default\"><textarea name=\"resolution\" cols=\"30\" rows=\"5\" wrap=\"soft\">" . $bug_row['resolution'] . "</textarea></td></tr>";
                          echo "<tr><td valign=\"center\" bgcolor=\"#E0E2F2\" class=\"row_default\">HTTP_REFER:</td><td bgcolor=\"#E0E2F2\" class=\"row_default\"><textarea name=\"refering_page\" cols=\"30\" rows=\"5\" wrap=\"soft\" disabled>" . $bug_row['referring_page'] . "</textarea></td></tr>";
                        }
                        ?>
                        </table>
                        </form>
                        </center>
                        <!-- END edit info -->


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
            <?php navbar("bug_report.php"); ?>
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