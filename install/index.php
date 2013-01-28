<?php

//check if we have an init.php file already...security problem
if(is_file("../etc/init.php")) {
   die("To run the install, " . realpath("../etc/init.php") . " must not already exist!");
}


//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100;
/**
 * install wizard
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: February 17, 2007.
 * By: M. Nielsen
 */

/**
 * Path for required files.
 */


/* eGPS required files. */
//require_once(IPP_PATH . 'etc/init.php');
//require_once(IPP_PATH . 'include/db.php');
//require_once(IPP_PATH . 'include/auth.php');
//if ((int)phpversion() < 5) { require_once(IPP_PATH . 'include/fileutils.php'); } //only for pre v5
//require_once(IPP_PATH . 'include/log.php');
//require_once(IPP_PATH . 'include/navbar.php');

header('Pragma: no-cache'); //don't cache this page!


?> 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
    <TITLE>IEP-IPP Installation</TITLE>
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
                    <td><center><img src="./images/banner.png"></center></td>
                    </tr>
                    <tr><td>
                    &nbsp;
                    </td></tr>
                    <tr>
                        <td valign="top">
                        <div id="main">

                        <center><table><tr><td><center><p class="header">- Installation Check Dependencies -</p></center></td></tr></table></center>
        <BR><center><table width="80%" border="0"><tr><td><ul>

        <?php $fail=FALSE; ?>
                        <?php 
                          if (!extension_loaded("mysql")) {
                           echo "<li>Mysql Extensions are not loaded (FAIL)";
                           $fail=TRUE;
                          } else {
                           echo "<li>Mysql Extension is loaded (PASS)";
                          }
                          if (!extension_loaded("gd")) {
                           echo "<li>GD Libraries are not loaded (FAIL)";
                           $fail=TRUE;
                          } else {
                           echo "<li>GD Libraries are loaded (PASS)";
                          }
                          if (!extension_loaded("iconv")) {
                           echo "<li>iconv Libraries are not loaded (FAIL)";
                           $fail=TRUE;
                          } else {
                           echo "<li>iconv Libraries are loaded (PASS)";
                          }
                          if (!extension_loaded("pspell")) {
                           echo "<li>pspell Libraries are not loaded.<BR>&nbsp;&nbsp;You cannot use the spell checking functions. (Not Recommended)";
                          } else {
                           echo "<li>pspell Libraries are loaded (PASS)";
                          }
             if(!@include_once("Mail.php")) {
               echo "<li>Pear Mail Class is not loaded (Not Recommended)";
                          } else {
                           echo "<li>Pear Mail Class is loaded (Recommended)";
                          }
                          if(!@include_once("Mail/mime.php")) {
                           echo "<li>Pear Mail/Mime Class is not loaded (Not Recommended)";
                          } else {
                           echo "<li>Pear Mail/Mime Class is loaded (Recommended)";
                          }
                          if(!@include_once("Net/SMTP.php")) {
                           echo "<li>Pear Net/SMTP Class is not loaded (Not Recommended)";
                          } else {
                           echo "<li>Pear Net/SMTP Class is loaded (Recommended)";
                          }

                        ?>
                        </ul></td></tr></table></center>
                        <?php 
   echo "<form enctype=\"multipart/form-data\" action=\"./install/permissions.php" . "\" method=\"post\">";
   echo " <center><input class=\"sbutton\" type=\"submit\" value=\"Next\"";
   if($fail) echo " DISABLED";
   echo "></center>";
   echo "</form>";

                        
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
            <td class="shadow-center">&nbsp;</td>
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