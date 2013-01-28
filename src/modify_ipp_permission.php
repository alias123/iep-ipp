<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 60;    //Teaching staff and up

/**
 * modify_ipp_permissions.php -- manage this ipps permissions.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: June 15, 2005
 * By: M. Nielsen
 * Modified: July 21, 2005
 * Modified: February 17, 2007  By M. Nielsen
 *
 */

/**
 * Path for IPP required files.
 */

if(isset($MESSAGE)) $MESSAGE = $MESSAGE; else $MESSAGE = "";

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

$student_id="";

if(isset($_GET['student_id'])) {
   $student_id = $_GET['student_id'];
}

if(isset($_POST['student_id'])) {
   $student_id = $_POST['student_id'];
}

if($student_id=="") {
    //ack
    echo "You've come to this page without a valid student ID<BR>To what end I wonder...<BR>";
    exit();
}



//************** validated past here SESSION ACTIVE****************

//get our permissions for this student...
$our_permission = getStudentPermission($student_id);

if($our_permission != "WRITE" && $our_permission != "ASSIGN" && $our_permission != "ALL") {
  //we don't have permission...
  $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
  IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
  require(IPP_PATH . 'src/security_error.php');
  exit();
}

//see if we need to update some permission values or delete somebody...
function update_permissions() {
    global $MESSAGE,$our_permission,$student_id;

    //get a list of all affected
    $user_list="";
    foreach($_POST as $key => $value) {
        if($key != "delete_users" && $value=="on"  )
        $user_list = $user_list . "egps_username='" . str_replace("_",".",$key) . "' or ";
    }
    //strip trailing 'or' and whitespace
    $user_list = substr($user_list, 0, -4);

    $query="UPDATE support_list SET ";
        if(isset($_POST['SET_ALL_x']))
        {
           if($our_permission != "ALL") {$MESSAGE = $MESSAGE . "You do not have sufficient permission to set this permission<BR>"; return FALSE; }
           $query = $query . "permission='ALL' WHERE student_id=$student_id AND " . $user_list;
        }
        if(isset($_POST['DELETE_x']))  {
           if($our_permission != "ASSIGN" && $our_permission !="ALL") {$MESSAGE = $MESSAGE . "You do not have sufficient permission to delete users<BR>"; return FALSE; }
           $query = "DELETE FROM support_list WHERE $user_list AND student_id=$student_id";
        }
        if(isset($_POST['SET_READ_x'])) {
           if($our_permission != "ASSIGN" && $our_permission != "ALL") {$MESSAGE = $MESSAGE . "You do not have sufficient permission to set this permission<BR>"; return FALSE; }
           $query = $query . "permission='READ' WHERE student_id=$student_id AND " . $user_list;
        }
        if(isset($_POST['SET_WRITE_x'])) {
           if($our_permission != "ASSIGN" && $our_permission != "ALL") {$MESSAGE = $MESSAGE . "You do not have sufficient permission to set this permission<BR>"; return FALSE; }
           $query = $query . "permission='WRITE' WHERE student_id=$student_id AND " . $user_list;
        }
        if(isset($_POST['SET_ASSIGN_x'])) {
           if($our_permission != "ASSIGN" && $our_permission != "ALL") {$MESSAGE = $MESSAGE . "You do not have sufficient permission to set this permission<BR>"; return FALSE; }
           $query = $query . "permission='ASSIGN' WHERE student_id=$student_id AND " . $user_list;
        }

    $result = mysql_query($query);
    if(!$result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$query'<BR>";
        $MESSAGE = $MESSAGE . $error_message;
    }
}
if(isset($_POST['SET_ASSIGN_x']) || isset($_POST['SET_WRITE_x']) || isset($_POST['SET_READ_x']) || isset($_POST['SET_ALL_x']) || isset($_POST['DELETE_x'])) {
    update_permissions();
}

if(!isset($_GET['iLimit'])) $iLimit = 10; else $iLimit = $_GET['iLimit'];
if(!isset($_GET['iCur'])) $iCur = 0; else $iCur = $_GET['iCur'];

$student_query = "select * from student where student.student_id=" . $student_id;
$student_result = mysql_query($student_query);
if(!$student_query) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

$student_row=mysql_fetch_array($student_result);


//get a list of all support members for this IPP...
function getSupportMembers() {
    global $error_message,$iLimit,$iCur,$student_id;
    if(!connectIPPDB()) {
        $MESSAGE = $MESSAGE . $error_message;  //just to remember we need this
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    }
    // LEFT JOIN area_list ON support_list.uid=area_list.support_list_uid LEFT JOIN area_type ON area_list.area_type_id=area_type.area_type_id
    // original $query = "SELECT * FROM support_list where student_id=" . $student_id . " ORDER BY egps_username ASC LIMIT $iCur,$iLimit";
    $query = "SELECT * FROM support_list where student_id=" . $student_id . " ORDER BY egps_username ASC";

    $result = mysql_query($query);
    if(!$result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$query'<BR>";
        return NULL;
    }
    return $result;
}

