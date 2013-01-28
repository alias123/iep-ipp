<?php

/**
 * launch.php -- initial screen will launch the ipp program
 *               the rationale behind this is to get a window
 *               without navigational and file bars.
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * This a simple login screen.
 * Created: June 06, 2005
 * Modified:
 *
 */

/**
 * Path for eGPS required files.
 */

define('IPP_PATH','../');

/* eGPS required files. */
require_once(IPP_PATH . 'etc/init.php');

header('Pragma: no-cache'); //don't cache this page!
if(isset($MESSAGE)) $MESSAGE = $MESSAGE; else $MESSAGE="";

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
        <!-- This script and many more are available free online at -->
        <!-- The JavaScript Source!! http://javascript.internet.com -->

        <!-- Begin
           function Start(page) {
               OpenWin = window.open(page,"_blank", "toolbar=no,menubar=no,location=no,scrollbars=yes,resizable=yes");
               return FALSE;
           }
        // End -->
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

                        <center><table><tr><td><center><p class="header">- <?php echo $IPP_ORGANIZATION; ?> -<BR></p></center></td></tr></table></center>
                        <form enctype="multipart/form-data" action="<?php echo IPP_PATH . 'src/launch.php'; ?>" method="post" onSubmit="Start('<?php echo IPP_PATH . "src/login.php";?>')")>
                        <center><table>
                        <tr>
                            <td>
                                    Please click the 'Launch IPP' button below to launch the IEP-IPP program
                                    in a new browser window.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                    <center><input class="sbutton" type="submit" value="Launch IEP-IPP";"></center>
                            </td>
                        </tr>
                        </table>
                        </center>
                        </form>
                        </div>
                        </td>
                    </tr>
                </table> 
            </td>
            <td class="shadow-right"></td>   
        </tr>
        <tr>
            <td class="shadow-left">&nbsp;</td>
            <td class="shadow-center" valign="top">&nbsp;</p></right></td>
            <td class="shadow-right">&nbsp;</td>
        </tr>
        <tr>
            <td class="shadow-left">&nbsp;</td>
            <td class="shadow-center" halign="right">
                   &nbsp;</script>  
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
