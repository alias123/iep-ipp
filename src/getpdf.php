<?php
$f=$HTTP_GET_VARS['f'];
//Check file (don't skip it!)
if(substr($f,0,3)!='tmp' or strpos($f,'/') or strpos($f,'\\'))
    die('Incorrect file name');
if(!file_exists($f))
    die('File does not exist');
//Handle special IE request if needed
if($HTTP_SERVER_VARS['HTTP_USER_AGENT']=='contype')
{
    Header('Content-Type: application/pdf');
    exit;
}
//Output PDF
Header('Content-Type: application/pdf');
Header('Content-Length: '.filesize($f));
readfile($f);
//Remove file
unlink($f);
exit;
?>