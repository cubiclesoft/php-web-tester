<?php
	// Sample Javascript notification instrumentation file for Web Test.
	// (C) 2018 CubicleSoft.  All Rights Reserved.

	if (isset($_REQUEST["message"]))
	{
		$fp = fopen(str_replace("\\", "/", dirname(__FILE__)) . "/instrumented_js.log", "ab");
		fprintf($fp, "[" . date("Y-m-d H:i:s") . "] " . $_REQUEST["message"] . "\n");
		fclose($fp);
	}
?>