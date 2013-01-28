<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100;    //everybody (do checks within document)

/**
 * student_view.php -- manage individual student
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: June 15, 2005
 * By: M. Nielsen
 * Modified: March 12, 2006
 * Modified: February 17,2007. M. Nielsen
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
}

$student_query = "select * from student where student.student_id=" . $_GET['student_id'];
$student_result = mysql_query($student_query);
if(!$student_query) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

$student_row=mysql_fetch_array($student_result);
$student_id=$student_row['student_id'];

//find support members...
$support_member_query = "SELECT * FROM support_list WHERE student_id=" . $_GET['student_id'] . " ORDER BY egps_username";
$support_member_result = mysql_query($support_member_query);
if(!$support_member_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$support_member_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}


//find the current coding...
$coding_query = "SELECT * FROM coding WHERE student_id=" . $_GET['student_id'] . " AND end_date IS NULL";
$coding_result = mysql_query($coding_query);
if(!$coding_query) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$coding_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}
$coding_row=mysql_fetch_array($coding_result);

//************** validated past here SESSION ACTIVE****************

//get our permissions for this student...
$our_permission = getStudentPermission($_GET['student_id']);

if($our_permission != "READ" && $our_permission != "WRITE" && $our_permission != "ASSIGN" && $our_permission != "ALL") {
  //we don't have permission...
  $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
  IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
  require(IPP_PATH . 'src/security_error.php');
  exit();
}

$supervisor_row="";
$supervisor_query = "SELECT * FROM supervisor WHERE student_id=" . addslashes($_GET['student_id']) . " AND end_date IS NULL";
$supervisor_result = mysql_query($supervisor_query);
if(!$supervisor_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$supervisor_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
    //there is only one row (or should be...so get it.)
    $supervisor_row=mysql_fetch_array($supervisor_result);
}

$school_row="";
$school_query = "SELECT * FROM school_history LEFT JOIN school on school_history.school_code=school.school_code WHERE end_date IS NULL AND student_id='" . $_GET['student_id'] . "'";
$school_result = mysql_query($school_query);
if(!$school_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$school_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
    //there is only one row (or should be...so get it.)
    $school_row=mysql_fetch_array($school_result);
}


//make sure they entered a valid date (mm-dd-yyyy)
function get_age_by_date($yyyymmdd)
{
    global $MESSAGE;
    $bdate = explode("-", $yyyymmdd);
    $dob_month=$bdate[1]; $dob_day=$bdate[2]; $dob_year=$bdate[0];
    if (checkdate($dob_month, $dob_day, $dob_year)) {
        $dob_date = "$dob_year" . "$dob_month" . "$dob_day";
        $age = floor((date("Ymd")-intval($dob_date))/10000);
        if (($age < 0) or ($age > 114)) {
            return $age . "<BR> -->Age warning: Negative or Zero (check D.O.B)<--";
        }
        return $age;
    }
    return "-unknown-";
}
//$age contains age of student.

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
                    <center><?php navbar("manage_student.php"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table width="80%" cellspacing="0" cellpadding="0"><tr><td><center><p class="header">-Student View-</p></center></td></tr>
                                                                                   <tr><td><center><p class="bold_text"> <?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?></p></center></td></tr>
                                                                                   <tr><td><center><p class="bold_text"> Current Age: <?php echo get_age_by_date($student_row['birthday']) ?></center></td></tr>
                                                                                   <?php if($school_row['school_name']=="") echo "<tr><td><center><p class=\"message\">-Archived Student-</p></center></td></tr>"  ?>

                        </table></center>
                        <BR>

                        <center>
                        <?php $colour0="#DFDFDF"; $colour1="#CCCCCC"; ?>
                        <center><a href="<?php echo IPP_PATH . "src/ipp_pdf.php?student_id=" . $student_row['student_id'] . "&file=ipp.pdf";?>" target="_blank"><img src="<?php echo IPP_PATH . "images/view-ippbutton.png";?>" border="0"></a>
                        </center>
                        <HR>
                        <!-- Nav -->
                        <a href="<?php echo IPP_PATH . "src/guardian_view.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Guardians";?>" border="0"></a>
                        <!--a href="<?php echo IPP_PATH . "src/supervisor_view.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Supervisor";?>" border="0"></a -->
                        <a href="<?php echo IPP_PATH . "src/strength_need_view.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Strength+%26+Needs"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/coordination_of_services.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Coord.+of+Services"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/achieve_level.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Achieve+Level"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/medical_info.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Medical+Info."?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/medication_view.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Medication"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/testing_to_support_code.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Testing+to+Support"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/background_information.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Background+Info"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/year_end_review.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Year+End+Review"?>" border="0" target="_blank"></a>
                        <a href="<?php echo IPP_PATH . "src/anecdotals.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Anecdotals"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/assistive_technology.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Asst.+Technology"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/transition_plan.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Transition+Plan"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/accomodations.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Accommodations"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/snapshots.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Snapshots"?>" border="0"></a>
                        <a href="<?php echo IPP_PATH . "src/long_term_goal_view.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Goals"?>" border="0"></a>
                        <!-- end NAV -->
                        <HR>
                        <!-- BEGIN CODING INFORMATION -->
                        <table width="80%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td colspan="3">
                            <p class="bold_text">Coding
                        </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour1; ?>">
                                Current Code:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour1; ?>">
                                <?php
                                if(mysql_num_rows($coding_result) <= 0) {
                                    echo "Currently not coded";
                                } else {
                                    echo $coding_row['code'] . " since<BR> " . $coding_row['start_date'];
                                }
                                ?>
                            </td>
                            <td width="100" rowspan="7" valign="center">
                               <a href="<?php echo IPP_PATH . "src/coding.php?student_id=" . $student_row['student_id'];?>""><img src="<?php echo IPP_PATH . "images/smallbutton.php?title=Edit";?>" border="0">
                            </td>
                        </tr>
                        </table>

                        <!-- END SCHOOL INFORMATION -->

                        <!-- The general stuff -->
                        <table width="80%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td colspan="3">
                            <p class="bold_text">General Information
                        </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour0; ?>">
                                Name:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour0; ?>">
                                <?php echo $student_row['first_name'] . " " . $student_row['last_name'];?>
                            </td>
                            <td width="100" rowspan="7" valign="center">
                               <?php
                                   if($our_permission != "WRITE" && $our_permission != "ASSIGN" && $our_permission !="ALL")
                                       echo "<a href=\"" . IPP_PATH . "src/security_error.php\" onClick=\"return noPermission();\"><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Modify\" border=\"0\">";
                                   else
                                       echo "<a href=\"" . IPP_PATH . "src/edit_general.php?student_id=" . $student_row['student_id'] . "\"><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Edit\" border=\"0\">";
                               ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour0; ?>">
                                Sex:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour0; ?>">
                                <?php if($student_row['gender'] =="F") echo "Female"; else echo "Male";?>
                            </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour0; ?>">
                                Date of Birth:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour0; ?>">
                                <?php echo $student_row['birthday'];?>
                            </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour0; ?>">
                                Current Grade:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour0; ?>">
                                <?php
                                      switch ($student_row['current_grade']) {
                                        case '0':
                                           echo "K or Pre-K";
                                           break;
                                        case '-1':
                                           echo "District Program";
                                           break;
                                        default:
                                            echo  $student_row['current_grade'];
                                      }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour0; ?>">
                                Student Number:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour0; ?>">
                                <?php echo $student_row['prov_ed_num'];?>
                            </td>
                        </tr>
                        </table>
                        <!-- END The general stuff -->

                        <!-- BEGIN Supervisor INFORMATION -->
                        <table width="80%" border="0" cellpadding="0" cellspacing="0">
                        <!-- The general stuff -->
                        <tr>
                        <td colspan="3">
                            <p class="bold_text">Supervisor
                        </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour1; ?>">
                                Current Supervisor:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour1; ?>">
                                <?php echo $supervisor_row['egps_username'];?>
                            </td>
                            <td width="100" rowspan="7" valign="center">
                               <a href="<?php echo IPP_PATH . "src/supervisor_view.php?student_id=" . $_GET['student_id'];?>"><img src="<?php echo IPP_PATH . "images/smallbutton.php?title=Change";?>" border="0">
                            </td>
                        </tr>
                        </table>

                        <!-- END Supervisor INFORMATION -->




                        <!-- BEGIN Support Member Information -->
                        <table width="80%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td colspan="3">
                            <p class="bold_text">Support Members
                        </td>
                        <td rowspan="<?php $iSupportNum=mysql_num_rows($support_member_result); if($iSupportNum <= 0) echo "2"; else echo $iSupportNum +1; ?>" valign="center" align="right" width="100">
                            <?php
                            if($our_permission !="ALL" && $our_permission !="ASSIGN" && $our_permission != "WRITE" )
                                echo "<a href=\"" . IPP_PATH . "src/security_error.php\" onClick=\"return noPermission();\"><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Modify\" border=\"0\">";
                            else
                                echo "<a href=\"" . IPP_PATH . "src/modify_ipp_permission.php?student_id=" . $_GET['student_id'] . "\"><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Manage\" border=\"0\">";
                            ?>
                        </td>
                        <?php
                            if(mysql_num_rows($support_member_result) <=0) {
                                echo "<tr><td colspan=\"3\" align=\"center\" bgcolor=\"$colour1\">-none-</td></tr>";
                            }
                        ?>
                        </tr>
                        <?php
                            while($support_member_row=mysql_fetch_array($support_member_result)) {
                                echo "<tr>\n";
                                echo "<td class=\"field_text\" bgcolor=\"$colour0\">" . $support_member_row['egps_username'] . "</td>\n";
                                echo "<td class=\"result_text\" bgcolor=\"$colour0\">" . $support_member_row['permission'] . "</td>\n";
                                if($support_member_row['support_area'] == "")
                                    echo "<td class=\"result_text\" bgcolor=\"$colour0\">No area assigned</td>\n";
                                else
                                    echo "<td class=\"result_text\" bgcolor=\"$colour0\">" . $support_member_row['support_area'] . "</td>\n";
                                echo "</tr>\n";
                            }
                        ?>
                        </table>
                        <!-- END Support Member Information -->

                        <!-- BEGIN SCHOOL INFORMATION -->
                        <table width="80%" border="0" cellpadding="0" cellspacing="0">
                        <!-- The general stuff -->
                        <tr>
                        <td colspan="3">
                            <p class="bold_text">School Information
                        </td>
                        </tr>
                        <tr>
                            <td class="field_text" bgcolor="<?php echo $colour1; ?>">
                                Current School:
                            </td>
                            <td class="result_text" bgcolor="<?php echo $colour1; ?>">
                                <?php
                                 if($school_row['school_name']=="")
                                  echo "-Archived Student-";
                                 else
                                  echo $school_row['school_name'] . " since<BR>" . $school_row['start_date'];
                                ?>
                            </td>
                            <td width="100" rowspan="7" valign="center">
                              <?php if($our_permission !="ALL" && $our_permission !="ASSIGN" && $our_permission != "WRITE" )
                               echo "<a href=\"" . IPP_PATH . "src/school_history.php?student_id=" . $student_id . "\" onClick=\"return noPermission();\"><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Move/History" . "\" border=\"0\">";
                              else
                               echo "<a href=\""  . IPP_PATH . "src/school_history.php?student_id=" . $student_id . "\"><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Move/History" . "\" border=\"0\">";
                              ?>
                            </td>
                        </tr>
                        </table>

                        <!-- END SCHOOL INFORMATION -->
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
            <?php navbar("manage_student.php"); ?>
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