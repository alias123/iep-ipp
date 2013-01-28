<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100;
/**
 * main.php -- main menu
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: May 13, 2005
 * By: M. Nielsen
 * Modified: June 06, 2005
 * Modified: February 10, 2007
 */

/**
 * Path for required files.
 */

if(isset($MESSAGE)) $MESSAGE = $MESSAGE;
else $MESSAGE = "";

define('IPP_PATH','../');

/* eGPS required files. */
require_once(IPP_PATH . 'etc/init.php');
require_once(IPP_PATH . 'include/db.php');
require_once(IPP_PATH . 'include/auth.php');
if ((int)phpversion() < 5) { require_once(IPP_PATH . 'include/fileutils.php'); } //only for pre v5
require_once(IPP_PATH . 'include/log.php');
require_once(IPP_PATH . 'include/navbar.php');

header('Pragma: no-cache'); //don't cache this page!

if(isset($_POST['LOGIN_NAME']) && isset( $_POST['PASSWORD'] )) {
    if(!validate( $_POST['LOGIN_NAME'] ,  $_POST['PASSWORD'] )) {
        $MESSAGE = $MESSAGE . $error_message;
        if(isset($_SESSION['egps_username'])) IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
        else IPP_LOG($MESSAGE,'no session','ERROR');
        require(IPP_PATH . 'src/login.php');
        exit();
    }
} else {
    if(!validate()) {
        $MESSAGE = $MESSAGE . $error_message;
        if(isset($_SESSION['egps_username'])) IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
        else IPP_LOG($MESSAGE,"no session",'ERROR');
        require(IPP_PATH . 'src/login.php');
        exit();
    }
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

//create the list of menu options based upon this users
//access rights.
$services = get_services($permission_level);
if(!$services) {
    //throw an error
    $MESSAGE = $MESSAGE . $error_message;
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
         -Concept and Design by Grasslands IPP Focus Group 2005
         -Programming and Database Design by M. Nielsen, Grasslands
          Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
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

                        <center><table><tr><td><center><p class="header">- Home -</p></center></td></tr></table></center>
                        <?php
                            $index = count($services);
                            //display list of menu options
                            $count=1;
                            echo "<center><table border=\"0\"><tr>";
                                while($index > 0) {
                                    if($count % 4 == 0) { echo "</tr><tr>"; $count=1; }
                                    $service = $services[$index-1];
                                    echo "<td><center><a href=\"" . $service->getLocation() . "\"><img src=\" " . IPP_PATH  . "images/mainbutton.php?title=" . $service->getTitle() . "\" border=0></a></center></td>\n";
                                    $index--;
                                    $count++;
                                }
                                while($count %4 != 0) {
                                   echo "<td>&nbsp;</td>";
                                   $count++;
                                }
                                echo "</tr></table></center>\n";
                            //end display menu options.
                        ?>
                        </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="shadow-right"></td>   
        </tr>
        <tr>
            <td class="shadow-left">&nbsp;</td>
            <td class="shadow-center"><table border="0" width="100%"><tr><td valign="bottom" align="center">Logged in as: <?php echo $_SESSION['egps_username'];?></td></tr></table><table border="0" width="100%"><tr><td align="right"><a class="small" target="_blank" href="http://www.iep-ipp.com">Bugs & Suggestions</a></td></tr></table></td>
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
