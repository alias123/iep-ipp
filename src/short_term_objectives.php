<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody check within

/**
 * short_term_objectives.php -- strength and needs management.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: September 8, 2005
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

//$student_id="";
//if(isset($_GET['student_id'])) $student_id= $_GET['student_id'];
//if(isset($_POST['student_id'])) $student_id = $_POST['student_id'];

//find the student owner of this objective...
$long_term_goal_query="SELECT * FROM long_term_goal WHERE goal_id=" . addslashes($_GET['goal_id']);
$long_term_goal_result=mysql_query($long_term_goal_query);
if(!$long_term_goal_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$long_term_goal_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}
$long_term_goal_row=mysql_fetch_array($long_term_goal_result);
$student_id=$long_term_goal_row['student_id'];


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

//check if we are adding an objective...
if(isset($_GET['add_objective']) && $have_write_permission) {
  $description=strip_tags($_GET['description']);
  $description=eregi_replace("\r\n",' ',$description);
  $description=eregi_replace("\r",' ',$description);
  $description=eregi_replace("\n",' ',$description);
  $description= addslashes($description);
  //check if we have this objective already...
  $check_query="SELECT * FROM short_term_objective WHERE DESCRIPTION='$description' AND goal_id=" . $long_term_goal_row['goal_id'];
  $check_result=mysql_query($check_query);
  if(mysql_num_rows($check_result) > 0) { $MESSAGE = $MESSAGE . "This objective is already added<BR>"; }
  else {
     $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
     if(!preg_match($regexp,$_GET['review_date'])) { $MESSAGE = $MESSAGE . "Date must be in YYYY-MM-DD format<BR>"; }
     else {
      if($_GET['description']=="") { $MESSAGE = $MESSAGE . "You must supply a description"; } else
      {
       $insert_query = "INSERT INTO short_term_objective (goal_id,description,review_date) VALUES (" . $long_term_goal_row['goal_id'] . ",'$description','" . addslashes($_GET['review_date']) . "')";
       $insert_result = mysql_query($insert_query);
       if(!$insert_result) {
           $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$insert_query'<BR>";
           $MESSAGE=$MESSAGE . $error_message;
           IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
       } else {
           unset($_GET['review_date']);
          unset($_GET['description']);
      }
     }
    }
  }
}

//************** validated past here SESSION ACTIVE WRITE PERMISSION CONFIRMED****************

if($have_write_permission && $_GET['delete']) {
    $delete_query = "DELETE from short_term_objective WHERE uid=" . addslashes($_GET['sto']);
    $delete_result = mysql_query($delete_query);
    if(!$delete_result) {
      $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$delete_query'<BR>";
      $MESSAGE=$MESSAGE . $error_message;
      IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    } else {
      $MESSAGE = $MESSAGE . "Deleted short term objective<BR>";
    }
}

if($have_write_permission && $_GET['set_achieved']) {
    $achieved_query = "UPDATE short_term_objective SET achieved='Y' WHERE uid=" . addslashes($_GET['sto']);
    $achieved_result = mysql_query($achieved_query);
    if(!$achieved_result) {
      $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$achieved_query'<BR>";
      $MESSAGE=$MESSAGE . $error_message;
      IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    } else {
      $MESSAGE = $MESSAGE . "Set short term objective achieved<BR>";
    }
}

if($have_write_permission && $_GET['set_not_achieved']) {
    $achieved_query = "UPDATE short_term_objective SET achieved='N' WHERE uid=" . addslashes($_GET['sto']);
    $achieved_result = mysql_query($achieved_query);
    if(!$achieved_result) {
      $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$achieved_query'<BR>";
      $MESSAGE=$MESSAGE . $error_message;
      IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    } else {
      $MESSAGE = $MESSAGE . "Set short term objective not achieved<BR>";
    }
}

$student_query = "SELECT * FROM student WHERE student_id = " . addslashes($student_id);
$student_result = mysql_query($student_query);
if(!$student_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {$student_row= mysql_fetch_array($student_result);}

$objectives_query="SELECT * FROM short_term_objective WHERE goal_id=" . addslashes($long_term_goal_row['goal_id']) . " and achieved='Y'";
$objectives_result=mysql_query($objectives_query);
if(!$objectives_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$objectives_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

$completed_objectives_query="SELECT * FROM short_term_objective WHERE goal_id=" . addslashes($long_term_goal_row['goal_id']) . " and achieved='N'";
$completed_objectives_result=mysql_query($completed_objectives_query);
if(!$completed_objectives_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$completed_objectives_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
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
          $error_message = "PopupChooser: Bad Data Source (" . __FILE__ . ":" . __LINE__ . ")<BR>";
          $MESSAGE= $MESSAGE . $error_message;
          IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
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
        global $MESSAGE;
        //get a list of all available goal categories...
        $catlist_query="SELECT typical_short_term_objective.goal FROM long_term_goal RIGHT JOIN typical_long_term_goal ON long_term_goal.goal LIKE typical_long_term_goal.goal RIGHT JOIN typical_short_term_objective ON typical_long_term_goal.ltg_id=typical_short_term_objective.ltg_id WHERE long_term_goal.goal_id=" . addslashes($_GET['goal_id']) . " AND student_id=" . addslashes($_GET['student_id']);
        $catlist_result=mysql_query($catlist_query);
        if(!$catlist_result) {
            $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$catlist_query'<BR>";
            $MESSAGE= $MESSAGE . $error_message;
            IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
            return;
        } else {
            //$MESSAGE = $MESSAGE . "Rows returned=" . mysql_num_rows($catlist_result) . " Query=$catlist_query<BR><BR>";
            echo createJavaScript($catlist_result,"popuplist");
        }

        //while($catlist=mysql_fetch_array($catlist_result)) {
           //$objlist_query="SELECT typical_long_term_goal.goal FROM typical_long_term_goal WHERE cid=" . $catlist['cid'] . " AND typical_long_term_goal.is_deleted='N'";
           //$objlist_result = mysql_query($objlist_query);
           //if(!$objlist_result) {
            // $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$objlist_query'<BR>";
            // $MESSAGE= $MESSAGE . $error_message;
           //  IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
           //} else {
             //call the function to create the javascript array...
           //  echo createJavaScript($objlist_result,$catlist['name']);
           //}
        //}
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
          Regional Division #6
         -User Interface Design and Educational Factors by P Stoddart,
          Grasslands Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
    <script language="javascript" src="<?php echo IPP_PATH . "include/popcalendar.js"; ?>"></script>
    <script language="javascript" src="<?php echo IPP_PATH . "include/popupchooser.js"; ?>"></script>
     <?php
       //output the javascript array for the chooser popup
       echoJSServicesArray();
     ?>
    <SCRIPT LANGUAGE="JavaScript">
      function confirmChecked() {
          var szGetVars = "strengthneedslist=";
          var szConfirmMessage = "Are you sure you want to modify/delete the following:\n";
          var count = 0;
          form=document.medicationlist;
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
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center>
                          <table>
                            <tr><td>
                              <center><p class="header">- Short Term Objectives (<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center>
                            </td></tr>
                          </table>
                        </center>
                        <BR>

                        <!-- BEGIN add short term objective -->
                        <center>
                        <form name="add_objective" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/short_term_objectives.php"; ?>" method="get" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <center><HR><p class="bold_text">Long Term Goal: <?php echo $long_term_goal_row['goal']; ?> </p><HR></center>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Add'.</p>
                           <input type="hidden" name="add_objective" value="1">
                           <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                           <input type="hidden" name="goal_id" value="<?php echo $long_term_goal_row['goal_id']; ?>">
                          </td>
                        </tr>
                        <tr>
                            <td valign="bottom" bgcolor="#E0E2F2" class="row_default">Description:</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <textarea name="description" cols="25" rows="3" wrap="hard"><?php echo $_GET['description']; ?></textarea>&nbsp;<img align="top" src="<?php echo IPP_PATH . "images/choosericon.png"; ?>" height="17" width="17" border=0 onClick="popUpChooser(this,document.all.description);" >
                            </td>
                            <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="3" class="row_default"><input type="submit" name="add" value="add"></td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Review Date: (YYYY-MM-DD)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="text" name="review_date" value="<?php echo $_GET['review_date']; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.review_date, 'yyyy-m-dd', 0, 0)">
                           </td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add short term objective -->

                        <!-- BEGIN  Incomplete Goals -->
                        <center><table width="80%"><tr><td>
                            <p align="left" style="info_text"><b>Not yet Achieved Objective(s)</b></p>
                        </td></tr></table></center>
                        <BR>
                        <center>
                        <table width="80%" border="0" cellpadding="0" cellspacing="0">
                        <?php
                        while($goal = mysql_fetch_array($completed_objectives_result)) {
                            echo "<tr><td colspan=\"2\" class=\"wrap_top\">";

                            echo "<p class=\"info_text\"><B>Short Term Objective:</B> " . $goal['description'] . "&nbsp;&nbsp;<a href=\"" . IPP_PATH . "src/long_term_goal_view.php?student_id=" . $student_id . "&setCompleted=" . $goal['goal_id'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            else echo "onClick=\"return changeStatusCompleted();\"";
                            echo "></p>";
                            echo "</td></tr>\n";

                            //begin description
                            //width = 100% in first column is workaround for IE6 issue...
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\" width=\"100%\"><CENTER>(Next Review: " . $goal['review_date'] . ")</CENTER></td><td class=\"wrap_right\" width=\"100\">&nbsp;</td></tr>\n";
                            //echo "</tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\">&nbsp;</td><td class=\"wrap_right\" width=\"100\">&nbsp;</td></tr>\n";
                            //echo "<tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><b>Assessment Procedure:</b><blockquote>" . $goal['assessment_procedure'] . "</blockquote></td><td class=\"wrap_right\" rowspan=\"5\" width=\"100\">";
                            echo "<a href=\"" . IPP_PATH . "src/edit_short_term_objective.php?student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Edit\" border=\"0\" width=\"100\" height=\"25\" ></a>";

                            echo "</td></tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><b>Strategies:</b><blockquote>" . $goal['strategies'] . "</blockquote></tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><b>Results and Recommendations:</b><blockquote>" . $goal['results_and_recommendations'] . "</blockquote></td></tr>\n";

                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><BR></td></tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\" align=\"right\">";
                            echo "<a href=\"" . IPP_PATH . "src/review_short_term_objective.php?student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Review\" border=\"0\" width=\"100\" height=\"25\" ></a>";

                            echo "<a href=\"" . IPP_PATH . "src/short_term_objectives.php?set_achieved=1&goal_id=" . $long_term_goal_row['goal_id'] . "&student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Set+Achieved\" border=\"0\" width=\"100\" height=\"25\" ></a>";

                            echo "<a href=\"" . IPP_PATH . "src/short_term_objectives.php?delete=1&s&goal_id=" . $long_term_goal_row['goal_id'] . "&student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" width=\"100\" height=\"25\" ></a>";
                            echo "</td></tr>\n";


                            echo "<tr><td bgcolor=\"$colour0\" class=\"wrap_bottom_left\">\n";
                            echo "&nbsp;";
                            echo "</td>\n";
                            echo "<td class=\"wrap_bottom_right\">&nbsp;</td>";
                            echo "</tr>\n";
                            echo "<tr><td>&nbsp;</td><td width=\"100\">&nbsp;</td></tr>";
                        }
                        ?>
                        </table>
                        </center>
                        <!-- END incomplete goals -->

                        <!-- BEGIN  complete Goals -->
                        <center><table width="80%"><tr><td><p align="left" style="info_text"><b>Achieved Objective(s)</b></p></td></tr></table></center>
                        <BR>
                        <center>
                        <table width="80%" border="0" cellpadding="0" cellspacing="0">
                        <?php
                        while($goal = mysql_fetch_array($objectives_result)) {
                            echo "<tr><td colspan=\"2\" class=\"wrap_top\">";

                            echo "<p class=\"info_text\"><B>Short Term Objective:</B> " . $goal['description'] . "&nbsp;&nbsp;<a href=\"" . IPP_PATH . "src/long_term_goal_view.php?student_id=" . $student_id . "&setCompleted=" . $goal['goal_id'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            else echo "onClick=\"return changeStatusCompleted();\"";
                            echo "></p>";
                            echo "</td></tr>\n";

                            //begin description
                            //width = 100% in first column is workaround for IE6 issue...
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\" width=\"100%\"><CENTER>(Next Review: " . $goal['review_date'] . ")</CENTER></td><td class=\"wrap_right\" width=\"100\">&nbsp;</td></tr>\n";
                            //echo "</tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\">&nbsp;</td><td class=\"wrap_right\" width=\"100\">&nbsp;</td></tr>\n";
                            //echo "<tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><b>Assessment Procedure:</b><blockquote>" . $goal['assessment_procedure'] . "</blockquote></td><td class=\"wrap_right\" rowspan=\"5\" width=\"100\">";
                            echo "<a href=\"" . IPP_PATH . "src/edit_short_term_objective.php?student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Edit\" border=\"0\" width=\"100\" height=\"25\" ></a>";

                            echo "</td></tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><b>Strategies:</b><blockquote>" . $goal['strategies'] . "</blockquote></tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><b>Results and Recommendations:</b><blockquote>" . $goal['results_and_recommendations'] . "</blockquote></td></tr>\n";

                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\"><BR></td></tr>\n";
                            echo "<tr><td class=\"wrap_left\" bgcolor=\"$colour0\" align=\"right\">";
                            echo "<a href=\"" . IPP_PATH . "src/review_short_term_objective.php?student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Review\" border=\"0\" width=\"100\" height=\"25\" ></a>";

                            echo "<a href=\"" . IPP_PATH . "src/short_term_objectives.php?set_not_achieved=1&goal_id=" . $long_term_goal_row['goal_id'] . "&student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Set+Not+Achieved\" border=\"0\" width=\"100\" height=\"25\" ></a>";

                            echo "<a href=\"" . IPP_PATH . "src/short_term_objectives.php?delete=1&s&goal_id=" . $long_term_goal_row['goal_id'] . "&student_id=" . $student_id . "&sto=" . $goal['uid'] . "\"";
                            if (!$have_write_permission) echo "onClick=\"return noPermission();\"";
                            echo "><img src=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" width=\"100\" height=\"25\" ></a>";
                            echo "</td></tr>\n";


                            echo "<tr><td bgcolor=\"$colour0\" class=\"wrap_bottom_left\">\n";
                            echo "&nbsp;";
                            echo "</td>\n";
                            echo "<td class=\"wrap_bottom_right\">&nbsp;</td>";
                            echo "</tr>\n";
                            echo "<tr><td>&nbsp;</td><td width=\"100\">&nbsp;</td></tr>";
                        }
                        ?>
                        </table>
                        </center>
                        <!-- END complete goals -->

                        </div>
                        </td>
                    </tr>
                </table></center>
            </td>
            <td class="shadow-right"></td>   
        </tr>
        <tr>
            <td class="shadow-left">&nbsp;</td>
            <td class="shadow-center"><table border="0" width="100%"><tr><td><a href="
            <?php
                echo IPP_PATH . "src/long_term_goal_view.php?goal_id=" . $long_term_goal_row['goal_id'] . "&student_id=" . $long_term_goal_row['student_id'];
            ?>"><img src="<?php echo IPP_PATH; ?>images/back-arrow.png" border=0></a></td><td width="60"><a href="<?php echo IPP_PATH . "src/main.php"; ?>"><img src="<?php echo IPP_PATH; ?>images/homebutton.png" border=0></a></td><td valign="bottom" align="center">Logged in as: <?php echo $_SESSION['egps_username'];?></td><td align="right"><a href="<?php echo IPP_PATH;?>"><img src="<?php echo IPP_PATH; ?>images/logout.png" border=0></a></td></tr></table></td>
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