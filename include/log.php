<?php

    require_once(IPP_PATH . 'include/db.php');

    function IPP_Log($szMsg='', $username="-UNKNOWN-", $level='ERROR',$student_id='') {
        //Error Handler
        switch($level) {
            case 'WARNING' :
            case 'INFORMATIONAL':
            case 'ERROR':
                //connect
                if(!connectIPPDB()) {
                    return;  //crappy...but...oh well.
                }
                $log_query = "INSERT INTO error_log (level,username,time,message,student_id) VALUES ('$level','$username',now(),'" . addslashes($szMsg) . "',";
                if($student_id=="") $log_query=$log_query . "NULL";
                else $log_query=$log_query . "'$student_id'";
                $log_query = $log_query . ")";
                $log_result = mysql_query($log_query);
                //don't care about the result...if she don't log, she don't log.
                if(!$log_result) {
                  echo "log error: " . mysql_error() . "<BR>Query= " . $log_query . "<BR>";
                }
            break;
        }
        return TRUE;
    }

?>