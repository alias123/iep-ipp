<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100;    //anybody

/**
 * security_error.php -- a security page w/ info on violation.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: June 20, 2005
 * By: M. Nielsen
 * Modified:
 *
 */

/**
 * Path for IPP required files.
 */

$MESSAGE = $MESSAGE;
error_reporting(1);

define('IPP_PATH','../');

header('Pragma: no-cache'); //don't cache this page!


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
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table width="80%" cellspacing="0" cellpadding="0"><tr><td><center><p class="header">- Insufficient Permissions -</p></center></td></tr><tr><td><center><p class="header"> <?php echo $student_row['first_name'] . " " . $student_row['last_name']?></p></center></td></tr></table></center>
                        <BR>
                        You are trying to access a page that you do not seem to have the permissions to access.
                        </div>
                        </td>
                    </tr>
                </table></center>
            </td>
            <td class="shadow-right"></td>   
        </tr>
        <tr>
            <td class="shadow-left">&nbsp;</td>
            <td class="shadow-center"><table border="0" width="100%"><tr><td width="60"><a href="
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