$sqlSupportMembers=getSupportMembers();
if(!$sqlSupportMembers) {
    $MESSAGE = $MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

//find a total num support members for nav bar...
$total_query="SELECT * FROM support_list where student_id=" .$student_id;
$total_result = mysql_query($total_query);
if(!$total_result) {
    $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$total_query'<BR>";
}

$total_support_members = mysql_num_rows($total_result);

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
      function deleteChecked() {
          var szGetVars = "delete_users=";
          var szConfirmMessage = "Are you sure you want to Modify/Delete:\n";
          var count = 0;
          form=document.userlist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + form.elements[x].name + " ";
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
    <SCRIPT LANGUAGE="JavaScript">
      function notYetImplemented() {
          alert("Functionality not yet implemented"); return false;
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

                        <center><table width="80%" cellspacing="0" cellpadding="0"><tr><td><center><p class="header">-Manage Support Members-</p></center></td></tr><tr><td><center><p class="header"> <?php echo $student_row['first_name'] . " " . $student_row['last_name']?></p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN new member add -->
                        <center>
                        <form enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/new_ipp_permission.php"; ?>" method="get" <?php if($our_permission != "ASSIGN" && $our_permission != "ALL") echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellpadding="0" cellspacing="0" width="80%">
                        <input type="hidden" name="student_id" value="<?php echo $student_id;?>">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Add member (firstname.lastname)</p>
                          </td>
                        </tr>
                        <tr>
                          <td bgcolor="#E0E2F2" align="right">IEP-IPP Username:</td><td bgcolor="#E0E2F2"><input type="text" name="username" length="30"></td><td align="left" bgcolor="#E0E2F2" rowspan="2">&nbsp;&nbsp;<input type="submit" value="Search"></td>
                        </tr>
                        <tr>
                        <td colspan="2" bgcolor="#E0E2F2" align="center"><p class="small_text">(Wildcards: '%'=match any '_'=match single)</p></td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END NEW MEMBER ADD -->

                        <?php //display support... ?>
                        <form name="userlist" onSubmit="return deleteChecked()" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/modify_ipp_permission.php"; ?>" method="post">
                        <input type="hidden" name="student_id" value="<?php echo $student_id ?>">
                        <center><table width="80%" border="0">

                        <?php
                        $bgcolor = "#DFDFDF";

                        //print the next and prev links...
                        echo "<tr><td colspan=\"5\" class=\"row_default\"><b>Please note: removing yourself from this list will remove your access to this student's IPP (even if you are still listed as the supervisor for this student). You will have to contact your school based IPP administrator to have your permissions restored.<b></td></tr>";
                        echo "<tr><td>";
                        if($iCur != 0) {
                            //we have previous values...
                            echo "<a href=\"./modify_ipp_permissions.php?iCur=" . ($iCur-$iLimit) . "\" class=\"default\">previous $iLimit</a>";
                        } else {
                            echo "&nbsp;";
                        }
                        echo "</td><td colspan=\"3\">";
                        echo "<center>Browse Current Support Group</center>";
                        echo "</td>";
                        if ( ($iLimit+$iCur) < $total_support_members) {
                            echo "<td align=\"right\"><a href=\"./modify_ipp_permissions.php?iCur=" . ($iCur+$iLimit) . "\" class=\"default\">next ";
                            if( $total_support_members-($iCur+$iLimit) > $iLimit) {
                                echo $iLimit . "</td>";
                            } else {
                                echo ($total_support_members-($iCur+$iLimit)) . "</td>";
                            }
                        } else {
                            echo "<td>&nbsp;</td>";
                        }
                        echo "</tr>\n";
                        //end print next and prev links

                        //print the header row...
                        echo "<tr><td bgcolor=\"#E0E2F2\">&nbsp;</td><td align=\"center\" bgcolor=\"#E0E2F2\">Username</td><td align=\"center\" bgcolor=\"#E0E2F2\">permission_level</td><td align=\"center\" bgcolor=\"#E0E2F2\">Support Area</td><td align=\"center\" bgcolor=\"#E0E2F2\">&nbsp;</td></tr>\n";
                        while ($users_row=mysql_fetch_array($sqlSupportMembers)) {
                            echo "<tr>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><input type=\"checkbox\" name=\"" . $users_row['egps_username'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\">" . $users_row['egps_username'] . "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">" . $users_row['permission'] . "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\">";
                            if (!$users_row['support_area']) echo "None assigned"; else echo $users_row['support_area'];
                            echo "</td>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><a href=\"" . IPP_PATH . "src/edit_support_member.php?username=" . $users_row['egps_username'] . "&student_id=$student_id" . "\"><IMG SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Edit\" border=\"0\"></a></td>";
                            echo "</tr>\n";
                            if($bgcolor=="#DFDFDF") $bgcolor="#CCCCCC";
                            else $bgcolor="#DFDFDF";
                        }
                        ?>
                        <tr>
                          <td colspan="5" align="left">
                             <table>
                             <tr>
                             <td nowrap>
                                <img src="<?php echo IPP_PATH . "images/table_arrow.png"; ?>">&nbsp;With Selected:
                             </td>
                             <td>
                                <INPUT NAME="SET_ASSIGN" TYPE="image" SRC="<?php echo IPP_PATH . "images/smallbutton.php?title=Set Assign"; ?>" border="0" value="SET_ASSIGN">
                                <INPUT NAME="SET_WRITE" TYPE="image" SRC="<?php echo IPP_PATH . "images/smallbutton.php?title=Set Write"; ?>" border="0" value="SET_WRITE">
                                <INPUT NAME="SET_READ" TYPE="image" SRC="<?php echo IPP_PATH . "images/smallbutton.php?title=Set Read"; ?>" border="0" value="SET_READ">
                             <?php
                                //if we have all permissions also allow delete and set all...
                                if($our_permission =="ALL") {
                                    echo "<INPUT NAME=\"SET_ALL\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Set All\" border=\"0\" value=\"SET_ALL\">";
                                }
                                if($our_permission =="ASSIGN" || $our_permission=="ALL") {
                                    echo "<INPUT NAME=\"DELETE\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" value=\"DELETE\">";
                                }
                             ?>
                             </td>
                             </tr>
                             </table>
                          </td>
                        </tr>
                        </table></center>
                        </form>

                        <BR>

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
