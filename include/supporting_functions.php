<?php

/**
 * supporting_functions.php 
 *
 * Copyright (c) 2007 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: May 19, 2007
 * By: M. Nielsen
 * Modified:
 *
 */

if(!defined('IPP_PATH')) define('IPP_PATH','../');

//spell checking functions
function checkSpelling ( $string )
{
   if (!extension_loaded("pspell")) {
      //spell libraries not loaded so just return the same string...
      return $string;
   }

   $pspell = pspell_new("en");
   $words = explode(" ", $string);
   $return = "";
   $trim =  ".!,?();:'\"\n\t\r";

   foreach($words as $word) {
     if (pspell_check($pspell, trim($word,$trim))) {
       // this word is fine; print as-is
       $return .= $word . " ";
     } else {
       //get up to 3 possible spellings for glossover...
       $suggestions = pspell_suggest($pspell,trim($word,$trim));
       $suggest = "";
       for($i = 0; $i < 3; $i++) {
          $suggest .= $suggestions[$i] . ",";
       }
       $suggest = substr($suggest, 0, -1);  //chop off the last comma
       $return .= "<span class='mispelt_text' title='$suggest'>$word </span>";
     }
   }
   return $return;
}

?>