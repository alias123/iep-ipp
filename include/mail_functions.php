<?php

   if(!defined('IPP_PATH')) define('IPP_PATH','../');

   require_once(IPP_PATH . 'etc/init.php'); //make sure we have this.
   require_once(IPP_PATH . 'include/log.php');

//make sure we aren't accessing this file directly...
if(realpath ($_SERVER["SCRIPT_FILENAME"]) == realpath (__FILE__)) {
  $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
  IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
  require(IPP_PATH . 'src/security_error.php');
  exit();
}

function mail_notification($recipients="",$message="-unknown message-") {
       //Recipients currently only one!(separated by , )
       global $MESSAGE,$enable_email_notification,$mail_host,$append_to_username,$email_reply_address,$IPP_ORGANIZATION;

       if(!$enable_email_notification) return;
       if(!@include_once("Mail.php")) {
          $MESSAGE = "Your administrator does not have the Pear Mail Class installed<BR>No email notification has been sent<BR>";
          return 0;
       }; //pear mail module.
       if(!@include_once("Mail/mime.php")) {
          $MESSAGE = "You do not have the Pear Mail Class installed<BR>No email notification sent<BR>";
          return 0;
       }; //mime class
       if(!@include_once("Net/SMTP.php")) {
          $MESSAGE = "Your administrator does not have the Net/SMTP Pear Class installed<BR>No email notification has been sent<BR>";
          return 0;
       }


       $recipients = $recipients . $append_to_username; //Recipients (separated by , )

       //echo "send to: " . $recipients . "<BR>";

       $headers["From"]  = $email_reply_address;
       $headers["Subject"] = "IPP System ($IPP_ORGANIZATION)"; //Subject of the address
       $headers["MIME-Version"] = "1.0";
       $headers["To"] = $recipients;
       //$headers["Content-type"] = "text/html; charset=iso-8859-1";

       $mime=new Mail_mime("\r\n");
       //$mime->setTxtBody("This is an HTML message only");
       //$mime->_build_params['text_encoding']='quoted_printable';
       $mime->setHTMLBody("<html><body>$message</body></html>");
       $mime->setTXTBody($message);
       //$mime->addAttachment("Connection.pdf","application/pdf");

       $body= $mime->get();
       $hdrs = $mime->headers($headers);

       $params["host"] = $mail_host; //SMTP server (mail.yourdomain.net)
       $params["port"] = "25"; //Leave as is
       $params["auth"] = false; //Leave as is
       $params["username"] = "user"; //Username of from account
       $params["password"] = "password"; //Password of the above

       // Create the mail object using the Mail::factory method
       $mail_object =& Mail::factory("smtp", $params);

       $mail_object->send($recipients, $hdrs, $body); //Send the email using the Mail PEAR Class
       //echo "send to: $recipients,<BR>headers: $hdrs,<BR>body: $body<BR>";
}
?>
