<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 0; //only super administrator

/**
 * superuser_manage_codes.php -- Manage valid coding
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: February 19, 2007
 * By: M. Nielsen
 * Modified: 
 *
 */

/* 
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

//************** validated past here ****************

function parse_submission() {
    //returns null on success else returns $szError
    global $content,$fileName,$fileType;
    $regexp='/^[0-9]*$/';
    if(!preg_match($regexp, $_POST['code'])) return "You must supply a valid code number (numbers only)<BR>";
    if(!$_POST['code_text']) return "You must supply a code description<BR>";

    return NULL;
}

//check if we are adding a code...
if(isset($_POST['add_code'])) {
  $retval=parse_submission();
  if($retval != NULL) {
    //no way...
    $MESSAGE = $MESSAGE . $retval;
  } else {
    //we add the entry.
    $insert_query = "INSERT INTO valid_coding (code_number,code_text) VALUES ('" . addslashes($_POST['code']) . "','" . addslashes($_POST['code_text']) . "')";
    $insert_result = mysql_query($insert_query);
     if(!$insert_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '" . $insert_query . "<BR>";
        $MESSAGE= $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
     } else {
        //clear some fields
        unset($_POST['code']);
        unset($_POST['code_text']);
     }
  }
}

//check if we are deleting some entries...
if(isset($_POST['delete_x']) && $permission_level <= $IPP_MIN_DELETE_CODE) {
    $delete_query = "DELETE FROM valid_coding WHERE ";
    foreach($_POST as $key => $value) {
        if(preg_match('/^(\d)*$/',$key))
        $delete_query = $delete_query . "code_number=" . $key . " or ";
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

$code_query="SELECT * FROM valid_coding WHERE 1 ORDER by code_number ASC";
$code_result = mysql_query($code_query);
if(!$code_result) {
        $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$code_query'<BR>";
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
   <SCRIPT LANGUAGE="JavaScript">
      function confirmChecked() {
          var szGetVars = "codelist=";
          var szConfirmMessage = "Are you sure you want to delete the following:\n";
          var count = 0;
          form=document.codelist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + form.elements[x].name + ",";
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
                    <center><?php navbar("main.php"); ?></center>
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">-Manage Codes-</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN add code -->
                        <center>
                        <form name="add_code" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/superuser_manage_coding.php"; ?>" method="post">
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Add'.</p>
                           <input type="hidden" name="add_code" value="1">
                          </td>
                        </tr>
                        <tr>
                            <td valign="bottom" bgcolor="#E0E2F2" class="row_default">Code Number:</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <input type="text" tabindex="1" name="code" value="<?php if(isset($_POST['code']))  echo $_POST['code']; ?>" size="10" maxsize="10">
                            </td>
                            <td valign="center" align="center" bgcolor="#E0E2F2" rowspan="2" class="row_default"><input type="submit" tabindex="3" value="add" value="add"></td>
                        </tr>
                        <tr>
                            <td valign="bottom" bgcolor="#E0E2F2" class="row_default">Code Description</td>
                            <td bgcolor="#E0E2F2" class="row_default">
                            <input type="text" tabindex="2" name="code_text" value="<?php if(isset($_POST['code_text'])) echo $_POST['code_text']; ?>" size="30" maxsize="254">
                            </td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add code -->

                        <!-- BEGIN codes table -->
                        <form name="codelist" onSubmit="return confirmChecked();" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/superuser_manage_coding.php"; ?>" method="post">
                        <center><table width="80%" border="0" cellpadding="0" cellspacing="1">
                        <tr><td colspan="6">Codes:</td></tr>
                        <?php
                        $bgcolor = "#DFDFDF";

                        //print the header row...
                        echo "<tr><td bgcolor=\"#E0E2F2\">&nbsp;</td><td align=\"center\" bgcolor=\"#E0E2F2\">Code</td><td align=\"center\" bgcolor=\"#E0E2F2\">Code Description</td></tr>\n";
                        while ($code_row=mysql_fetch_array($code_result)) { //current...
                            echo "<tr>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><input type=\"checkbox\" name=\"" . $code_row['code_number'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\">" . $code_row['code_number'] . "</td>\n";
                            echo "<td bgcolor=\"$bgcolor\" class=\"row_default\">" . $code_row['code_text']  . "</td>\n";
                            echo "</tr>\n";
                            if($bgcolor=="#DFDFDF") $bgcolor="#CCCCCC";
                            else $bgcolor="#DFDFDF";
                        }
                        ?>
                        <tr>
                          <td colspan="6" align="left">
                             <table>
                             <tr>
                             <td nowrap>
                                <img src="<?php echo IPP_PATH . "images/table_arrow.png"; ?>">&nbsp;With Selected:
                             </td>
                             <td>
                             <?php
                                //if we have permissions also allow delete.
                                if($permission_level <= $IPP_MIN_DELETE_SCHOOL) {
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
                        <!-- end codes table -->

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
              <?php navbar("main.php"); ?>
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
