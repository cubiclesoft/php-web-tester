<?php
	// Sample PHP instrumentation file for Web Test.
	// (C) 2018 CubicleSoft.  All Rights Reserved.

	error_reporting(E_ALL);
	ini_set("display_startup_errors", true);
	ini_set("display_errors", "1");
	ini_set("log_errors", true);
	ini_set("error_log", str_replace("\\", "/", dirname(__FILE__)) . "/instrumented_php.log");
?>