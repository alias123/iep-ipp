<?php

/**
 * index.php -- Displays the main frameset
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 *
 *
 * Redirects to the login page.
 *
 *
 */
define('IPP_PATH','./');

require_once(IPP_PATH . 'include/auth.php');

//force logout...
logout();
//header('Location: src/login.php');
header('Location: src/launch.php');
?>
<html></html>