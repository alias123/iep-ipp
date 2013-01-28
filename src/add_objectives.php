<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100; //everybody check within

/**
 * add_objectives.php
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: March 02, 2005
 * By: M. Nielsen
 * Modified: April 19,2006
 *
 */

/*   INPUTS:
 *           $_POST['student_id']
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

if((!isset($_GET['student_id']) || $_GET['student_id']=="") &&  (!isset($_POST['student_id']) || $_POST['student_id'] == "")) {
    //ack
    echo "You've come to this page without a valid student ID<BR>To what end I wonder...<BR>";
    exit();
} else {
   if(!isset($_POST['student_id']))
    $student_id=addslashes($_GET['student_id']);
   else
    $student_id=addslashes($_POST['student_id']);
}

$goal_id="";
if(isset($_POST['lto'])) $goal_id=addslashes($_POST['lto']);
if(isset($_GET['lto'])) $goal_id=addslashes($_GET['lto']);

$our_permission = getStudentPermission($student_id);
if($our_permission == "WRITE" || $our_permission == "ASSIGN" || $our_permission == "ALL") {
    //we have write permission.
    $have_write_permission = true;
}  else {
    $have_write_permission = false;
}

//see if we are adding a goal
if(isset($_POST['add_goal']) && $have_write_permission) {

    if(!isset($_POST['description']) || $_POST['description'] == "") {
        $MESSAGE = $MESSAGE . "You must supply a description of this goal<BR>";
        header("Location: add_goal_1.php?student_id=$student_id&MESSAGE=$MESSAGE");
        exit();
    }  else {
      $description=strip_tags($_POST['description']);
      //$description=eregi_replace("\r\n",' ',$description);
      //$description=eregi_replace("\r",' ',$description);
      //$description=eregi_replace("\n",' ',$description);
      $description= addslashes($description);
      $check_query="SELECT * FROM long_term_goal WHERE student_id=" . addslashes($student_id) . " AND goal='" . addslashes($description) . "'";
      $check_result=mysql_query($check_query);
      if(mysql_num_rows($check_result) > 0) {
          $MESSAGE = $MESSAGE . "This is already set as a goal for this student (you might have hit reload)<BR>";
          $check_row=mysql_fetch_array($check_result);
          $goal_id=$check_row['goal_id'];
      } else {
        //check that date is the correct pattern...
        $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
        if(!preg_match($regexp,$_POST['review_date'])) {
          $MESSAGE = $MESSAGE . "Date must be in YYYY-MM-DD format<BR>";
          header("Location: " . IPP_PATH . "src/add_goal_1.php?goal_area=" . $_POST['goal_area'] . "&review_date=" . $_POST['review_date'] . "&description=" .  $_POST['description'] . "&MESSAGE=" . $MESSAGE . "&student_id=" . $student_id);
        }
        else {
            $area="";
            if($_POST['goal_area'] == "") {
                $area = "Other";
            } else {
                //lets get the area...
                $area_query="SELECT * FROM typical_long_term_goal_category WHERE cid=" . addslashes($_POST['goal_area']);
                $area_result=mysql_query($area_query);
                if(!$area_result) $area="Other";
                else { $area_row=mysql_fetch_array($area_result); $area = $area_row['name'];}
            }
            $insert_goal_query="INSERT INTO long_term_goal (goal,student_id,review_date,area) VALUES ('$description',$student_id,'" . addslashes($_POST['review_date']) . "','" . addslashes($area) . "')";
            $insert_goal_result = mysql_query($insert_goal_query);
            if(!$insert_goal_result) {
                $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$insert_goal_query'<BR>";
                $MESSAGE=$MESSAGE . $error_message;
                IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
                header("Location: " . IPP_PATH . "src/add_goal_1.php?MESSAGE=" . $MESSAGE . "&student_id=" . $student_id);
            }  else {
                $goal_id=mysql_insert_id();
                unset($_POST['description']);
            }
        }
      }
    }
}

if($goal_id == "" && (!isset($goal_id) || $goal_id=="") ) {
   $MESSAGE = $MESSAGE . "An error occured: you have arrived here without a valid goal id number, perhaps you don't have permission levels necessary.<BR>";
} else {
   if($goal_id == "") { $goal_id=$goal_id; }
   //find the student owner of this objective...
   $goal_query="SELECT long_term_goal.*,short_term_objective.*,long_term_goal.review_date AS goal_review_date FROM long_term_goal LEFT JOIN short_term_objective ON long_term_goal.goal_id=short_term_objective.goal_id WHERE long_term_goal.goal_id=" . addslashes($goal_id);
   $goal_result=mysql_query($goal_query);
   if(!$goal_result) {
     $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$goal_query'<BR>";
     $MESSAGE=$MESSAGE . $error_message;
     IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
   } else {
      $goal_row=mysql_fetch_array($goal_result);
      $student_id=$goal_row['student_id'];
      $goal_review_date=$goal_row['goal_review_date'];
      //recheck permissions...
   }
}

$our_permission = getStudentPermission($student_id);
if($our_permission == "WRITE" || $our_permission == "ASSIGN" || $our_permission == "ALL") {
    //we have write permission.
    $have_write_permission = true;
}  else {
    $have_write_permission = false;
}

//check if we are updating the goal text...
if(isset($_POST['update_goal']) && $have_write_permission) {
   if($_POST['goal_text'] == "") $MESSAGE = $MESSAGE . "You must supply goal text<BR>";
   else {
      $update_query="UPDATE long_term_goal SET area=";
      $update_query .= "'" . addslashes($_POST['goal_area']) . "'";
      $update_query .= ", review_date='" . addslashes($_POST['goal_review_date']) . "',goal='" . addslashes($_POST['goal_text']) . "' WHERE goal_id=$goal_id LIMIT 1";
      $update_result = mysql_query($update_query);
      if(!$update_result) {
         $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$update_query'<BR>";
         $MESSAGE=$MESSAGE . $error_message;
         IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
      }
    //rerun the queries
    $goal_query="SELECT long_term_goal.*,short_term_objective.*,long_term_goal.review_date AS goal_review_date FROM long_term_goal LEFT JOIN short_term_objective ON long_term_goal.goal_id=short_term_objective.goal_id WHERE long_term_goal.goal_id=" . addslashes($goal_id);
    $goal_result=mysql_query($goal_query);
    if(!$goal_result) {
     $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$goal_query'<BR>";
     $MESSAGE=$MESSAGE . $error_message;
     IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    } else {
      $goal_row=mysql_fetch_array($goal_result);
      $student_id=$goal_row['student_id'];
      $goal_review_date=$goal_row['goal_review_date'];
    }

   }
}

//check if we are adding an objective...
if(isset($_POST['add_objective']) && $have_write_permission) {
   if($_POST['description'] == "")  {
      $MESSAGE = $MESSAGE . "You must supply a short term objective<BR>";
   }  else {
     //check that date is the correct pattern...
     $regexp = '/^\d\d\d\d-\d\d?-\d\d?$/';
     if(!preg_match($regexp,$_POST['review_date'])) {
          $MESSAGE = $MESSAGE . "Date must be in YYYY-MM-DD format<BR>";
     } else {
       $insert_query = "INSERT INTO short_term_objective (goal_id,description,review_date,results_and_recommendations,strategies,assessment_procedure) values (";
       $insert_query = $insert_query . addslashes($goal_id) . ",";
       $insert_query = $insert_query . "'" . AddSlashes($_POST['description']) . "',";
       $insert_query = $insert_query . "'" . AddSlashes($_POST['review_date']) . "',";

       if($_POST['results_and_recommendations'] == "")
          $insert_query = $insert_query . "NULL,";
       else
          $insert_query = $insert_query . "'" . AddSlashes($_POST['results_and_recommendations']) . "',";

       if($_POST['strategies'] == "")
          $insert_query = $insert_query . "NULL,";
       else
          $insert_query = $insert_query . "'" . AddSlashes($_POST['strategies']) . "',";

       if($_POST['assessment_procedure'] == "")
          $insert_query = $insert_query . "NULL";
       else
          $insert_query = $insert_query . "'" . AddSlashes($_POST['assessment_procedure']) . "'";

       $insert_query = $insert_query . ")";

       $insert_result = mysql_query($insert_query);
       if(!$insert_result) {
         $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$insert_query'<BR>";
         $MESSAGE=$MESSAGE . $error_message;
         IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
       } else {
         unset($_POST['description']);
         unset($_POST['review_date']);
         unset($_POST['results_and_recommendations']);
         unset($_POST['strategies']);
         unset($_POST['assessment_procedure']);
       }
     }
   }
}

//rerun the query...ok, lazy...should be reworked
if($goal_id == "" && (!isset($goal_id) || $goal_id=="") ) {
   $MESSAGE = $MESSAGE . "An error occured: you have arrived here without a valid goal id number, perhaps you don't have permission levels necessary. lto='$goal_id'<BR>";
} else {
   if($goal_id == "") { $goal_id=$goal_id; }
   //find the student owner of this objective...
   $goal_query="SELECT * FROM long_term_goal LEFT JOIN short_term_objective ON long_term_goal.goal_id=short_term_objective.goal_id WHERE long_term_goal.goal_id=" . addslashes($goal_id);
   $goal_result=mysql_query($goal_query);
   if(!$goal_result) {
     $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$goal_query'<BR>";
     $MESSAGE=$MESSAGE . $error_message;
     IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
   } else {
      $goal_row=mysql_fetch_array($goal_result);
      $student_id=$goal_row['student_id'];
      //recheck permissions...
   }
}


//************** validated past here SESSION ACTIVE WRITE PERMISSION CONFIRMED****************

if($student_id) {
  $student_query = "SELECT * FROM student WHERE student_id = " . addslashes($student_id);
  $student_result = mysql_query($student_query);
  if(!$student_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$student_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
  } else {$student_row= mysql_fetch_array($student_result);}
}

//last thing...add an instructional note:

$MESSAGE = $MESSAGE . "<BR>Please add short term objectives to achieve this goal.<BR>Click the done button when done adding goals<BR>";

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
          return('Invalid result set parameter');
        }
        for($i=0;$i<$numRows;$i++){
          // build JavaScript array from result set
          $javascript.=$arrayName.'['.$i.']="';
          $tempOutput='';
          //output only the first column
          $row=mysql_fetch_array($dataSource);

          $bad_chars = array("\n", "\r");
          $tempOutput.= addslashes(str_replace($bad_chars, "\\n",$row[0])) . ' ';

          $javascript.=trim($tempOutput).'";';
        }
      }
      $javascript.='</script><!--End popup array-->'."\n";

      // return JavaScript code
      return $javascript;
    }

    function echoJSServicesArray() {
        global $MESSAGE;
        $asslist_query="SELECT DISTINCT `assessment_procedure`, COUNT(`assessment_procedure`) AS `count` FROM short_term_objective GROUP BY `assessment_procedure` ORDER BY `count` DESC LIMIT 200";
        $asslist_result = mysql_query($asslist_query);
        if(!$asslist_result) {
            $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$asslist_query'<BR>";
            $MESSAGE= $MESSAGE . $error_message;
            IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
        } else {
            //call the function to create the javascript array...
            if(mysql_num_rows($asslist_result)) echo createJavaScript($asslist_result,"popuplist");
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
          Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
    <script language="javascript" src="<?php echo IPP_PATH . "include/popcalendar.js"; ?>"></script>
    <script language="javascript" src="<?php echo IPP_PATH . "include/popupchooser.js"; ?>"></script>
    <?php
       //output the javascript array for the chooser popup
       echoJSServicesArray();
    ?>
    <script language="javascript" src="<?php echo IPP_PATH . "include/autocomplete.js"; ?>"></script>
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
                    <tr><td>
                    <center><?php navbar("long_term_goal_view.php?student_id=$student_id"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center>
                          <table>
                            <tr><td colspan="2">
                              <center><p class="header">- Add Short Term Objectives<BR>(<?php echo $student_row['first_name'] . " " . $student_row['last_name']; ?>)-</p></center>
                            </td></tr>
                            <tr><td colspan="2">
                               <center><a href="<?php echo IPP_PATH . "src/long_term_goal_view.php?student_id=" . $student_row['student_id'];?>"><img src="<?php echo IPP_PATH . "images/mainbutton.php?title=Done"?>" border="0"></a></center>
                            </td></tr>
                            <tr><td>&nbsp;&nbsp;</td>
                            <td>
                            <form name="edit_goal" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/add_objectives.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                              <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                              <tr>
                               <td colspan="3">
                               <p class="info_text">Change the goal text below and click 'Update'</p>
                               <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                               <input type="hidden" name="lto" value="<?php echo $goal_id; ?>">
                               <input type="hidden" name="update_goal" value="1">
                               </td>
                               </tr>
                              <tr><td bgcolor="#E0E2F2" valign="middle">
                              <p class="info"><b>Goal Area:&nbsp;</b>
                              </td><td bgcolor="#E0E2F2">
                              <input type="text" size="30" maxsize="100" name="goal_area" value="<?php echo $goal_row['area']; ?>">
                              </td>
                              <td bgcolor="#E0E2F2" rowspan="3">
                              <input type="submit" name="Update" value="Update">
                              </td>
                              </tr>
                              <tr><td bgcolor="#E0E2F2" valign="middle">
                              <p class="info"><b>Goal:</b>
                              </td><td bgcolor="#E0E2F2">
                              <textarea name="goal_text" cols="45" rows="3" wrap="soft"><?php echo $goal_row['goal']; ?></textarea>
                              </td></tr>
                              <tr><td bgcolor="#E0E2F2" valign="middle">
                              <p class="info"><b>Review Date:&nbsp;</b>
                              </td><td bgcolor="#E0E2F2">
                              <input type="text" name="goal_review_date" value="<?php echo $goal_review_date; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.goal_review_date, 'yyyy-m-dd', 0, 0)">
                              </td></tr>
                              </table>
                            </form>
                            <BR><p class="Header">Objectives already added to this goal:
                              <?php
                              //echo "<p class=\"info\"><b>Current Objective: </b>" . $goal_row['description'];
                              //do not while because we've already fetched one.
                              if($goal_row['description'] != "") {
                                do {
                                  echo "<p class=\"info\"><b>Objective: </b>" . $goal_row['description'];
                                } while ($goal_row = mysql_fetch_array($goal_result));
                              }
                              ?>
                              <BR><p class="Header">Add Another Objective:
                            </td>
                            </tr>
                          </table>
                        </center>
                        <BR>

                        <!-- BEGIN add short term objective -->
                        <center>
                        <form name="add_objective" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/add_objectives.php"; ?>" method="post" <?php if(!$have_write_permission) echo "onSubmit=\"return noPermission();\"" ?>>
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Add a new objective edit and click 'Add'.</p>
                           <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                           <input type="hidden" name="lto" value="<?php echo $goal_id; ?>">
                           <input type="hidden" name="add_objective" value="1">
                          </td>
                        </tr>
                        <tr>
                            <td valign="center" bgcolor="#E0E2F2" class="row_default">Short Term<BR>Objective:</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <textarea name="description" tabindex="1" cols="40" rows="3" wrap="soft"><?php if(isset($_POST['description'])) echo $_POST['description']; ?></textarea>
                            </td>
                        </tr>
                        <tr>
                           <td bgcolor="#E0E2F2" class="row_default">Review Date: (YYYY-MM-DD)</td>
                           <td bgcolor="#E0E2F2" class="row_default">
                               <input type="text" tabindex="2" name="review_date" value="<?php if(isset($_POST['review_date'])) echo $_POST['review_date']; ?>">&nbsp;<img src="<?php echo IPP_PATH . "images/calendaricon.gif"; ?>" height="17" width="17" border=0 onClick="popUpCalendar(this, document.all.review_date, 'yyyy-m-dd', 0, 0)">
                           </td>
                        </tr>
                        <tr>
                            <td valign="center" bgcolor="#E0E2F2" class="row_default">Assessment Procedure:</td>
                            <td bgcolor="#E0E2F2" class="row_default" valign="top">
                            <textarea name="assessment_procedure" tabindex="3" cols="35" rows="3" onkeypress="return autocomplete(this,event,popuplist)" wrap="soft"><?php if(isset($_POST['assessment_procedure'])) echo $_POST['assessment_procedure']; ?></textarea> &nbsp;<img src="<?php echo IPP_PATH . "images/choosericon.png"; ?>" height="17" width="17" border=0 onClick="popUpChooser(this,document.all.assessment_procedure)" >
                            </td>
                        </tr>
                        <tr>
                            <td valign="center" bgcolor="#E0E2F2" class="row_default">Strategies:</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <textarea name="strategies" tabindex="4" cols="40" rows="3" wrap="soft"><?php if(isset($_POST['strategies'])) echo $_POST['strategies']; ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td valign="center" bgcolor="#E0E2F2" class="row_default">Progress Review:</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <textarea name="results_and_recommendations" tabindex="5" cols="40" rows="3" wrap="soft"><?php if(isset($_POST['results_and_recommendations'])) echo $_POST['results_and_recommendations']; ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="row_default" bgcolor="#E0E2F2">&nbsp;</td>
                            <td valign="center" align="center" bgcolor="#E0E2F2"><input type="submit" tabindex="6" name="Add" value="Add"></td>
                        </tr>
                        </table>
                        </form>
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
            <?php navbar("long_term_goal_view.php?student_id=$student_id"); ?>
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