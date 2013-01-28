<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100;  //all, decide in the page

/**
 * superuser_manage_users.php -- IPP manage users main menu
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: June 06, 2005
 * By: M. Nielsen
 * Modified: March 11, 2005
 * Modified: April 20, 2006 M. Nielsen (empty school table not showing in list)
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
if(getPermissionLevel($_SESSION['egps_username']) > $MINIMUM_AUTHORIZATION_LEVEL) {
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}

//************** validated past here SESSION ACTIVE****************
$permission_level=getPermissionLevel($_SESSION['egps_username']);
//check permission levels
if($permission_level > $MINIMUM_AUTHORIZATION_LEVEL || $permission_level == NULL) {
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}

//check if we are deleting some peeps...
//print_r ($_POST);

if(isset($_POST['delete_x'])) {
    if(!connectIPPDB()) {
        $MESSAGE = $MESSAGE . $error_message;  //just to remember we need this
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    }

    $delete_query = "DELETE FROM student WHERE ";
    foreach($_POST as $key => $value) {
        if(preg_match('/^(\d)*$/',$key))
        $delete_query = $delete_query . "student_id=" . $key . " or ";
    }
    //strip trailing 'or' and whitespace
    $delete_query = substr($delete_query, 0, -4);
    //echo $delete_query . "<-><BR>";
    //$MESSAGE = $MESSAGE . $delete_query . "<BR>";
    $delete_result = mysql_query($delete_query);
    if(!$delete_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$delete_query'<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    }
}

  //get the list of all users...
  //wonder how php handles dangling else...
  if(!isset($_GET['iLimit']))
    if(!isset($_POST['iLimit'])) $iLimit = 50;
        else $iLimit=$_POST['iLimit'];
  else $iLimit = $_GET['iLimit'];

  if(!isset($_GET['iCur']))
    if(!isset($_POST['iCur'])) $iCur = 0;
    else $iCur=$_POST['iCur'];
  else $iCur = $_GET['iCur'];

  if(!isset($_GET['szSchool']))
    if(!isset($_POST['szSchool'])) $szSchool = "ALL";
    else $szSchool=$_POST['szSchool'];
  else $szSchool = $_GET['szSchool'];

$szTotal=0;
function getStudents() {
    global$error_message,$permission_level,$MESSAGE,$IPP_MIN_VIEW_LIST_ALL_LOCAL_STUDENTS,$IPP_MIN_VIEW_LIST_ALL_STUDENTS,$iLimit,$iCur,$szSchool,$szTotalStudents;
    if(!connectIPPDB()) {
        $MESSAGE = $MESSAGE . $error_message;  //just to remember we need this
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    }

    //do a subquery to find our school code...easier than messing with the ugly
    //query below...
    $school_code_query="SELECT school_code FROM support_member WHERE egps_username='" . addslashes($_SESSION['egps_username']) . "'";
    $school_code_result=mysql_query($school_code_query);
    if(!$school_code_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$school_code_query'<BR>";
        return NULL;
    }
    $school_code_row=mysql_fetch_array($school_code_result);
    $school_code= $school_code_row['school_code'];

    $student_query = "SELECT DISTINCT student.student_id,last_name,first_name FROM student LEFT JOIN support_list ON student.student_id = support_list.student_id LEFT JOIN school_history ON student.student_id=school_history.student_id WHERE ((support_list.egps_username='" . addslashes($_SESSION['egps_username']) . "' AND support_list.student_id IS NOT NULL)";
    //prior to april 20/06: $student_query = "SELECT DISTINCT student.student_id,last_name,first_name FROM student LEFT JOIN support_list ON student.student_id = support_list.student_id LEFT JOIN school_history ON student.student_id=school_history.student_id WHERE ((support_list.egps_username='" . addslashes($_SESSION['egps_username']) . "' AND support_list.student_id IS NOT NULL)";
    //prior to march 18/06: $student_query = "SELECT DISTINCT student.student_id,last_name,first_name,school_history.school_code,school.* FROM student LEFT JOIN support_list ON student.student_id = support_list.student_id LEFT JOIN school_history ON student.student_id=school_history.student_id LEFT JOIN school ON school_history.school_code=school.school_code WHERE (support_list.egps_username='" . addslashes($_SESSION['egps_username']) . "' AND support_list.student_id IS NOT NULL) OR (";
    
    if(($IPP_MIN_VIEW_LIST_ALL_STUDENTS >= $permission_level)) {
        //orig 2006-04-20: $student_query = $student_query . " OR (end_date IS NOT NULL)";
        $student_query = $student_query . " OR (student.student_id IS NOT NULL)";
    }
    $student_query .= ") AND NOT EXISTS (SELECT student.student_id,last_name,first_name FROM school_history WHERE school_history.student_id=student.student_id AND school_history.end_date IS NULL)";
    $student_query_limit = $student_query . " ORDER BY student.last_name ASC LIMIT $iCur,$iLimit";
    $student_result_limit = mysql_query($student_query_limit);
    if(!$student_result_limit) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query_limit'<BR>";
        return NULL;
    }
    //$MESSAGE = $MESSAGE . "rows returned: " . mysql_num_rows($student_result_limit) . "<BR>";
    //$MESSAGE = $MESSAGE . $student_query_limit . "<BR>";

    //find the totals...
    $student_result_total = mysql_query($student_query);
    if(!$student_result_total) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
        return NULL;
    }
    $szTotalStudents =  mysql_num_rows($student_result_total);
    $MESSAGE = $MESSAGE . "Number of archived students: $szTotalStudents<BR>";
    $MESSAGE = $MESSAGE . "(Showing: " . mysql_num_rows($student_result_limit) . ")<BR>";
    //$MESSAGE = $MESSAGE . "<BR>$student_query<BR><BR>";
    return $student_result_limit;
}



$sqlStudents=getStudents(); //$szTotalStudents contains total number of stdnts.


//get totals...

if(!$sqlStudents) {
    $MESSAGE = $MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}


//set back vars...
$szBackGetVars="";
foreach($_GET as $key => $value) {
    $szBackGetVars = $szBackGetVars . $key . "=" . $value . "&";
}
//strip trailing '&'
$szBackGetVars = substr($szBackGetVars, 0, -1);


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
         -CSS and layout images are courtesy A. Clapton.
     -->

    <SCRIPT LANGUAGE="JavaScript">
      function deleteChecked() {
          var szGetVars = "delete_users=";
          var szConfirmMessage = "Are you sure you want to delete:\n";
          var count = 0;
          form=document.studentlist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + form.elements[x].value + " (ID #" + form.elements[x].name + ")\n";
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

      function notYetImplemented() {
          alert("Functionality not yet implemented"); return false;
      }

      function noPermission() {
          alert("You don't have the permissions"); return false;
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
                    <tr><td>
                    <center><?php navbar("main.php"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">-Archive-</p></center></td></tr></table></center>
                        <HR>

                        <!-- search fx >
                        <form enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/manage_student.php"; ?>" method="get">
                        <center><table width="80%" cellspacing="0">
                        <tr>
                        <td align=center bgcolor="#E0E2F2">&nbsp;
                        </td>
                        </tr>
                        <tr>
                        <td align=center bgcolor="#E0E2F2">
                            Search:&nbsp;
                            <SELECT name="field">
                            <option value="last_name" <?php if($_GET['field'] == "last_name") echo "selected"; ?>>Last Name
                            <option value="first_name" <?php if($_GET['field'] == "first_name") echo "selected"; ?>>First Name
                            <option value="school_name" <?php if($_GET['field'] == "school_name") echo "selected"; ?>>School Name
                            <option value="school_code" <?php if($_GET['field'] == "school_code") echo "selected"; ?>>School Code
                            </SELECT>
                            &nbsp;is&nbsp;&nbsp;<input type="text" name="szSearchVal" size="15" value="<?php echo $_GET['szSearchVal'];?>">&nbsp;Limit:&nbsp;<input type="text" name="iLimit" size="5" value="<?php echo $iLimit; ?>">&nbsp;<input type="submit" value="Query" name="SEARCH">
                            <p class="small_text">(Wildcards: '%'=match any '_'=match single)</p>
                        </td>
                        </tr></table></center>
                        </form>
                        <-- end search fx -->


                        <form name="studentlist" onSubmit="return deleteChecked()" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/student_archive.php"; ?>" method="post">
                        <center><table width="80%" border="0">
                        <?php
                        $bgcolor = "#DFDFDF";

                        //print the next and prev links...
                        echo "<tr><td>&nbsp;</td><td>";
                        if($iCur != 0) {
                            //we have previous values...
                            echo "<a href=\"./student_archive.php?iCur=" . ($iCur-$iLimit) . "&iLimit=$iLimit&szSearch=&szSearchVal=" . $_GET['szSearchVal'] . "&field=" . $_GET['field'] . "&SEARCH=" . $_GET['SEARCH'] . "\" class=\"default\">previous $iLimit</a>";
                        } else {
                            echo "&nbsp;";
                        }
                        echo "</td><td colspan=\"2\" align=\"center\">";
                        echo "Click Username to view";
                        echo "</td>";
                        if(($iLimit+$iCur < $szTotalStudents)) {
                            echo "<td align=\"right\"><a href=\"./student_archive.php?iCur=" . ($iCur+$iLimit) . "&iLimit=$iLimit&szSearchVal=" . $_GET['szSearchVal'] . "&field=" . $_GET['field'] . "&SEARCH=" . $_GET['SEARCH'] . "\" class=\"default\">next ";
                            if( $sqlLogTotals-($iCur+$iLimit) > $iLimit) {
                                echo $iLimit . "</td>";
                            } else {
                                echo ($szTotalStudents-($iCur+$iLimit)) . "</td>";
                            }
                        } else {
                            echo "<td>&nbsp;</td>";
                        }
                        echo "</tr>\n";
                        //end print next and prev links

                        //print the header row...
                        echo "<tr><td bgcolor=\"#E0E2F2\">&nbsp;</td><td align=\"center\" bgcolor=\"#E0E2F2\">UID</td><td align=\"center\" bgcolor=\"#E0E2F2\">Last Name, First Name</td><td align=\"center\" bgcolor=\"#E0E2F2\">School</td><td align=\"center\" bgcolor=\"#E0E2F2\">Permission</td></tr>\n";
                        while ($student_row=mysql_fetch_array($sqlStudents)) {
                            $current_student_permission = getStudentPermission($student_row['student_id']);
                            echo "<tr>\n";
                            $school_colour = "#FFFFFF"; //all white.
                            echo "<td bgcolor=\"$school_colour\"><input type=\"checkbox\" name=\"" . $student_row['student_id'] . "\" value=\"" . $student_row['first_name'] . " " . $student_row['last_name'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\">" . $student_row['student_id'] . "<p></td>\n";
                            echo "<td bgcolor=\"$bgcolor\"><a href=\"" . IPP_PATH . "src/student_view.php?student_id=" . $student_row['student_id'] . "\" class=\"default\" ";
                            if($current_student_permission == "NONE" || $current_student_permission == "ERROR") {
                                echo "onClick=\"return noPermission();\" ";
                            }
                            echo ">" .  $student_row['last_name'] . "," . $student_row['first_name'] . "</a>";
                            if($current_student_permission == "READ" || $current_student_permission != "WRITE" || $current_student_permission != "ALL") {
                                echo "<a href=\"". IPP_PATH . "src/ipp_pdf.php?student_id=" . $student_row['student_id'] . "\" class=\"default\" target=\"_blank\"";
                                if($current_student_permission == "NONE" || $current_student_permission == "ERROR") {
                                echo "onClick=\"return noPermission();\" ";
                                }
                                echo "><img src=\"". IPP_PATH . "images/pdf.png\" align=\"top\" border=\"0\"></a>";
                            }
                            echo "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\"><p class=\"small_text\">-none-<p></td>\n";
                            echo "<td bgcolor=\"$bgcolor\" align=\"center\" class=\"row_default\"><p class=\"small_text\">$current_student_permission<p></td>\n";
                            echo "</tr>\n";
                            if($bgcolor=="#DFDFDF") $bgcolor="#CCCCCC";
                            else $bgcolor="#DFDFDF";
                        }
                        if($permission_level <= $IPP_MIN_DELETE_STUDENT_PERMISSION)
                            echo "<tr><td colspan=\"5\" align=\"left\"><img src=\"" . IPP_PATH . "images/table_arrow.png\">&nbsp;With Selected: <INPUT TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" name=\"delete\" value=\"1\"></td></tr>\n";
                        
                        ?>
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
            <center><?php navbar("main.php"); ?></center>
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