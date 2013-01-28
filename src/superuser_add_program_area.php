<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 20;  //assistant administrators

/**
 * superuser_add_program_area.php -- add a global program area
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: August 15, 2005
 * By: M. Nielsen
 * Modified:  December 01,2005
 *
 */

/**
 * Path for IPP required files.
 */

$MESSAGE = $MESSAGE;

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

//check if we do add...
if(isset($_GET['add'])) {
    $add_query = "INSERT into area_type (type) VALUES ('" . addslashes($_GET['type']) . "')";
    $add_result = mysql_query($add_query);
    if(!$add_result) {
        $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$add_query'<BR>";
        $MESSAGE=$MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    }
}

//check if we are deleting some peeps...
if($_GET['delete_x'] && $permission_level <= $IPP_MIN_DELETE_AREA_PERMISSION) {
    $delete_query = "DELETE FROM area_type WHERE ";
    foreach($_GET as $key => $value) {
        if(preg_match('/^(\d)*$/',$key))
        $delete_query = $delete_query . "area_type_id=" . $key . " or ";
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
    $MESSAGE = $MESSAGE . $delete_query . "<BR>";
}

$area_query = "SELECT * FROM area_type WHERE 1=1 ORDER BY type";
$area_result = mysql_query($area_query);
if(!$area_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$area_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
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
         -User Interface Design and Educational Factors by P Stoddart,
          Grasslands Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
    <SCRIPT LANGUAGE="JavaScript">
      function confirmChecked() {
          var szGetVars = "delete_supervisor=";
          var szConfirmMessage = "Are you sure you want to delete area:\n";
          var count = 0;
          form=document.arealist;
          for(var x=0; x<form.elements.length; x++) {
              if(form.elements[x].type=="checkbox") {
                  if(form.elements[x].checked) {
                     szGetVars = szGetVars + form.elements[x].name + "|";
                     szConfirmMessage = szConfirmMessage + "ID #" + form.elements[x].name + "\n";
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

                        <center><table><tr><td><center><p class="header">- IPP Add Global Program Area</p></center></td></tr></table></center>
                        <BR>

                        <!-- BEGIN add area -->
                        <center>
                        <form name="addarea" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/superuser_add_program_area.php"; ?>" method="get">
                        <table border="0" cellspacing="0" cellpadding ="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and click 'Add'.</p>
                           <input type="hidden" name="addarea" value="1">
                          </td>
                        </tr>
                        <tr>
                           <td valign="bottom" bgcolor="#E0E2F2">Area:</td><td bgcolor="#E0E2F2"><input type="text" name="type" value="" size="30" maxsize="255"></td>
                           <td valign="center" align="center" bgcolor="#E0E2F2"><input type="submit" name="add" value="add"></td>
                        </tr>
                        </table>
                        </form>
                        </center>
                        <!-- END add area -->

                        <!-- BEGIN area table -->
                        <form name="arealist" onSubmit="return confirmChecked();" enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/superuser_add_program_area.php"; ?>" method="get">
                        <center><table width="80%" border="0">

                        <?php
                        $bgcolor = "#DFDFDF";

                        //print the header row...
                        echo "<tr><td bgcolor=\"#E0E2F2\">&nbsp;</td><td bgcolor=\"#E0E2F2\">UID</td><td align=\"center\" bgcolor=\"#E0E2F2\">Username</td></tr>\n";
                        while ($area_row=mysql_fetch_array($area_result)) { //current...
                            echo "<tr>\n";
                            echo "<td bgcolor=\"#E0E2F2\"><input type=\"checkbox\" name=\"" . $area_row['area_type_id'] . "\"></td>";
                            echo "<td bgcolor=\"$bgcolor\">" . $area_row['area_type_id'] . "</td>";
                            echo "<td bgcolor=\"$bgcolor\">" . $area_row['type']  ."</td>\n";
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
                                //if we have permissions also allow delete and set all.
                                if($permission_level <= $IPP_MIN_DELETE_AREA_PERMISSION) {
                                    echo "<INPUT NAME=\"delete\" TYPE=\"image\" SRC=\"" . IPP_PATH . "images/smallbutton.php?title=Delete\" border=\"0\" value=\"delete\">";
                                }
                             ?>
                             </td>
                             </tr>
                             </table>
                          </td>
                        </tr>
                        </table></center>
                        </form>
                        <!-- end area table -->
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
                echo IPP_PATH . "src/main.php";
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